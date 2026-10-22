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
        Schema::create('part_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('part_type', 10); // '1', '12', '15'
            $table->string('sn', 100);
            $table->string('old_status', 50);
            $table->string('new_status', 50);
            $table->string('action_type', 50); // 'OVERRIDE_STATUS', 'RE_REGISTER', 'REPLACEMENT'
            $table->string('actor_username', 100);
            $table->string('actor_role', 50);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('created_at', 'idx_audit_created');
            $table->index('sn', 'idx_audit_sn');
        });

        Schema::create('assembly_wo_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tray_id')->constrained('trays')->onDelete('cascade');
            $table->string('tray_code', 50)->nullable();
            $table->integer('set_number');
            $table->string('pic_assembly', 100)->nullable();
            $table->string('part15_sn', 100)->nullable();
            $table->string('part12_sn', 100)->nullable();
            $table->string('part1_sn', 100)->nullable();
            $table->string('status', 30)->default('Good');
            $table->text('reason')->nullable();
            $table->string('defective_parts', 255)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('source', 50)->default('ASSEMBLY');
            $table->timestamps();

            $table->index('tray_id', 'idx_wo_hist_tray');
            $table->index(['part15_sn', 'part1_sn', 'part12_sn'], 'idx_wo_hist_parts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_audit_logs');
        Schema::dropIfExists('assembly_wo_history');
    }
};
