<?php



namespace App\Http\Controllers;



use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\Assembly;

use App\Models\Tray;

use App\Models\MasterStock;

use App\Models\PartAuditLog;

use App\Models\AssemblyTest;



class QcController extends Controller

{

    /**

     * Mengambil daftar seluruh unit yang masuk ke antrean Quality Control (TF 2)

     */

    public function items(Request $request)

    {

        $status = $request->input('status'); // 'pending', 'history', 'all'

        $trayCode = $request->input('tray_code');



        $query = DB::table('assemblies as a')

            ->join('trays as t', 'a.tray_id', '=', 't.id')

            ->select([

                'a.id',

                'a.tray_id',

                't.tray_code',

                'a.set_number',

                'a.pic_assembly',

                'a.part15_sn',

                'a.part12_sn',

                'a.part1_sn',

                'a.status as assembly_status',

                'a.assy_test_status',

                'a.reason',

                'a.rework_reason',

                'a.assy_test_checklist', // Rekap hasil 17 checklist TF 1 untuk ditampilkan Read-Only di TF 2

                'a.notes as assy_notes',

                'a.assy_tester_username',

                'a.assy_tested_at',

                'a.assy_doc_number',

                'a.qc_status',

                'a.qc_failure_reasons',

                'a.qc_inspector_username',

                'a.qc_tested_at',

                'a.qc_doc_number',

                'a.started_at',

                'a.finished_at',

            ]);



        // Yang berhak masuk ke TF 2 adalah barang yang Good di perakitan

        $query->where('a.status', 'Good');



        if ($trayCode) {

            $query->where('t.tray_code', $trayCode);

        }



        $items = $query->orderBy('a.id', 'desc')->get()->map(function ($item) {

            $item->assy_test_checklist = json_decode($item->assy_test_checklist ?: '[]');

            return $item;

        });



        $pendingItems = $items->filter(function ($i) {

            $st = strtoupper($i->qc_status ?? '');

            return ($st === 'PENDING_QC' || $st === 'PENDING' || empty($st)) 

                && $i->assembly_status === 'Good' 

                && strtoupper($i->assy_test_status ?? '') === 'GOOD'

                && !empty($i->assy_doc_number);

        })->values();



        $historyItems = $items->filter(function ($i) {

            $st = strtoupper($i->qc_status ?? '');

            return in_array($st, ['GOOD', 'HOLD_QC', 'REWORK_QC', 'HOLD', 'RU']) || !empty($i->qc_doc_number) || !empty($i->qc_tested_at);

        })->values();



        $responseData = $items;

        if ($status === 'pending') {

            $responseData = $pendingItems;

        } elseif ($status === 'history') {

            $responseData = $historyItems;

        } else {

            // Default untuk pending-items: data = pending, history_data = history, all_data = all

            $responseData = $pendingItems;

        }



        return response()->json([

            'ok' => true,

            'message' => 'Success',

            'data' => $responseData,

            'pending' => $pendingItems,

            'history' => $historyItems,

            'history_data' => $historyItems,

            'all_data' => $items,

        ]);

    }



    /**

     * Keputusan Uji Mutu Quality Control (TF 2)

     * ATURAN BAKU: Hanya ada 2 pilihan: 'Good' atau 'Not Good' (Hold).

     * TIDAK ADA 17 PENGUJIAN DI QC. QC HANYA MEMILIH SEKALI KLIK.

     */

    public function submitTest(Request $request)

