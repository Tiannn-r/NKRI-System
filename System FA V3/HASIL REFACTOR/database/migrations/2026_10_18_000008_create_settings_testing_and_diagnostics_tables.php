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
        // 1. DO Settings (Default values for Kemhan, PPIC, prefixes, Cloudinary, etc.)
        Schema::create('do_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('PT Nanggala Kencana Rekatama Indonesia');
            $table->string('company_logo')->default('nkri-logo.png');
            $table->text('head_office')->nullable();
            $table->text('factory_address')->nullable();
            $table->string('contact_email')->default('info@nanggala.co.id');
            $table->string('default_customer_name')->default('KEMHAN RI');
            $table->text('default_customer_address')->nullable();
            $table->string('default_customer_phone')->nullable();
            $table->string('default_customer_fax')->nullable();
            $table->string('default_contract_number')->default('TRAK/2509/APBN/XI/2025/KEMHAN');
            $table->string('default_program_name')->default('Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025');
            $table->string('default_product_name')->default('Nanggala P9');
            $table->string('default_issuer_name')->default('Rezka Rachdana');
            $table->string('default_issuer_title')->default('Staf');
            $table->string('default_issuer_department')->default('PPIC');
            $table->string('do_prefix', 50)->default('/SJ/PPIC/');
            $table->string('pl_prefix', 50)->default('/PL/PPIC/');
            $table->string('crate_prefix', 50)->default('2609-');
            $table->integer('crate_capacity')->default(20);
            $table->integer('box_capacity')->default(10);
            $table->integer('do_sequence_offset')->default(0);
            $table->string('do_offset_month', 10)->nullable();
            
            // Cloudinary integration credentials editable in /admin
            $table->string('cloudinary_cloud_name')->nullable();
            $table->string('cloudinary_api_key')->nullable();
            $table->string('cloudinary_api_secret')->nullable();
            $table->timestamps();
        });

        // 2. Testing Points (17 Parameter Checklist Pengujian TF 1)
        Schema::create('testing_points', function (Blueprint $table) {
            $table->id();
            $table->integer('test_num')->unique(); // 1 to 17
            $table->string('category', 100)->default('Umum / Fungsional');
            $table->text('instruction');
            $table->text('output');
            $table->json('images')->nullable();
            $table->timestamps();

            $table->index('test_num', 'idx_test_num');
        });

        // 3. System Error Logs (Realtime Backend Error Monitoring for /admin)
        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('ERROR'); // 'ERROR', 'CRITICAL', 'WARNING'
            $table->text('message');
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->longText('trace')->nullable();
            $table->string('endpoint')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('username', 100)->default('system');
            $table->string('ip_address', 50)->nullable();
            $table->timestamps();

            $table->index('created_at', 'idx_err_created');
            $table->index('level', 'idx_err_level');
        });

        // 4. Slow Query Logs (Lag Monitor for /admin)
        Schema::create('slow_query_logs', function (Blueprint $table) {
            $table->id();
            $table->longText('sql_query');
            $table->text('bindings')->nullable();
            $table->float('duration_ms'); // e.g. 520.45 ms
            $table->string('endpoint')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('username', 100)->default('system');
            $table->string('ip_address', 50)->nullable();
            $table->timestamps();

            $table->index('created_at', 'idx_slow_created');
            $table->index('duration_ms', 'idx_slow_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slow_query_logs');
        Schema::dropIfExists('system_error_logs');
        Schema::dropIfExists('testing_points');
        Schema::dropIfExists('do_settings');
    }
};
