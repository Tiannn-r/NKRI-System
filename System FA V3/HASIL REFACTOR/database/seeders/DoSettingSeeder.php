<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('do_settings')->updateOrInsert(
            ['id' => 1],
            [
                'company_name' => 'PT Nanggala Kencana Rekatama Indonesia',
                'company_logo' => 'nkri-logo.png',
                'head_office' => "Jalan Mendawai I No. 11 Kramat Pela\nJakarta Selatan, 12130",
                'factory_address' => "Jalan Gempol No. 9, Mekar Mulya\nBandung, 40292",
                'contact_email' => 'info@nanggala.co.id',
                'default_customer_name' => 'KEMHAN RI',
                'default_customer_address' => "BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110",
                'default_customer_phone' => '(021) 3840889',
                'default_customer_fax' => '(021) 3840889',
                'default_contract_number' => 'TRAK/2509/APBN/XI/2025/KEMHAN',
                'default_program_name' => 'Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025',
                'default_product_name' => 'Nanggala P9',
                'default_issuer_name' => 'Rezka Rachdana',
                'default_issuer_title' => 'Staf',
                'default_issuer_department' => 'PPIC',
                'do_prefix' => '/SJ/PPIC/',
                'pl_prefix' => '/PL/PPIC/',
                'crate_prefix' => '2609-',
                'crate_capacity' => 20,
                'box_capacity' => 10,
                'do_sequence_offset' => 0,
                'do_offset_month' => '',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('customers')->updateOrInsert(
            ['name' => 'KEMHAN RI'],
            [
                'address' => "BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110",
                'phone' => '(021) 3840889',
                'fax' => '(021) 3840889',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
