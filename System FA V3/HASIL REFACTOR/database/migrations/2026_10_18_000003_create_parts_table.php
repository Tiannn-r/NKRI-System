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
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tray_id')->constrained('trays')->onDelete('cascade');
            $table->string('part_type', 10); // '1', '12', '15'
            $table->string('sn', 100);
            $table->string('status', 30)->default('Supplied'); // 'Supplied', 'Used', 'Returned', 'Declined'
            $table->timestamps();

            $table->index('tray_id', 'idx_parts_tray_id');
            $table->index(['part_type', 'sn'], 'idx_parts_type_sn');
            $table->index('sn', 'idx_parts_sn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
