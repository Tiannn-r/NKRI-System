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
        // 1. Delivery Orders Table
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number', 50)->unique(); // e.g. '001/SJ/PPIC/10/2026'
            $table->string('pl_number', 50)->nullable(); // e.g. '001/PL/PPIC/10/2026'
            $table->date('do_date');
            $table->string('customer_name');
            $table->text('customer_address')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_fax', 50)->nullable();
            $table->string('package_type', 50)->default('peti'); // 'peti', 'box', 'unit'
            $table->integer('total_crates')->default(0);
            $table->integer('total_boxes')->default(0);
            $table->integer('total_units')->default(0);
            $table->string('status', 30)->default('ISSUED'); // 'DRAFT', 'ISSUED', 'CANCELLED'
            $table->string('created_by', 100);
            $table->string('created_by_name', 100)->nullable();
            $table->text('notes')->nullable();
            
            // Cloudinary storage URLs
            $table->text('cloud_pdf_url')->nullable();
            $table->string('cloud_public_id')->nullable();
            $table->timestamp('cloud_uploaded_at')->nullable();
            $table->timestamps();

            $table->index('do_number', 'idx_do_number');
            $table->index('do_date', 'idx_do_date');
            $table->index('status', 'idx_do_status');
        });

        // 2. Packing Lists (Crates / Peti)
        Schema::create('packing_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->onDelete('cascade');
            $table->integer('crate_number');
            $table->string('crate_id', 50); // e.g. '2610-1001'
            $table->string('pl_number', 50)->nullable();
            $table->integer('total_quantity')->default(20);
            $table->timestamps();

            $table->index('delivery_order_id', 'idx_pl_do_id');
            $table->index('crate_id', 'idx_pl_crate_id');
        });

        // 3. Packing List Boxes
        Schema::create('packing_list_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_list_id')->constrained('packing_lists')->onDelete('cascade');
            $table->integer('box_number'); // 1 or 2
            $table->string('box_id', 50); // e.g. '2610-1001-01'
            $table->integer('total_quantity')->default(10);
            $table->timestamps();

            $table->index('packing_list_id', 'idx_plb_pl_id');
            $table->index('box_id', 'idx_plb_box_id');
        });

        // 4. Packing List Items (Units)
        Schema::create('packing_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->onDelete('cascade');
            $table->foreignId('box_id')->nullable()->constrained('packing_list_boxes')->onDelete('cascade');
            $table->integer('slot_number'); // 1 to 10
            $table->string('serial_number', 100);
            $table->timestamps();

            $table->index('delivery_order_id', 'idx_pli_do_id');
            $table->index('serial_number', 'idx_pli_sn');
        });

        // 5. Standalone Packing Labels
        Schema::create('standalone_packing_labels', function (Blueprint $table) {
            $table->id();
            $table->string('pl_number', 50);
            $table->date('label_date');
            $table->string('package_type', 50)->default('peti');
            $table->json('crates_json');
            $table->integer('total_quantity')->default(0);
            $table->string('created_by', 100);
            $table->timestamps();

            $table->index('pl_number', 'idx_spl_number');
        });

        // 6. Customers Table
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_list_items');
        Schema::dropIfExists('packing_list_boxes');
        Schema::dropIfExists('packing_lists');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('standalone_packing_labels');
        Schema::dropIfExists('customers');
    }
};
