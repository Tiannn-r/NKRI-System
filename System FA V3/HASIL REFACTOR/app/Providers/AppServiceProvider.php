<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Models\SlowQueryLog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dynamic Database Selector (Live Production vs Testing Environment)
        $activeDbPath = storage_path('app/active_database.json');
        if (file_exists($activeDbPath)) {
            $dbConfig = json_decode(@file_get_contents($activeDbPath), true);
            $activeDb = $dbConfig['active_db'] ?? null;
            if ($activeDb && in_array($activeDb, ['db_final_assembly', 'db_final_assembly_testing'])) {
                config(['database.connections.mysql.database' => $activeDb]);
            }
        }

        // Realtime Slow Query & Lag Monitor (Threshold: 500ms)
        // Automatically logs any query that causes server lag to the database
        DB::listen(function ($query) {
            if ($query->time > 500) { // Execution time in milliseconds
                try {
                    SlowQueryLog::create([
                        'sql_query' => $query->sql,
                        'bindings' => json_encode($query->bindings),
                        'duration_ms' => $query->time,
                        'endpoint' => request()->path(),
                        'method' => request()->method(),
                        'username' => auth()->user()?->username ?? 'unauthenticated',
                        'ip_address' => request()->ip(),
                    ]);
                } catch (\Throwable $e) {
                    // Suppress error if table does not exist yet during migration
                }
            }
        });
    }
}
