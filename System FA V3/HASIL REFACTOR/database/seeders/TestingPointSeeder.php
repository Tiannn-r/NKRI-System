<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingPointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $points = [
            [
                'test_num' => 1,
                'category' => 'Cek Visual',
                'instruction' => '1. Pengecekan visual secara menyeluruh mulai dari sisi atas-bawah, samping kanan-kiri, depan belakang',
                'output' => "- Penebalan pada hasil pengecatan\n- Retak pada area yang berpasangan dengan part berbahan metal",
                'images' => '[]',
            ],
            [
                'test_num' => 2,
                'category' => 'Cek Rigiditas',
                'instruction' => '1. Cek upper dan lower dengan cara digoyangkan secara horizontal',
                'output' => 'Tidak mengeluarkan suara akibat hasil rakitan longgar',
                'images' => '[]',
            ],
            [
                'test_num' => 3,
                'category' => 'Cek Rigiditas',
                'instruction' => '2. Cek front sight dan rear sight dengan cara digoyangkan',
                'output' => 'Tidak boleh bergeser',
                'images' => '[]',
            ],
            [
                'test_num' => 4,
                'category' => 'Cek Mekanik',
                'instruction' => '1. Uji pelepas magasen sebanyak 3x',
                'output' => "Magasen tidak jatuh / lepas setelah dikunci\nMagasen dapat masuk - keluar dengan baik ketika pelepas magasen di tekan",
                'images' => '[]',
            ],
            [
                'test_num' => 5,
                'category' => 'Cek Mekanik',
                'instruction' => '2. Uji kokang sebanyak 3x',
                'output' => 'Tidak menyangkut ataupun tersendat ketika di kokang dan dapat kembali dengan lancar',
                'images' => '[]',
            ],
            [
                'test_num' => 6,
                'category' => 'Cek Mekanik',
                'instruction' => '3. Uji picu dengan cara menarik picu untuk mengaktifkan pemukul',
                'output' => 'Pemukul dapat memukul pena pukul',
                'images' => '[]',
            ],
            [
                'test_num' => 7,
                'category' => 'Cek Mekanik',
                'instruction' => '4. Uji pemukul dengan cara menarik pemukul kemudian tarik picu',
                'output' => 'Pemukul dapat memukul pena pukul',
                'images' => '[]',
            ],
            [
                'test_num' => 8,
                'category' => 'Cek Mekanik',
                'instruction' => '5. Uji pemukul dengan cara menarik pemukul 1/2 kemudian tarik picu',
                'output' => "Pemukul dapat terkunci pada kondisi 1/2 tegang\nPemukul dapat memukul pena pukul",
                'images' => '[]',
            ],
            [
                'test_num' => 9,
                'category' => 'Cek Mekanik',
                'instruction' => '6. Uji pemukul dengan cara menarik penuh pemukul kemudian tarik picu',
                'output' => 'Pemukul dapat memukul pena pukul',
                'images' => '[]',
            ],
            [
                'test_num' => 10,
                'category' => 'Cek Mekanik',
                'instruction' => '7. Uji pemukul dengan cara menarik penuh pemukul kemudian dorong kedepan',
                'output' => 'Pemukul tidak boleh memukul pena pukul',
                'images' => '[]',
            ],
            [
                'test_num' => 11,
                'category' => 'Cek Mekanik',
                'instruction' => '7.1. Dorong keatas tuas pengaman kemudian tarik picu',
                'output' => 'Pemukul tidak boleh memukul pena pukul',
                'images' => '[]',
            ],
            [
                'test_num' => 12,
                'category' => 'Cek Mekanik',
                'instruction' => '7.1.1 Dorong kebawah tuas pengaman (untuk menormalkan kembali)',
                'output' => 'Tuas pengaman kembali ke posisi normal',
                'images' => '[]',
            ],
            [
                'test_num' => 13,
                'category' => 'Cek Mekanik',
                'instruction' => '7.2 Tarik pemukul secara penuh kemudian kokang penuh lalu aktifkan kunci eretan',
                'output' => 'Eretan dapat terkunci dengan baik dan tidak bergerak maju kedepan',
                'images' => '[]',
            ],
            [
                'test_num' => 14,
                'category' => 'Cek Mekanik',
                'instruction' => '7.3 Turunkan kunci eretan',
                'output' => 'Eretan dapat maju kedepan dengan baik dan tidak tersendat',
                'images' => '[]',
            ],
            [
                'test_num' => 15,
                'category' => 'Cek Mekanik',
                'instruction' => '8. Kokang penuh kemudian aktifkan kunci eretan dan putar keatas tuas pembongkar',
                'output' => 'Tuas pembongkar dapat diputar keatas',
                'images' => '[]',
            ],
            [
                'test_num' => 16,
                'category' => 'Cek Mekanik',
                'instruction' => '8.1 Putar kembali kebawah tuas pembongkar kemudian non-aktifkan kunci eretan',
                'output' => 'Tuas pembongkar dan kunci eretan dapat dikembalikan ke posisi awal',
                'images' => '[]',
            ],
            [
                'test_num' => 17,
                'category' => 'Cek Mekanik',
                'instruction' => '9. Tarik pemukul secara penuh, kemudian dorong laras dari depan ke belakang',
                'output' => 'Laras tidak dapat bergerak mundur ke belakang',
                'images' => '[]',
            ],
        ];

        foreach ($points as $p) {
            DB::table('testing_points')->updateOrInsert(
                ['test_num' => $p['test_num']],
                [
                    'category' => $p['category'],
                    'instruction' => $p['instruction'],
                    'output' => $p['output'],
                    'images' => $p['images'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
