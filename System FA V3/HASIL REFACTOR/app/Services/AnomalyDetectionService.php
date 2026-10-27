<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\PartAuditLog;

class AnomalyDetectionService
{
    /**
     * Memindai seluruh ketidaksinkronan data dan bottleneck penyebab lag
     */
    public function scanAll(): array
    {
        return $this->scanAnomalies();
    }

    public function scanAnomalies(): array
    {
        // 1. Orphan Parts (Part yang menunjuk ke tray yang sudah tidak ada)
        $orphanParts = DB::select("
            SELECT p.id, p.part_type, p.sn, p.tray_id 
            FROM parts p 
            LEFT JOIN trays t ON p.tray_id = t.id 
            WHERE t.id IS NULL
        ");

        // 2. Stuck Sessions (Sesi perakitan aktif menggantung lebih dari 8 jam)
        $stuckAssemblies = DB::select("
            SELECT a.id, a.tray_id, t.tray_code, a.set_number, a.pic_assembly, a.started_at,
                   TIMESTAMPDIFF(HOUR, a.started_at, NOW()) as hours_elapsed
            FROM assemblies a
            LEFT JOIN trays t ON a.tray_id = t.id
            WHERE a.finished_at IS NULL AND a.started_at IS NOT NULL
              AND TIMESTAMPDIFF(HOUR, a.started_at, NOW()) >= 8
        ");

        // 3. Stock Desync: Master stock status 'Supplied' tapi tray sudah tidak aktif atau selesai
        $desyncedStocks = DB::select("
            SELECT m.id, m.part_type, m.sn, m.status, m.allocated_tray_id, t.tray_code
            FROM master_stocks m
            LEFT JOIN trays t ON m.allocated_tray_id = t.id
            WHERE m.status = 'Supplied' AND (t.id IS NULL OR t.status IN ('finished', 'returned', 'completed'))
        ");

        // 4. Duplicate Part SN dalam tray aktif (disertai daftar kode baki/tray)
        $duplicatePartAssignments = DB::select("
            SELECT p.part_type, p.sn, COUNT(*) as occurrence_count,
                   GROUP_CONCAT(DISTINCT t.tray_code ORDER BY t.id ASC SEPARATOR ', ') as tray_codes
            FROM parts p
            JOIN trays t ON p.tray_id = t.id
            WHERE t.status = 'active'
            GROUP BY p.part_type, p.sn
            HAVING COUNT(*) > 1
            ORDER BY occurrence_count DESC, p.sn ASC
        ");

        $totalAnomalies = count($orphanParts) + count($stuckAssemblies) + count($desyncedStocks) + count($duplicatePartAssignments);

        return [
            'total_anomalies' => $totalAnomalies,
            'orphan_parts' => [
                'count' => count($orphanParts),
                'items' => $orphanParts,
                'description' => 'Part yang memiliki ID tray yang sudah tidak ada.',
            ],
            'stuck_sessions' => [
                'count' => count($stuckAssemblies),
                'items' => $stuckAssemblies,
                'description' => 'Perakitan yang dibiarkan menggantung berjalan lebih dari 8 jam tanpa diselesaikan.',
            ],
            'desynced_stocks' => [
                'count' => count($desyncedStocks),
                'items' => $desyncedStocks,
                'description' => 'Master stock berstatus Supplied padahal tray-nya sudah selesai atau tidak ada.',
            ],
            'duplicate_parts' => [
                'count' => count($duplicatePartAssignments),
                'items' => $duplicatePartAssignments,
                'description' => 'Nomor seri part ganda yang dialokasikan di lebih dari 1 tray aktif.',
            ],
        ];
    }

    /**
     * 1-Click Auto Repair: Memperbaiki anomali data secara otomatis
     */
    public function autoRepair(string $actorUsername = 'admin'): array
    {
        return DB::transaction(function () use ($actorUsername) {
            $repaired = [
                'orphan_parts_deleted' => 0,
                'stuck_sessions_closed' => 0,
                'stocks_resynced' => 0,
            ];

            // 1. Hapus orphan parts
            $repaired['orphan_parts_deleted'] = DB::delete("
                DELETE p FROM parts p 
                LEFT JOIN trays t ON p.tray_id = t.id 
                WHERE t.id IS NULL
            ");

            // 2. Tutup stuck sessions otomatis
            $repaired['stuck_sessions_closed'] = DB::update("
                UPDATE assemblies 
                SET finished_at = NOW(), 
                    status = 'Hold', 
                    reason = 'Otomatis di-Hold oleh Sistem Anomaly Scanner (Stuck >8 Jam)' 
                WHERE finished_at IS NULL AND started_at IS NOT NULL 
                  AND TIMESTAMPDIFF(HOUR, started_at, NOW()) >= 8
            ");

            // 3. Sinkronkan master stock yang desync (putus total seluruh relasi lama)
            $repaired['stocks_resynced'] = DB::update("
                UPDATE master_stocks m
                LEFT JOIN trays t ON m.allocated_tray_id = t.id
                SET m.status = 'Tersedia', 
                    m.allocated_tray_id = NULL,
                    m.allocated_at = NULL,
                    m.used_at = NULL,
                    m.last_tray_id = NULL,
                    m.is_replaced_from_assy = 0
                WHERE m.status = 'Supplied' AND (t.id IS NULL OR t.status IN ('finished', 'returned', 'completed'))
            ");

            // Log tindakan perbaikan di audit
            PartAuditLog::create([
                'part_type' => 'SYSTEM',
                'sn' => 'AUTO_REPAIR',
                'old_status' => 'ANOMALIES_DETECTED',
                'new_status' => 'NORMALIZED',
                'action_type' => 'ANOMALY_AUTO_REPAIR',
                'actor_username' => $actorUsername,
                'actor_role' => 'admin',
                'reason' => 'Perbaikan otomatis anomali integritas data via CMS Admin',
            ]);

            return [
                'ok' => true,
                'message' => 'Seluruh anomali data berhasil dinormalisasi dan diperbaiki.',
                'details' => $repaired,
            ];
        });
    }
}
