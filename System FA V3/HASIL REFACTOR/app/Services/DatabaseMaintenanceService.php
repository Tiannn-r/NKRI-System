<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\PartAuditLog;

class DatabaseMaintenanceService
{
    /**
     * Pembersihan Database Seluruh Tray:
     * - Kosongkan seluruh data assemblies, parts, dan trays
     * - Kembalikan seluruh 18.000 master stock ke status 'Tersedia'
     */
    public function clearAllTrays(string $actorUsername = 'admin'): array
    {
        try {
            // Disable foreign key checks for safe truncation
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            DB::table('assemblies')->truncate();
            DB::table('parts')->truncate();
            DB::table('trays')->truncate();
            if (Schema::hasTable('assembly_tests')) {
                DB::table('assembly_tests')->truncate();
            }
            if (Schema::hasTable('assembly_wo_history')) {
                DB::table('assembly_wo_history')->truncate();
            }
            
            // Kembalikan seluruh 18.000 master_stocks ke status 'Tersedia'
            $affected = DB::table('master_stocks')->update([
                'status' => 'Tersedia',
                'allocated_tray_id' => null,
                'allocated_at' => null,
                'used_at' => null,
                'last_reason' => null,
                'last_tray_id' => null,
                'last_operator' => null,
                'is_replaced_from_assy' => 0,
                'updated_at' => now(),
            ]);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Rekam audit trail
        PartAuditLog::create([
            'part_type' => 'ALL',
            'sn' => 'ALL_TRAYS',
            'old_status' => 'Mixed',
            'new_status' => 'Tersedia',
            'action_type' => 'RESET_ALL_TRAYS',
            'actor_username' => $actorUsername,
            'actor_role' => 'admin',
            'reason' => 'Pembersihan database seluruh tray via CMS Admin',
        ]);

        return [
            'ok' => true,
            'message' => 'Seluruh data Tray, Parts, dan Assemblies telah dibersihkan.',
            'stocks_restored' => $affected,
        ];
    }

    /**
     * Alias method for clearAllTrays
     */
    public function clearTraysAndRestoreStock(string $actorUsername = 'admin'): array
    {
        return $this->clearAllTrays($actorUsername);
    }

    /**
     * Pembersihan Audit Downtime:
     * - Menghapus log jeda/istirahat operator pada tabel assembly_downtimes
     */
    public function clearDowntimeAudit(string $actorUsername = 'admin'): array
    {
        $count = DB::table('assembly_downtimes')->count();
        DB::table('assembly_downtimes')->truncate();

        PartAuditLog::create([
            'part_type' => 'ALL',
            'sn' => 'DOWNTIME_LOGS',
            'old_status' => 'COUNT_' . $count,
            'new_status' => 'CLEARED',
            'action_type' => 'CLEAR_DOWNTIME_LOGS',
            'actor_username' => $actorUsername,
            'actor_role' => 'admin',
            'reason' => 'Pembersihan log downtime operator via CMS Admin',
        ]);

        return [
            'ok' => true,
            'message' => "Berhasil membersihkan {$count} data audit downtime operator.",
            'deleted_count' => $count,
        ];
    }

    /**
     * Alias method for clearDowntimeAudit
     */
    public function clearDowntimeLogs(string $actorUsername = 'admin'): array
    {
        return $this->clearDowntimeAudit($actorUsername);
    }

    /**
     * Pembersihan Audit Keseluruhan:
     * - Mengosongkan part_audit_logs, assembly_wo_history, re_register_requests, part_replacement_requests
     */
    public function clearOverallAudit(string $actorUsername = 'admin'): array
    {
        $auditCount = DB::table('part_audit_logs')->count();
        $woCount = DB::table('assembly_wo_history')->count();
        $reregCount = DB::table('re_register_requests')->count();
        $replCount = DB::table('part_replacement_requests')->count();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('part_audit_logs')->truncate();
        DB::table('assembly_wo_history')->truncate();
        DB::table('re_register_requests')->truncate();
        DB::table('part_replacement_requests')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Rekam entry awal setelah dibersihkan
        PartAuditLog::create([
            'part_type' => 'SYSTEM',
            'sn' => 'AUDIT_PURGE',
            'old_status' => 'LOGS_CLEARED',
            'new_status' => 'FRESH_START',
            'action_type' => 'PURGE_OVERALL_AUDIT',
            'actor_username' => $actorUsername,
            'actor_role' => 'admin',
            'reason' => 'Pembersihan total seluruh audit histori via CMS Admin',
        ]);

        return [
            'ok' => true,
            'message' => 'Seluruh audit trail dan riwayat histori berhasil dikosongkan.',
            'purged' => [
                'part_audit_logs' => $auditCount,
                'assembly_wo_history' => $woCount,
                're_register_requests' => $reregCount,
                'part_replacement_requests' => $replCount,
            ]
        ];
    }

    /**
     * Alias method for clearOverallAudit
     */
    public function clearAuditLogs(string $actorUsername = 'admin'): array
    {
        return $this->clearOverallAudit($actorUsername);
    }
}
