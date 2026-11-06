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
            $table->string('pic_assembly', 100)->nullable()->default('')->change();
            $table->string('part15_sn', 100)->nullable()->default('')->change();
            $table->string('part12_sn', 100)->nullable()->default('')->change();
            $table->string('part1_sn', 100)->nullable()->default('')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assemblies', function (Blueprint $table) {
            $table->string('pic_assembly', 100)->nullable(false)->change();
            $table->string('part15_sn', 100)->nullable(false)->change();
            $table->string('part12_sn', 100)->nullable(false)->change();
            $table->string('part1_sn', 100)->nullable(false)->change();
        });
    }
};

