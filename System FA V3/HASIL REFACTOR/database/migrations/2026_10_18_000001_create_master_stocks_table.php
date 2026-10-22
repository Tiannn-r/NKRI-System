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
        Schema::create('master_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('sn', 100);
            $table->string('cs', 100)->nullable();
            $table->string('part_type', 10); // '1', '12', '15'
            $table->string('status', 30)->default('Tersedia'); // 'Tersedia', 'Supplied', 'Done', 'Hold', 'Scrap', 'Rework'
            $table->unsignedBigInteger('allocated_tray_id')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->text('last_reason')->nullable();
            $table->unsignedBigInteger('last_tray_id')->nullable();
            $table->string('last_operator', 100)->nullable();
            $table->boolean('is_replaced_from_assy')->default(false);
            $table->timestamps();

            // Constraints and High Performance Indexes for Un-capped queries
            $table->unique(['sn', 'part_type'], 'uq_master_stocks_sn_type');
            $table->index(['part_type', 'status', 'allocated_tray_id'], 'idx_type_status_tray');
            $table->index('sn', 'idx_sn');
            $table->index('allocated_tray_id', 'idx_tray');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_stocks');
    }
};
