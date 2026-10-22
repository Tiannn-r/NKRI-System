<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Assembly;
use App\Models\AssemblyTest;

class ReportController extends Controller
{
    /**
     * 1. Laporan Work Order Traveler (WO Traveler)
     * Endpoint: /api/laporan
     */
    public function traveler(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $operator = $request->query('operator');
        $statusParam = $request->query('status');
        $trayCode = $request->query('tray_code');
        $q = trim($request->query('q', ''));
        $sortBy = strtolower($request->query('sort_by', 'date'));
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $query = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->leftJoin('assembly_wo_history as h', function ($join) {
                $join->on('h.tray_id', '=', 'a.tray_id')
                     ->on('h.set_number', '=', 'a.set_number');
            })
            ->leftJoin('users as u_assy', function ($join) {
                $join->on('a.pic_assembly', '=', 'u_assy.username')
                     ->orOn('a.pic_assembly', '=', 'u_assy.full_name');
            })
            ->leftJoin('users as u_wh', function ($join) {
                $join->on('t.pic_warehouse', '=', 'u_wh.username')
                     ->orOn('t.pic_warehouse', '=', 'u_wh.full_name');
            })
            ->leftJoin('users as u_op', function ($join) {
                $join->on('t.assigned_operator', '=', 'u_op.username')
                     ->orOn('t.assigned_operator', '=', 'u_op.full_name');
            })
            ->select([
                'a.id',
                'a.tray_id',
                'a.set_number',
                DB::raw("COALESCE(a.part15_sn, h.part15_sn) as part15_sn"),
                DB::raw("COALESCE(a.part12_sn, h.part12_sn) as part12_sn"),
                DB::raw("COALESCE(a.part1_sn, h.part1_sn) as part1_sn"),
                DB::raw("COALESCE(a.status, h.status, 'Good') as status"),
                DB::raw("COALESCE(a.reason, h.reason) as reason"),
                DB::raw("COALESCE(a.defective_parts, h.defective_parts) as defective_parts"),
                'a.verified_status',
                'a.is_declined',
                'a.is_reregistered',
                'a.declined_reason',
                DB::raw("COALESCE(a.started_at, h.started_at) as started_at"),
                DB::raw("COALESCE(a.finished_at, h.finished_at) as finished_at"),
                DB::raw("COALESCE(u_assy.full_name, a.pic_assembly, h.pic_assembly) as pic_assembly"),
                't.tray_code',
                DB::raw("COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse"),
                DB::raw("COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator"),
                't.source_document_mo',
                DB::raw("COALESCE(t.product_type, 'Nanggala P9') as product_type"),
                DB::raw("COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang"),
                't.verified_at',
                't.verified_by_user',
                't.created_at as created_at',
                't.created_at as tray_created_at',
                'a.assy_doc_number',
                'a.qc_doc_number',
                'a.qc_status',
                'a.qc_failure_reasons',
                'a.qc_notes',
                'a.rework_reason',
                'a.lifecycle_path',
                DB::raw("COALESCE(a.assy_test_checklist, '[]') as assy_test_checklist"),
                DB::raw("COALESCE(a.assy_test_checklist, '[]') as test_checklist"),
                'a.assy_failure_reasons',
            ]);

        // Filter Rentang Tanggal
        if ($startDate) {
            $query->whereDate(DB::raw("COALESCE(a.finished_at, h.finished_at, a.started_at, h.started_at, t.created_at)"), '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate(DB::raw("COALESCE(a.finished_at, h.finished_at, a.started_at, h.started_at, t.created_at)"), '<=', $endDate);
        }

        // Filter Operator
        if ($operator) {
            $query->where(function ($w) use ($operator) {
                $like = "%{$operator}%";
                $w->where('a.pic_assembly', 'LIKE', $like)
                  ->orWhere('u_assy.full_name', 'LIKE', $like)
                  ->orWhere('t.pic_warehouse', 'LIKE', $like)
                  ->orWhere('u_wh.full_name', 'LIKE', $like)
                  ->orWhere('t.assigned_operator', 'LIKE', $like)
                  ->orWhere('u_op.full_name', 'LIKE', $like);
            });
        }

        // Filter Status
        if ($statusParam && !in_array($statusParam, ['Semua Status', 'ALL'])) {
            $query->whereRaw("UPPER(COALESCE(a.status, h.status, 'Good')) = UPPER(?)", [$statusParam]);
        }

        // Filter Tray Code
        if ($trayCode) {
            $query->where(function ($w) use ($trayCode) {
                $like = "%{$trayCode}%";
                $w->where('t.tray_code', 'LIKE', $like)
                  ->orWhere('a.assy_doc_number', 'LIKE', $like)
                  ->orWhere('t.source_document_mo', 'LIKE', $like);
            });
        }

        // Pencarian Umum (q)
        if ($q) {
            $query->where(function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where('a.part15_sn', 'LIKE', $like)
                  ->orWhere('a.part1_sn', 'LIKE', $like)
                  ->orWhere('a.part12_sn', 'LIKE', $like)
                  ->orWhere('t.tray_code', 'LIKE', $like)
                  ->orWhere('t.source_document_mo', 'LIKE', $like)
                  ->orWhere('a.reason', 'LIKE', $like);
            });
        }

        // Sorting
        switch ($sortBy) {
            case 'sn15':
                $query->orderBy(DB::raw("COALESCE(a.part15_sn, h.part15_sn)"), $order)->orderBy('a.id', $order);
                break;
            case 'sn1':
                $query->orderBy(DB::raw("COALESCE(a.part1_sn, h.part1_sn)"), $order)->orderBy('a.id', $order);
                break;
            case 'sn12':
                $query->orderBy(DB::raw("COALESCE(a.part12_sn, h.part12_sn)"), $order)->orderBy('a.id', $order);
                break;
            case 'doc':
                $query->orderBy('t.tray_code', $order)->orderBy('a.id', $order);
                break;
            case 'mo':
                $query->orderBy('t.source_document_mo', $order)->orderBy('a.id', $order);
                break;
            case 'operator':
                $query->orderBy(DB::raw("COALESCE(u_wh.full_name, t.pic_warehouse, u_op.full_name, t.assigned_operator, u_assy.full_name, a.pic_assembly)"), $order)->orderBy('a.id', $order);
                break;
            case 'status':
                $query->orderBy(DB::raw("COALESCE(a.status, h.status, 'Good')"), $order)->orderBy('a.id', $order);
                break;
            default:
                $query->orderBy(DB::raw("COALESCE(a.finished_at, h.finished_at, a.started_at, h.started_at, t.created_at)"), $order)
                      ->orderBy('t.tray_code', $order)
                      ->orderBy('a.set_number', $order)
                      ->orderBy('a.id', $order);
                break;
        }

        $rows = $query->get();
        return $this->successResponse($rows);
    }

