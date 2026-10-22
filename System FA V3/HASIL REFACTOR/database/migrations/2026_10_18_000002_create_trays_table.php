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
        Schema::create('trays', function (Blueprint $table) {
            $table->id();
            $table->string('tray_code', 50)->unique(); // e.g., 'WH/WO/000001' or 'WH/ASSY/000001'
            $table->string('pic_warehouse', 100);
            $table->string('assigned_operator', 100)->nullable();
            $table->string('source_document_mo', 100)->nullable();
            $table->string('product_type', 100)->default('Nanggala P9');
            $table->string('status', 30)->default('active'); // 'active', 'finished', 'returned'
            $table->string('session_status', 50)->default('active');
            $table->boolean('verified_by_gudang')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by_user', 100)->nullable();
            $table->boolean('return_verified')->default(false);
            $table->timestamp('return_verified_at')->nullable();
            $table->timestamps();

            $table->index('tray_code', 'idx_trays_code');
            $table->index('created_at', 'idx_trays_created');
            $table->index('status', 'idx_trays_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trays');
    }
};
