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
        if (Schema::hasTable('do_settings')) {
            Schema::table('do_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('do_settings', 'current_batch_number')) {
                    $table->integer('current_batch_number')->default(1)->after('default_product_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('do_settings')) {
            Schema::table('do_settings', function (Blueprint $table) {
                if (Schema::hasColumn('do_settings', 'current_batch_number')) {
                    $table->dropColumn('current_batch_number');
                }
            });
        }
    }
};

