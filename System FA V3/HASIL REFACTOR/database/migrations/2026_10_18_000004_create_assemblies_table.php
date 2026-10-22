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
        Schema::create('assemblies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tray_id')->constrained('trays')->onDelete('cascade');
            $table->integer('set_number');
            $table->string('pic_assembly', 100);
            $table->string('part15_sn', 100);
            $table->string('part12_sn', 100);
            $table->string('part1_sn', 100);
            
            // Assembly Stage Status
            $table->string('status', 30)->default('Good'); // 'Good', 'Hold', 'Scrap', 'Rework'
            $table->text('reason')->nullable();
            $table->text('rework_reason')->nullable();
            $table->string('defective_parts', 255)->nullable(); // e.g. '15', '1', '12'
            $table->string('original_assembly_status', 50)->nullable();
            $table->string('lifecycle_path', 100)->default('FRESH_GOOD');
            
            // TF 1 (Tes Fungsional 1 Checklist Results & Document)
            $table->string('assy_test_status', 50)->default('PENDING'); // 'Good', 'Hold', 'PENDING'
            $table->json('assy_test_checklist')->nullable(); // 17 Checklist parameter results
            $table->text('assy_failure_reasons')->nullable();
            $table->text('notes')->nullable();
            $table->string('assy_tester_username', 100)->nullable();
            $table->timestamp('assy_tested_at')->nullable();
            $table->string('assy_doc_number', 50)->nullable();
            $table->text('assy_status_history')->nullable();

            // TF 2 (QC Stage: Only Good / Not Good Decision)
            $table->string('qc_status', 30)->default('PENDING'); // 'GOOD', 'HOLD_QC', 'REWORK_QC', 'PENDING'
            $table->text('qc_failure_reasons')->nullable();
            $table->text('qc_notes')->nullable();
            $table->string('qc_inspector_username', 100)->nullable();
            $table->string('qc_tested_by', 100)->nullable();
            $table->timestamp('qc_tested_at')->nullable();
            $table->string('qc_doc_number', 50)->nullable();
            $table->text('qc_status_history')->nullable();

            // Warehouse Receipt (Penerimaan Fisik Gudang)
            $table->string('warehouse_receipt_status', 50)->default('PENDING'); // 'PENDING', 'ACCEPTED', 'DECLINED'
            $table->timestamp('warehouse_receipt_at')->nullable();
            $table->string('warehouse_receipt_by', 100)->nullable();
            $table->string('warehouse_doc_number', 50)->nullable();

            // Admin / Warehouse Verification
            $table->string('verified_status', 50)->nullable(); // 'ACC', 'DECLINED', 'PENDING', 'WAITING_REASSEMBLY'
            $table->boolean('is_declined')->default(false);
            $table->text('declined_reason')->nullable();
            $table->boolean('is_reregistered')->default(false);
            $table->string('rereg_pending_operator', 100)->nullable();
            $table->string('rereg_wo_suffix', 50)->nullable();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['tray_id', 'set_number'], 'uq_tray_set');
            $table->index('tray_id', 'idx_assemblies_tray');
            $table->index('status', 'idx_assemblies_status');
            $table->index('assy_test_status', 'idx_assemblies_assy_test_status');
            $table->index('qc_status', 'idx_assemblies_qc_status');
            $table->index('warehouse_receipt_status', 'idx_assemblies_wh_status');
            $table->index(['part15_sn', 'part1_sn', 'part12_sn'], 'idx_assemblies_parts');
        });

        // Assembly Tests (Detailed test history per stage: ASSY or QC)
        Schema::create('assembly_tests', function (Blueprint $table) {
            $table->id();
            $table->string('stage', 20); // 'ASSY', 'QC'
            $table->string('doc_number', 50)->nullable();
            $table->foreignId('tray_id')->nullable()->constrained('trays')->onDelete('cascade');
            $table->string('tray_code', 50)->nullable();
            $table->integer('set_number')->nullable();
            $table->foreignId('assembly_id')->nullable()->constrained('assemblies')->onDelete('cascade');
            $table->string('p9_sn', 100)->nullable();
            $table->string('part15_sn', 100)->nullable();
            $table->string('part12_sn', 100)->nullable();
            $table->string('part1_sn', 100)->nullable();
            $table->string('tester_username', 100)->nullable();
            $table->string('tester_role', 50)->nullable();
            $table->json('test_checklist')->nullable();
            $table->string('status', 50); // 'Good', 'Hold', etc.
            $table->text('failure_reasons')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('admin_approved_at')->nullable();
            $table->string('admin_approved_by', 100)->nullable();
            $table->string('admin_action', 50)->nullable();
            $table->text('status_history')->nullable();
            $table->string('lifecycle_path', 100)->nullable();
            $table->timestamps();

            $table->index('stage', 'idx_assy_tests_stage');
            $table->index('doc_number', 'idx_assy_tests_doc');
            $table->index('assembly_id', 'idx_assy_tests_assembly');
            $table->index('part15_sn', 'idx_assy_tests_part15');
            $table->index('created_at', 'idx_assy_tests_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assembly_tests');
        Schema::dropIfExists('assemblies');
    }
};
