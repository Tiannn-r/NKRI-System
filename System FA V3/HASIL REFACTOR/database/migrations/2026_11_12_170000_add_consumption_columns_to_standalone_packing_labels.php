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
        if (Schema::hasTable('standalone_packing_labels')) {
            Schema::table('standalone_packing_labels', function (Blueprint $table) {
                if (!Schema::hasColumn('standalone_packing_labels', 'delivery_order_id')) {
                    $table->unsignedBigInteger('delivery_order_id')->nullable()->after('pl_mode');
                    $table->index('delivery_order_id', 'idx_spl_do_id');
                }
                if (!Schema::hasColumn('standalone_packing_labels', 'is_consumed')) {
                    $table->boolean('is_consumed')->default(0)->after('delivery_order_id');
                    $table->index('is_consumed', 'idx_spl_is_consumed');
                }
                if (!Schema::hasColumn('standalone_packing_labels', 'consumed_at')) {
                    $table->timestamp('consumed_at')->nullable()->after('is_consumed');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('standalone_packing_labels')) {
            Schema::table('standalone_packing_labels', function (Blueprint $table) {
                if (Schema::hasColumn('standalone_packing_labels', 'consumed_at')) {
                    $table->dropColumn('consumed_at');
                }
                if (Schema::hasColumn('standalone_packing_labels', 'is_consumed')) {
                    $table->dropColumn('is_consumed');
                }
                if (Schema::hasColumn('standalone_packing_labels', 'delivery_order_id')) {
                    $table->dropColumn('delivery_order_id');
                }
            });
        }
    }
};

