<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assemblies', function (Blueprint $table) {
            // Index komposit untuk query status, QC, dan filter admin cepat
            if (!Schema::hasIndex('assemblies', 'idx_assemblies_status_qc')) {
                $table->index(['status', 'qc_status', 'assy_test_status'], 'idx_assemblies_status_qc');
            }
            if (!Schema::hasIndex('assemblies', 'idx_assemblies_admin_pending')) {
                $table->index(['is_declined', 'verified_status', 'status'], 'idx_assemblies_admin_pending');
            }
        });

        Schema::table('trays', function (Blueprint $table) {
            if (!Schema::hasIndex('trays', 'idx_trays_session_verified')) {
                $table->index(['session_status', 'verified_by_gudang'], 'idx_trays_session_verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assemblies', function (Blueprint $table) {
            $table->dropIndex('idx_assemblies_status_qc');
            $table->dropIndex('idx_assemblies_admin_pending');
        });

        Schema::table('trays', function (Blueprint $table) {
            $table->dropIndex('idx_trays_session_verified');
        });
    }
};
