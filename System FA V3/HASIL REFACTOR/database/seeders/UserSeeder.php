<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'nik' => 'ADMIN01',
                'full_name' => 'Administrator Sistem',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'department' => 'Administrator',
                'permissions' => json_encode(['all' => 2]),
            ],
            [
                'nik' => '262052',
                'full_name' => 'Miranti',
                'username' => 'Miranti',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'department' => 'Administrator',
                'permissions' => json_encode(['all' => 2]),
            ],
            [
                'nik' => '261044',
                'full_name' => 'Yasin Alibi',
                'username' => 'Yasin',
                'password' => Hash::make('yasin123'),
                'role' => 'admin',
                'department' => 'Administrator',
                'permissions' => json_encode(['all' => 2]),
            ],
            [
                'nik' => '262067',
                'full_name' => 'Heni',
                'username' => 'Heni',
                'password' => Hash::make('Ppic00'),
                'role' => 'gudang',
                'department' => 'Warehouse',
                'permissions' => json_encode(['warehouse' => 2, 'traveler' => 2, 'delivery_order' => 2]),
            ],
            [
                'nik' => '262041',
                'full_name' => 'Rezka',
                'username' => 'Rezka',
                'password' => Hash::make('Whs00'),
                'role' => 'gudang',
                'department' => 'Warehouse',
                'permissions' => json_encode(['warehouse' => 2, 'traveler' => 2, 'delivery_order' => 2]),
            ],
            [
                'nik' => '001',
                'full_name' => 'Warehouse QC Point 2',
                'username' => 'Warehouse',
                'password' => Hash::make('whs248'),
                'role' => 'qc',
                'department' => 'QC',
                'permissions' => json_encode(['qc' => 2, 'traveler' => 2, 'report' => 2]),
            ],
            [
                'nik' => '262048',
                'full_name' => 'Arini',
                'username' => 'Arini',
                'password' => Hash::make('Assy02'),
                'role' => 'assembly',
                'department' => 'Assembly',
                'permissions' => json_encode(['assembly' => 2, 'assy_testing' => 2]),
            ],
            [
                'nik' => '262049',
                'full_name' => 'Meisi',
                'username' => 'Meisi',
                'password' => Hash::make('Assy03'),
                'role' => 'assembly',
                'department' => 'Assembly',
                'permissions' => json_encode(['assembly' => 2, 'assy_testing' => 2]),
            ],
            [
                'nik' => '262051',
                'full_name' => 'Sopi',
                'username' => 'Sopi',
                'password' => Hash::make('Assy04'),
                'role' => 'assembly',
                'department' => 'Assembly',
                'permissions' => json_encode(['assembly' => 2, 'assy_testing' => 2]),
            ],
            [
                'nik' => '262057',
                'full_name' => 'Dila',
                'username' => 'Dila',
                'password' => Hash::make('Assy05'),
                'role' => 'assembly',
                'department' => 'Assembly',
                'permissions' => json_encode(['assembly' => 2, 'assy_testing' => 2]),
            ],
            [
                'nik' => '262058',
                'full_name' => 'Irsa',
                'username' => 'Irsa',
                'password' => Hash::make('Assy06'),
                'role' => 'assembly',
                'department' => 'Assembly',
                'permissions' => json_encode(['assembly' => 2, 'assy_testing' => 2]),
            ],
            [
                'nik' => '262059',
                'full_name' => 'Keisha',
                'username' => 'Keisha',
                'password' => Hash::make('Assy07'),
                'role' => 'assembly',
                'department' => 'Assembly',
                'permissions' => json_encode(['assembly' => 2, 'assy_testing' => 2]),
            ],
            [
                'nik' => '262060',
                'full_name' => 'Muthia',
                'username' => 'Muthia',
                'password' => Hash::make('Assy08'),
                'role' => 'assembly',
                'department' => 'Assembly',
                'permissions' => json_encode(['assembly' => 2, 'assy_testing' => 2]),
            ],
            [
                'nik' => '262061',
                'full_name' => 'Reva',
                'username' => 'Reva',
                'password' => Hash::make('Assy10'),
                'role' => 'assembly',
                'department' => 'Assembly',
                'permissions' => json_encode(['assembly' => 2, 'assy_testing' => 2]),
            ],
        ];

        foreach ($users as $u) {
            DB::table('users')->updateOrInsert(
                ['username' => $u['username']],
                [
                    'nik' => $u['nik'],
                    'full_name' => $u['full_name'],
                    'password' => $u['password'],
                    'role' => $u['role'],
                    'department' => $u['department'],
                    'permissions' => $u['permissions'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
