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
        // 1. Permohonan Daftar Ulang Part
        Schema::create('re_register_requests', function (Blueprint $table) {
            $table->id();
            $table->string('part_type', 10);
            $table->string('sn', 100);
            $table->string('current_status', 50);
            $table->string('target_status', 50)->default('Tersedia');
            $table->string('requestor_username', 100);
            $table->string('requestor_role', 50);
            $table->text('reason')->nullable();
            $table->string('status', 50)->default('PENDING'); // 'PENDING', 'APPROVED', 'REJECTED'
            $table->string('admin_username', 100)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_rereg_status');
            $table->index('sn', 'idx_rereg_sn');
        });

        // 2. Permohonan Replacement Part Rusak
        Schema::create('part_replacement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained('assemblies')->onDelete('cascade');
            $table->foreignId('tray_id')->constrained('trays')->onDelete('cascade');
            $table->integer('set_number');
            $table->json('replacements_json'); // array of [{part_type, old_sn, new_sn, reason}]
            $table->string('requestor_username', 100);
            $table->string('status', 50)->default('PENDING'); // 'PENDING', 'APPROVED', 'REJECTED'
            $table->string('admin_username', 100)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index('assembly_id', 'idx_repl_assy_id');
            $table->index('status', 'idx_repl_status');
        });

        // 3. Audit Downtime & Jeda Kerja Operator
        Schema::create('assembly_downtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tray_id')->nullable()->constrained('trays')->onDelete('cascade');
            $table->string('tray_code', 50);
            $table->integer('set_number')->nullable();
            $table->string('operator', 100);
            $table->text('reason');
            $table->timestamp('paused_at');
            $table->timestamp('resumed_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->timestamps();

            $table->index('tray_id', 'idx_down_tray');
            $table->index('operator', 'idx_down_operator');
            $table->index('created_at', 'idx_down_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_register_requests');
        Schema::dropIfExists('part_replacement_requests');
        Schema::dropIfExists('assembly_downtimes');
    }
};