    {

        $rawDecision = $request->input('decision') ?: $request->input('status');

        $normalizedDecision = 'Good';

        if ($rawDecision) {

            $lower = strtolower(trim($rawDecision));

            if (in_array($lower, ['good', 'lolos', 'ok', 'pass'])) {

                $normalizedDecision = 'Good';

            } elseif (in_array($lower, ['not good', 'hold', 'hold_qc', 'not_good', 'fail', 'ng'])) {

                $normalizedDecision = 'Hold';

            }

        }

        $request->merge(['decision' => $normalizedDecision]);



        $request->validate([

            'assembly_id' => 'required|integer',

            'decision' => 'required|in:Good,Not Good,Hold,good,not good,hold,GOOD,NOT_GOOD,HOLD,NOT GOOD',

            'notes' => 'nullable|string',

        ]);



        try {

            return DB::transaction(function () use ($request, $normalizedDecision) {

                $assembly = Assembly::findOrFail($request->assembly_id);

                $inspector = $request->input('qc_inspector_username')
                    ?: $request->input('qc_tested_by')
                    ?: $request->input('qc_inspector_fullname')
                    ?: $request->header('X-User-Full-Name')
                    ?: $request->header('X-User-Name')
                    ?: auth()->user()?->full_name
                    ?: auth()->user()?->username
                    ?: 'Administrator NKRI';

                $isGood = ($normalizedDecision === 'Good');



                // 1. VALIDASI KEPATUHAN ALUR: Wajib telah lolos perakitan dan lolos TF 1

                if (strtoupper($assembly->status ?? '') !== 'GOOD' || strtoupper($assembly->assy_test_status ?? '') !== 'GOOD') {

                    return $this->errorResponse('Unit belum lolos Uji Fungsional 1 (TF 1) dengan status Good! Tidak dapat diverifikasi QC.', 400);

                }



                // 2. Kunci permanen jika sudah diverifikasi fisik oleh Gudang

                if (in_array(strtoupper($assembly->warehouse_receipt_status ?? ''), ['ACCEPTED', 'ACC', 'DECLINED']) || in_array(strtoupper($assembly->verified_status ?? ''), ['ACC', 'ACCEPTED', 'DECLINED'])) {

                    return $this->errorResponse('Unit ini sudah diverifikasi fisik oleh Gudang dan terkunci permanen!', 400);

                }



                // Format nomor dokumen QC: WH/QC/xxxxxx (atau WH/QC/PU/angka jika perakitan ulang)

                $tray = Tray::find($assembly->tray_id);

                $isPU = $tray && (str_contains($tray->tray_code, 'PU') || str_contains($tray->tray_code, '-PU'));

                if ($isPU) {

                    preg_match('/PU[^\d]*(\d+)/i', $tray->tray_code, $m);

                    $puNum = !empty($m[1]) ? (int)$m[1] : 1;

                    $qcDocNumber = "WH/QC/PU/{$puNum}";

                } else {

                    $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);

                    $qcDocNumber = "WH/QC/{$docSeq}";

                }



                $rawChecklist = $request->input('test_checklist') ?: $assembly->assy_test_checklist;

                $checklistJson = is_string($rawChecklist) ? $rawChecklist : json_encode($rawChecklist ?: []);



                $assembly->update([

                    'qc_status' => $isGood ? 'GOOD' : 'HOLD_QC',

                    'qc_failure_reasons' => $isGood ? null : ($request->input('notes') ?: 'Tertahan di Quality Control (QC)'),

                    'qc_notes' => $request->input('notes'),

                    'qc_inspector_username' => $inspector,

                    'qc_tested_by' => $inspector,

                    'qc_tested_at' => now(),

                    'qc_doc_number' => $qcDocNumber,

                ]);



                // Sinkronkan ke tabel assembly_tests agar laporan TF 2 / QC dan histori audit lengkap

                AssemblyTest::updateOrCreate(

                    [

                        'assembly_id' => $assembly->id,

                        'stage' => 'QC',

                    ],

                    [

                        'tray_id' => $assembly->tray_id,

                        'tray_code' => $tray->tray_code ?? '',

                        'set_number' => $assembly->set_number,

                        'doc_number' => $qcDocNumber,

                        'part15_sn' => $assembly->part15_sn,

                        'part1_sn' => $assembly->part1_sn,

                        'part12_sn' => $assembly->part12_sn,

                        'status' => $isGood ? 'Good' : 'Hold',

                        'failure_reasons' => $isGood ? null : ($request->input('notes') ?: 'Tertahan di Quality Control (QC)'),

                        'notes' => $request->input('notes'),

                        'test_checklist' => is_string($checklistJson) ? (json_decode($checklistJson, true) ?: $checklistJson) : $checklistJson,

                        'tester_username' => $inspector,

                    ]

                );



                // Jika Not Good di QC, update status part di master stock

                if (!$isGood) {

                    foreach (array_filter([$assembly->part15_sn, $assembly->part1_sn, $assembly->part12_sn]) as $sn) {

                        MasterStock::where('sn', trim($sn))->update([

                            'status' => 'Hold',

                            'last_reason' => $request->input('notes') ?: 'Tertahan di Quality Control (QC)',

                        ]);

                    }



                    if ($assembly->part15_sn) {

                        PartAuditLog::create([

                            'part_type' => '15',

                            'sn' => $assembly->part15_sn,

                            'old_status' => 'Good',

                            'new_status' => 'HOLD_QC',

                            'action_type' => 'QC_DECISION_HOLD',

                            'actor_username' => $inspector,

                            'actor_role' => 'qc',

                            'reason' => $request->input('notes') ?: 'Keputusan QC: Not Good',

                        ]);

                    }

                }



                return $this->successResponse($assembly, $isGood ? 'Unit Lolos Quality Control (Good).' : 'Unit Tertahan di Quality Control (Hold).');

            });

        } catch (\Illuminate\Validation\ValidationException $e) {

            throw $e;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return $this->errorResponse("Data perakitan ID #{$request->assembly_id} tidak ditemukan.", 404);

        } catch (\Throwable $e) {

            return $this->errorResponse("Gagal menyimpan hasil uji QC: " . $e->getMessage(), 500);

        }

    }



    /**

     * Alias untuk items()

     */

    public function getItems(Request $request)

    {

        return $this->items($request);

    }



    /**

     * Daftar Unit yang Tertahan (Hold Items) di QC atau Perakitan

     */

    public function getHoldItems(Request $request)

    {

        $productType = $request->input('product_type');

        $source = strtoupper(trim($request->input('source', '')));

        $q = trim($request->input('q', ''));



        $query = DB::table('assemblies as a')

            ->join('trays as t', 'a.tray_id', '=', 't.id')

            ->select([

                'a.id as assembly_id',

                'a.tray_id',

                'a.set_number',

                'a.part15_sn',

                'a.part12_sn',

                'a.part1_sn',

                'a.status as assembly_status',

                'a.status',

                'a.assy_test_status',

                'a.assy_test_checklist',

                'a.assy_failure_reasons',

                'a.assy_tested_at',

                'a.assy_tester_username',

                'a.pic_assembly',

                'a.qc_status',

                'a.verified_status',

                'a.reason as assembly_reason',

                'a.reason',

                'a.notes',

                'a.qc_failure_reasons',

                'a.qc_notes',

                'a.rework_reason',

                'a.started_at',

                'a.finished_at',

                'a.qc_tested_at',

                'a.assy_doc_number',

                'a.qc_doc_number',

                'a.is_reregistered',

                'a.defective_parts',

                't.tray_code',

                't.product_type',

                DB::raw("(SELECT COUNT(*) FROM part_replacement_requests WHERE part_replacement_requests.assembly_id = a.id AND part_replacement_requests.status = 'PENDING') as pending_replacement_count"),

                DB::raw("(SELECT COUNT(*) FROM re_register_requests WHERE re_register_requests.status = 'PENDING' AND re_register_requests.sn IN (a.part15_sn, a.part1_sn, a.part12_sn)) as pending_rereg_count"),

                DB::raw("CASE 
                    WHEN a.qc_status IN ('HOLD_QC', 'REWORK_QC') OR (a.qc_failure_reasons IS NOT NULL AND a.assy_failure_reasons IS NULL) THEN 'QC'
                    ELSE 'TESTER_ASSEMBLY'
                END as hold_source")
            ])
            ->where(function ($w) {
                // 1. Hold atau RU pada Uji Mutu QC (TF 2)
                $w->where(function ($sub) {
                    $sub->where(function ($s) {
                        $s->whereIn('a.qc_status', ['HOLD_QC', 'REWORK_QC'])
                          ->orWhere(function ($s2) {
                              $s2->where('a.qc_status', 'RU')
                                 ->whereNotNull('a.qc_failure_reasons');
                          });
                    })
                    ->where(function ($sub2) {
                        $sub2->where('a.status', 'Good')
                             ->orWhere('a.status', 'Dirakit Ulang')
                             ->orWhere('a.verified_status', 'WAITING_REASSEMBLY')
                             ->orWhere('a.verified_status', 'APPROVED_REASSEMBLY');
                    });
                })
                // 2. Hold atau RU pada Uji Fungsional 1 (TF 1)
                ->orWhere(function ($sub) {
                    $sub->where(function ($s) {
                        $s->whereIn('a.assy_test_status', ['Hold TF 1', 'Hold', 'Not Good', 'FAIL'])
                          ->orWhere(function ($s2) {
                              $s2->where('a.assy_test_status', 'RU')
                                 ->where(function ($s3) {
                                     $s3->whereNotNull('a.assy_failure_reasons')
                                        ->orWhere('a.verified_status', 'APPROVED_REASSEMBLY')
                                        ->orWhere('a.verified_status', 'WAITING_REASSEMBLY');
                                 });
                          });
                    })
                    ->where(function ($sub2) {
                        $sub2->where('a.status', 'Good')
                             ->orWhere('a.status', 'Dirakit Ulang')
                             ->orWhere('a.verified_status', 'WAITING_REASSEMBLY')
                             ->orWhere('a.verified_status', 'APPROVED_REASSEMBLY');
                    });
                });
            });

        if ($productType && $productType !== 'ALL') {
            $query->where('t.product_type', $productType);
        }

        if ($source === 'QC') {
            $query->where(function ($q) {
                $q->whereIn('a.qc_status', ['HOLD_QC', 'REWORK_QC'])
                  ->orWhere(function ($q2) {
                      $q2->where('a.qc_status', 'RU')
                         ->whereNotNull('a.qc_failure_reasons');
                  })
                  ->orWhere(function ($q3) {
                      $q3->whereNotNull('a.qc_failure_reasons')->whereNull('a.assy_failure_reasons');
                  });
            });
        } elseif ($source === 'ASSY' || $source === 'TESTER_ASSEMBLY') {
            $query->where(function ($q) {
                $q->whereIn('a.assy_test_status', ['Hold TF 1', 'Hold', 'Not Good', 'FAIL'])
                  ->orWhere('a.assy_test_status', 'RU');
            });
        }


        if ($q) {

            $query->where(function ($w) use ($q) {

                $w->where('a.part15_sn', 'like', "%{$q}%")

                  ->orWhere('a.part12_sn', 'like', "%{$q}%")

                  ->orWhere('a.part1_sn', 'like', "%{$q}%")

                  ->orWhere('t.tray_code', 'like', "%{$q}%")

                  ->orWhere('a.reason', 'like', "%{$q}%")

                  ->orWhere('a.assy_failure_reasons', 'like', "%{$q}%")

                  ->orWhere('a.qc_failure_reasons', 'like', "%{$q}%");

            });

        }



        $items = $query->orderBy('a.id', 'desc')->get();

        return $this->successResponse($items);

    }



    /**

     * Persetujuan / Tindakan Hold Item oleh Administrator

     */

    public function approveHoldItem(Request $request)

    {

        $assemblyId = $request->input('assembly_id');

        $decision = strtoupper($request->input('decision', 'APPROVE_GOOD'));

        $notes = $request->input('notes', '');



        $assembly = Assembly::findOrFail($assemblyId);



        $isGood = in_array($decision, ['APPROVE', 'GOOD', 'APPROVE_GOOD', 'APPROVE_QC', 'APPROVE_GOOD_QC', 'APPROVE_TF2', 'APPROVE_GUDANG', 'APPROVE_PROCEED_TF1', 'APPROVE_PROCEED_TF2']) || str_starts_with($decision, 'APPROVE_GOOD') || str_starts_with($decision, 'APPROVE_PROCEED');

        if ($isGood) {

            $isQC = in_array(strtoupper($assembly->qc_status ?? ''), ['HOLD_QC', 'HOLD', 'HOLD TF 2', 'REWORK_QC']) ||
                $assembly->verified_status === 'PENDING_ADMIN_QC' ||
                str_contains($decision, '_QC') ||
                str_contains($assembly->notes ?? '', 'Penerimaan');

            $actor = $this->resolveActor($request)['full_name'];

            if ($isQC) {
                $qcDocNumber = $assembly->qc_doc_number;
                if (!$qcDocNumber) {
                    $tray = Tray::find($assembly->tray_id);
                    $docSeq = ($tray && preg_match('/(\d+)$/', $tray->tray_code, $m)) ? str_pad($m[1], 6, '0', STR_PAD_LEFT) : str_pad($assembly->tray_id, 6, '0', STR_PAD_LEFT);
                    $qcDocNumber = "WH/QC/{$docSeq}";
                }

                $assembly->update([
                    'qc_status' => 'GOOD',
                    'status' => 'Good',
                    'verified_status' => 'APPROVED_QC',
                    'qc_failure_reasons' => null,
                    'qc_tested_at' => $assembly->qc_tested_at ?: now(),
                    'qc_tested_by' => $assembly->qc_tested_by ?: $actor,
                    'qc_doc_number' => $qcDocNumber,
                    'warehouse_receipt_status' => 'PENDING',
                    'qc_notes' => $notes ?: 'Disetujui oleh Administrator untuk lanjut ke Penerimaan Gudang',
                ]);

                PartAuditLog::create([
                    'part_type' => 'SET',
                    'sn' => $assembly->part15_sn ?: ("Set #" . $assembly->set_number),
                    'old_status' => 'HOLD_QC',
                    'new_status' => 'APPROVED_QC',
                    'action_type' => 'ADMIN_APPROVE_HOLD_QC',
                    'actor_username' => $actor,
                    'actor_role' => 'admin',
                    'reason' => "Admin menyetujui Set #{$assembly->set_number} Good QC untuk lanjut ke Penerimaan Fisik Gudang. {$notes}",
                ]);

            } elseif ($decision === 'APPROVE_PROCEED_TF1' || $assembly->verified_status === 'PENDING_ADMIN_PROCEED_TF1' || ($assembly->status === 'Hold' && (empty($assembly->assy_test_status) || $assembly->assy_test_status === 'PENDING'))) {

                $assembly->update([
                    'status' => 'Good',
                    'assy_test_status' => 'PENDING',
                    'assy_test_checklist' => null,
                    'assy_tested_at' => null,
                    'assy_doc_number' => null,
                    'assy_failure_reasons' => null,
                    'verified_status' => 'APPROVED_PROCEED_TF1',
                    'notes' => $notes ?: 'Disetujui oleh Administrator untuk lanjut ke Test Functional 1 (TF 1)',
                ]);

                PartAuditLog::create([
                    'part_type' => 'SET',
                    'sn' => $assembly->part15_sn ?: ("Set #" . $assembly->set_number),
                    'old_status' => 'HOLD_ASSY',
                    'new_status' => 'APPROVED_TF1',
                    'action_type' => 'ADMIN_APPROVE_PROCEED_TF1',
                    'actor_username' => $actor,
                    'actor_role' => 'admin',
                    'reason' => "Admin menyetujui Set #{$assembly->set_number} untuk lanjut ke Test Functional 1 (TF 1). {$notes}",
                ]);

            } else {

                $assembly->update([
                    'assy_test_status' => 'Good',
                    'status' => 'Good',
                    'qc_status' => 'PENDING_QC',
                    'qc_test_checklist' => null,
                    'qc_tested_at' => null,
                    'qc_doc_number' => null,
                    'qc_failure_reasons' => null,
                    'verified_status' => 'APPROVED_PROCEED_TF2',
                    'notes' => $notes ?: 'Disetujui oleh Administrator untuk lanjut ke TF 2',
                ]);

                PartAuditLog::create([
                    'part_type' => 'SET',
                    'sn' => $assembly->part15_sn ?: ("Set #" . $assembly->set_number),
                    'old_status' => 'HOLD_TF1',
                    'new_status' => 'APPROVED_TF2',
                    'action_type' => 'ADMIN_APPROVE_HOLD_TF1',
                    'actor_username' => $actor,
                    'actor_role' => 'admin',
                    'reason' => "Admin menyetujui Set #{$assembly->set_number} Good TF1 untuk lanjut ke TF 2 (QC). {$notes}",
                ]);

            }

        } elseif (in_array($decision, ['SET_REWORK', 'REWORK'])) {

            $assembly->update([

                'qc_status' => 'REWORK_QC',

                'status' => 'Rework',

                'rework_reason' => $notes ?: 'Diminta perbaikan/rework oleh Administrator',

            ]);

        } elseif (in_array($decision, ['REJECT', 'DECLINE'])) {

            $assembly->update([

                'verified_status' => 'DECLINED',

                'is_declined' => true,

                'declined_reason' => $notes ?: 'Permohonan ditolak Administrator',

            ]);

        }



        return $this->successResponse($assembly, 'Status hold item berhasil diperbarui');

    }

}

