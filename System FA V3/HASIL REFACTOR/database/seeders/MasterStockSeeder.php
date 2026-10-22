<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MasterStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sqlPath = database_path('seeders/data/master_stocks.sql');

        if (File::exists($sqlPath)) {
            $this->command?->info("Importing 16,921+ master stock rows from master_stocks.sql...");
            // Execute raw SQL file
            DB::unprepared(File::get($sqlPath));
            $this->command?->info("Master stocks imported successfully.");
            return;
        }

        $this->command?->info("Generating default master stock ranges programmatically...");
        
        // Fallback: Generate master stock ranges
        $batch = [];
        $now = now();

        // 1. Part 1 (SN 001 - 6000)
        for ($i = 1; $i <= 6000; $i++) {
            $sn = ($i < 1000) ? str_pad($i, 3, '0', STR_PAD_LEFT) : (string)$i;
            $batch[] = [
                'sn' => $sn,
                'cs' => null,
                'part_type' => '1',
                'status' => 'Tersedia',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batch) >= 1000) {
                DB::table('master_stocks')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        // 2. Part 12 (60001 - 65000) & (70001 - 75000)
        for ($i = 60001; $i <= 65000; $i++) {
            $batch[] = [
                'sn' => (string)$i,
                'cs' => null,
                'part_type' => '12',
                'status' => 'Tersedia',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batch) >= 1000) {
                DB::table('master_stocks')->insertOrIgnore($batch);
                $batch = [];
            }
        }
        for ($i = 70001; $i <= 75000; $i++) {
            $batch[] = [
                'sn' => (string)$i,
                'cs' => null,
                'part_type' => '12',
                'status' => 'Tersedia',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($batch) >= 1000) {
                DB::table('master_stocks')->insertOrIgnore($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('master_stocks')->insertOrIgnore($batch);
        }

        $this->command?->info("Master stock generation completed.");
    }
}