    /**
     * 2. Laporan Uji Fungsional 1 (TF 1)
     * Endpoint: /api/reports/assy-tests
     */
    public function assyTests(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $operator = $request->query('operator');
        $docNumber = $request->query('doc_number');
        $statusParam = trim($request->query('status', ''));
        $q = trim($request->query('q', ''));
        $sortBy = strtolower($request->query('sort_by', 'date'));
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        // Query CTE atau Union All untuk menyajikan hasil uji TF1
        $query = DB::table(function ($unionQuery) {
            $unionQuery->select([
                't.id',
                't.stage',
                DB::raw("COALESCE(NULLIF(t.doc_number, '-'), t.tray_code) as doc_number"),
                't.tray_id',
                't.tray_code',
                't.set_number',
                't.assembly_id',
                DB::raw("COALESCE(t.part15_sn, t.p9_sn) as part15_sn"),
                't.part1_sn',
                't.part12_sn',
                't.tester_username',
                't.test_checklist',
                't.status',
                't.failure_reasons',
                't.notes',
                DB::raw("COALESCE(t.lifecycle_path, a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path"),
                DB::raw("COALESCE(t.status_history, a.assy_status_history, 'Good') as status_history"),
                't.created_at',
                DB::raw("COALESCE(t.tray_code, a.assy_doc_number) as assy_doc_number"),
                DB::raw("COALESCE(u.full_name, t.tester_username) as tester_full_name"),
            ])
            ->from('assembly_tests as t')
            ->leftJoin('assemblies as a', 't.assembly_id', '=', 'a.id')
            ->leftJoin('users as u', function ($j) {
                $j->on('t.tester_username', '=', 'u.username')
                  ->orOn('t.tester_username', '=', 'u.full_name');
            })
            ->where('t.stage', 'ASSY')

            ->unionAll(
                DB::table('assemblies as a')
                    ->join('trays as tr', 'a.tray_id', '=', 'tr.id')
                    ->leftJoin('users as u_op', function ($j) {
                        $j->on('tr.assigned_operator', '=', 'u_op.username')
                          ->orOn('tr.assigned_operator', '=', 'u_op.full_name');
                    })
                    ->select([
                        DB::raw("(1000000 + a.id) as id"),
                        DB::raw("'ASSY' as stage"),
                        DB::raw("COALESCE(a.assy_doc_number, tr.tray_code) as doc_number"),
                        'a.tray_id',
                        'tr.tray_code',
                        'a.set_number',
                        'a.id as assembly_id',
                        'a.part15_sn',
                        'a.part1_sn',
                        'a.part12_sn',
                        DB::raw("COALESCE(tr.assigned_operator, a.pic_assembly, '-') as tester_username"),
                        DB::raw("'[]' as test_checklist"),
                        DB::raw("'Menunggu Test Functional 1' as status"),
                        DB::raw("NULL as failure_reasons"),
                        DB::raw("'Menunggu pengujian oleh Operator Assembly' as notes"),
                        DB::raw("COALESCE(a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path"),
                        DB::raw("COALESCE(a.assy_status_history, 'Menunggu Uji TF1') as status_history"),
                        DB::raw("COALESCE(a.finished_at, a.started_at, tr.created_at) as created_at"),
                        DB::raw("COALESCE(tr.tray_code, a.assy_doc_number) as assy_doc_number"),
                        DB::raw("COALESCE(u_op.full_name, tr.assigned_operator, a.pic_assembly, '-') as tester_full_name"),
                    ])
                    ->where('a.status', 'Good')
                    ->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('assembly_tests')
                            ->whereColumn('assembly_tests.assembly_id', 'a.id')
                            ->where('assembly_tests.stage', 'ASSY');
                    })
            );
        }, 't');

        // Filters
        if ($startDate) {
            $query->whereDate('t.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('t.created_at', '<=', $endDate);
        }
        if ($operator) {
            $query->where(function ($w) use ($operator) {
                $like = "%{$operator}%";
                $w->where('t.tester_username', 'LIKE', $like)
                  ->orWhere('t.tester_full_name', 'LIKE', $like);
            });
        }
        if ($docNumber) {
            $query->where(function ($w) use ($docNumber) {
                $like = "%{$docNumber}%";
                $w->where('t.doc_number', 'LIKE', $like)
                  ->orWhere('t.assy_doc_number', 'LIKE', $like);
            });
        }
        if ($statusParam && $statusParam !== 'Semua Status') {
            if (strtoupper($statusParam) === 'MENUNGGU') {
                $query->where(function ($w) {
                    $w->where('t.status', 'LIKE', '%Menunggu%')
                      ->orWhere('t.status', 'LIKE', '%Belum%');
                });
            } else {
                $query->whereRaw("UPPER(t.status) = ?", [strtoupper($statusParam)]);
            }
        }
        if ($q) {
            $query->where(function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where('t.part15_sn', 'LIKE', $like)
                  ->orWhere('t.part1_sn', 'LIKE', $like)
                  ->orWhere('t.part12_sn', 'LIKE', $like)
                  ->orWhere('t.doc_number', 'LIKE', $like)
                  ->orWhere('t.assy_doc_number', 'LIKE', $like)
                  ->orWhere('t.failure_reasons', 'LIKE', $like);
            });
        }

        // Sorting
        switch ($sortBy) {
            case 'sn15':
                $query->orderBy('t.part15_sn', $order)->orderBy('t.id', $order);
                break;
            case 'sn1':
                $query->orderBy('t.part1_sn', $order)->orderBy('t.id', $order);
                break;
            case 'sn12':
                $query->orderBy('t.part12_sn', $order)->orderBy('t.id', $order);
                break;
            case 'doc':
                $query->orderBy('t.doc_number', $order)->orderBy('t.id', $order);
                break;
            case 'operator':
                $query->orderBy('t.tester_full_name', $order)->orderBy('t.id', $order);
                break;
            case 'status':
                $query->orderBy('t.status', $order)->orderBy('t.id', $order);
                break;
            default:
                $query->orderBy('t.created_at', $order)->orderBy('t.id', $order);
                break;
        }

        $rows = $query->get();
        return $this->successResponse($rows);
    }

    /**
     * 3. Laporan Uji Mutu Quality Control (TF 2 / QC)
     * Endpoint: /api/reports/qc-tests
     */
    public function qcTests(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $operator = $request->query('operator');
        $docNumber = $request->query('doc_number');
        $statusParam = trim($request->query('status', ''));
        $q = trim($request->query('q', ''));
        $sortBy = strtolower($request->query('sort_by', 'date'));
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $query = DB::table(function ($unionQuery) {
            $unionQuery->select([
                't.id',
                't.stage',
                DB::raw("COALESCE(NULLIF(t.doc_number, '-'), t.tray_code) as doc_number"),
                DB::raw("COALESCE(a.assy_doc_number, t.tray_code) as tf1_doc_number"),
                'a.assy_doc_number',
                't.tray_id',
                't.tray_code',
                't.set_number',
                't.assembly_id',
                DB::raw("COALESCE(t.part15_sn, t.p9_sn) as part15_sn"),
                't.part1_sn',
                't.part12_sn',
                't.tester_username',
                't.test_checklist',
                't.test_checklist as qc_test_checklist',
                't.created_at as qc_tested_at',
                't.tester_username as qc_tested_by',
                DB::raw("COALESCE(u.full_name, t.tester_username) as qc_tester_name"),
                't.status',
                't.failure_reasons',
                't.notes',
                DB::raw("COALESCE(t.lifecycle_path, a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path"),
                DB::raw("COALESCE(t.status_history, a.qc_status_history, 'Good QC') as status_history"),
                't.created_at',
                DB::raw("COALESCE(u.full_name, t.tester_username) as tester_full_name"),
                DB::raw("COALESCE(a.status, 'Good') as assembly_status"),
                DB::raw("COALESCE(a.assy_test_status, 'Good') as tf1_status"),
                'a.assy_failure_reasons as tf1_failure_reasons',
                'a.assy_test_checklist',
                'a.assy_test_checklist as tf1_test_checklist',
                'a.pic_assembly',
                DB::raw("COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly_name"),
                'a.started_at',
                'a.finished_at',
                't.status as qc_test_status',
                DB::raw("COALESCE(t.failure_reasons, t.notes, a.qc_failure_reasons) as qc_failure_reasons"),
            ])
            ->from('assembly_tests as t')
            ->leftJoin('assemblies as a', 't.assembly_id', '=', 'a.id')
            ->leftJoin('users as u', function ($j) {
                $j->on('t.tester_username', '=', 'u.username')
                  ->orOn('t.tester_username', '=', 'u.full_name');
            })
            ->leftJoin('users as u_op', function ($j) {
                $j->on('a.pic_assembly', '=', 'u_op.username')
                  ->orOn('a.pic_assembly', '=', 'u_op.full_name');
            })
            ->where('t.stage', 'QC')

            ->unionAll(
                DB::table('assemblies as a')
                    ->join('trays as tr', 'a.tray_id', '=', 'tr.id')
                    ->leftJoin('users as u_op', function ($j) {
                        $j->on('a.pic_assembly', '=', 'u_op.username')
                          ->orOn('a.pic_assembly', '=', 'u_op.full_name');
                    })
                    ->select([
                        DB::raw("(2000000 + a.id) as id"),
                        DB::raw("'QC' as stage"),
                        DB::raw("COALESCE(a.qc_doc_number, tr.tray_code) as doc_number"),
                        DB::raw("COALESCE(a.assy_doc_number, tr.tray_code) as tf1_doc_number"),
                        'a.assy_doc_number',
                        'a.tray_id',
                        'tr.tray_code',
                        'a.set_number',
                        'a.id as assembly_id',
                        'a.part15_sn',
                        'a.part1_sn',
                        'a.part12_sn',
                        DB::raw("'-' as tester_username"),
                        DB::raw("'[]' as test_checklist"),
                        DB::raw("'[]' as qc_test_checklist"),
                        DB::raw("NULL as qc_tested_at"),
                        DB::raw("NULL as qc_tested_by"),
                        DB::raw("NULL as qc_tester_name"),
                        DB::raw("'Menunggu Test Functional 2' as status"),
                        DB::raw("NULL as failure_reasons"),
                        DB::raw("'Menunggu pengujian mutu oleh Inspector QC' as notes"),
                        DB::raw("COALESCE(a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path"),
                        DB::raw("COALESCE(a.qc_status_history, 'Menunggu QC') as status_history"),
                        DB::raw("COALESCE(a.finished_at, tr.created_at) as created_at"),
                        DB::raw("'Menunggu Inspector QC' as tester_full_name"),
                        DB::raw("COALESCE(a.status, 'Good') as assembly_status"),
                        DB::raw("COALESCE(a.assy_test_status, 'Good') as tf1_status"),
                        'a.assy_failure_reasons as tf1_failure_reasons',
                        'a.assy_test_checklist',
                        'a.assy_test_checklist as tf1_test_checklist',
                        'a.pic_assembly',
                        DB::raw("COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly_name"),
                        'a.started_at',
                        'a.finished_at',
                        DB::raw("'Menunggu Test Functional 2' as qc_test_status"),
                        DB::raw("NULL as qc_failure_reasons"),
                    ])
                    ->where('a.status', 'Good')
                    ->where(function ($w) {
                        $w->where('a.assy_test_status', 'Good')
                          ->orWhereNotNull('a.assy_doc_number');
                    })
                    ->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('assembly_tests')
                            ->whereColumn('assembly_tests.assembly_id', 'a.id')
                            ->where('assembly_tests.stage', 'QC');
                    })
            );
        }, 't');

        // Filters
        if ($startDate) {
            $query->whereDate('t.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('t.created_at', '<=', $endDate);
        }
        if ($operator) {
            $query->where(function ($w) use ($operator) {
                $like = "%{$operator}%";
                $w->where('t.tester_username', 'LIKE', $like)
                  ->orWhere('t.tester_full_name', 'LIKE', $like);
            });
        }
        if ($docNumber) {
            $query->where(function ($w) use ($docNumber) {
                $like = "%{$docNumber}%";
                $w->where('t.doc_number', 'LIKE', $like)
                  ->orWhere('t.tf1_doc_number', 'LIKE', $like)
                  ->orWhere('t.assy_doc_number', 'LIKE', $like);
            });
        }
        if ($statusParam && $statusParam !== 'Semua Status') {
            if (strtoupper($statusParam) === 'MENUNGGU') {
                $query->where(function ($w) {
                    $w->where('t.status', 'LIKE', '%Menunggu%')
                      ->orWhere('t.status', 'LIKE', '%Belum%');
                });
            } else {
                $query->whereRaw("UPPER(t.status) = ?", [strtoupper($statusParam)]);
            }
        }
        if ($q) {
            $query->where(function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where('t.part15_sn', 'LIKE', $like)
                  ->orWhere('t.part1_sn', 'LIKE', $like)
                  ->orWhere('t.part12_sn', 'LIKE', $like)
                  ->orWhere('t.doc_number', 'LIKE', $like)
                  ->orWhere('t.tf1_doc_number', 'LIKE', $like)
                  ->orWhere('t.failure_reasons', 'LIKE', $like);
            });
        }

        // Sorting
        switch ($sortBy) {
            case 'sn15':
                $query->orderBy('t.part15_sn', $order)->orderBy('t.id', $order);
                break;
            case 'sn1':
                $query->orderBy('t.part1_sn', $order)->orderBy('t.id', $order);
                break;
            case 'sn12':
                $query->orderBy('t.part12_sn', $order)->orderBy('t.id', $order);
                break;
            case 'doc':
                $query->orderBy('t.doc_number', $order)->orderBy('t.id', $order);
                break;
            case 'operator':
                $query->orderBy('t.tester_full_name', $order)->orderBy('t.id', $order);
                break;
            case 'status':
                $query->orderBy('t.status', $order)->orderBy('t.id', $order);
                break;
            default:
                $query->orderBy('t.created_at', $order)->orderBy('t.id', $order);
                break;
        }

        $rows = $query->get();
        return $this->successResponse($rows);
    }

    /**
     * 4. Laporan Penerimaan Fisik Gudang
     * Endpoint: /api/reports/warehouse-receipts
     */
    public function warehouseReceipts(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $operator = $request->query('operator');
        $docNumber = $request->query('doc_number');
        $statusParam = $request->query('status');
        $q = trim($request->query('q', ''));
        $sortBy = strtolower($request->query('sort_by', 'date'));
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $query = DB::table('assemblies as a')
            ->join('trays as t', 'a.tray_id', '=', 't.id')
            ->leftJoin('users as u_wh', function ($j) {
                $j->on('a.warehouse_receipt_by', '=', 'u_wh.username')
                  ->orOn('a.warehouse_receipt_by', '=', 'u_wh.full_name');
            })
            ->leftJoin('users as u_op', function ($j) {
                $j->on('a.pic_assembly', '=', 'u_op.username')
                  ->orOn('a.pic_assembly', '=', 'u_op.full_name');
            })
            ->leftJoin('users as u_qc', function ($j) {
                $j->on('a.qc_tested_by', '=', 'u_qc.username')
                  ->orOn('a.qc_tested_by', '=', 'u_qc.full_name');
            })
            ->select([
                'a.id as assembly_id',
                'a.tray_id',
                'a.set_number',
                'a.part15_sn',
                'a.part12_sn',
                'a.part1_sn',
                DB::raw("COALESCE(a.status, 'Good') as assembly_status"),
                DB::raw("COALESCE(a.status, 'Good') as barang_status"),
                'a.started_at',
                'a.finished_at',
                'a.pic_assembly',
                DB::raw("COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly_name"),
                'a.assy_doc_number',
                'a.assy_doc_number as tf1_doc_number',
                DB::raw("COALESCE(a.assy_test_checklist, '[]') as assy_test_checklist"),
                DB::raw("COALESCE(a.assy_test_checklist, '[]') as tf1_test_checklist"),
                DB::raw("COALESCE(a.assy_test_status, 'Good') as assy_test_status"),
                'a.qc_doc_number',
                'a.qc_doc_number as qc_test_doc_number',
                'a.qc_status',
                'a.qc_status as qc_test_status',
                'a.qc_notes as qc_test_checklist',
                'a.qc_tested_at',
                'a.qc_tested_by',
                DB::raw("COALESCE(u_qc.full_name, a.qc_tested_by) as qc_tester_name"),
                DB::raw("COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc"),
                'a.reason',
                DB::raw("COALESCE(a.warehouse_receipt_status, 'PENDING') as warehouse_receipt_status"),
                'a.warehouse_receipt_at',
                'a.warehouse_receipt_by',
                DB::raw("COALESCE(a.declined_reason, a.reason) as declined_reason"),
                DB::raw("COALESCE(a.declined_reason, a.reason) as warehouse_decline_reason"),
                'a.warehouse_doc_number',
                't.tray_code',
                't.source_document_mo',
                't.product_type',
                DB::raw("COALESCE(u_wh.full_name, a.warehouse_receipt_by) as warehouse_receipt_by_name"),
            ])
            ->whereIn(DB::raw("UPPER(COALESCE(a.qc_status, ''))"), ['GOOD', 'GOOD_QC', 'PASS', 'PASSED']);

        // Filters
        if ($startDate) {
            $query->whereDate(DB::raw("COALESCE(a.warehouse_receipt_at, a.qc_tested_at, a.finished_at)"), '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate(DB::raw("COALESCE(a.warehouse_receipt_at, a.qc_tested_at, a.finished_at)"), '<=', $endDate);
        }
        if ($operator) {
            $query->where(function ($w) use ($operator) {
                $like = "%{$operator}%";
                $w->where('a.warehouse_receipt_by', 'LIKE', $like)
                  ->orWhere('u_wh.full_name', 'LIKE', $like);
            });
        }
        if ($docNumber) {
            $query->where('a.qc_doc_number', 'LIKE', "%{$docNumber}%");
        }
        if ($statusParam) {
            $st = strtoupper($statusParam);
            if ($st === 'PENDING') {
                $query->where(function ($w) {
                    $w->where('a.warehouse_receipt_status', 'PENDING')
                      ->orWhereNull('a.warehouse_receipt_status')
                      ->orWhere('a.warehouse_receipt_status', '');
                });
            } elseif (in_array($st, ['ACCEPTED', 'ACC'])) {
                $query->whereIn('a.warehouse_receipt_status', ['ACCEPTED', 'ACC']);
            } elseif ($st === 'DECLINED') {
                $query->where('a.warehouse_receipt_status', 'DECLINED');
            } else {
                $query->where('a.warehouse_receipt_status', $statusParam);
            }
        }
        if ($q) {
            $query->where(function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where('a.part15_sn', 'LIKE', $like)
                  ->orWhere('a.part1_sn', 'LIKE', $like)
                  ->orWhere('a.part12_sn', 'LIKE', $like)
                  ->orWhere('t.tray_code', 'LIKE', $like)
                  ->orWhere('a.qc_doc_number', 'LIKE', $like);
            });
        }

        // Sorting
        switch ($sortBy) {
            case 'sn15':
                $query->orderBy('a.part15_sn', $order)->orderBy('a.id', $order);
                break;
            case 'sn1':
                $query->orderBy('a.part1_sn', $order)->orderBy('a.id', $order);
                break;
            case 'sn12':
                $query->orderBy('a.part12_sn', $order)->orderBy('a.id', $order);
                break;
            case 'doc':
                $query->orderBy('a.qc_doc_number', $order)->orderBy('a.id', $order);
                break;
            case 'operator':
                $query->orderBy(DB::raw("COALESCE(u_wh.full_name, a.warehouse_receipt_by)"), $order)->orderBy('a.id', $order);
                break;
            case 'status':
                $query->orderBy(DB::raw("COALESCE(a.warehouse_receipt_status, 'PENDING')"), $order)->orderBy('a.id', $order);
                break;
            default:
                $query->orderBy(DB::raw("COALESCE(a.warehouse_receipt_at, a.qc_tested_at, a.finished_at)"), $order)->orderBy('a.id', $order);
                break;
        }

        $rows = $query->get();
        return $this->successResponse($rows);
    }
}
