<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            ['role_id' => 'R01', 'name' => 'Admin'], // On Check
            ['role_id' => 'R02', 'name' => 'Staf Gudang'], // Tinggal Pengadaan
            ['role_id' => 'R03', 'name' => 'Staf RnD'], // Aman
            ['role_id' => 'R04', 'name' => 'Staf Pengadaan'],
            ['role_id' => 'R05', 'name' => 'Staf Penjualan'], // Aman
            ['role_id' => 'R06', 'name' => 'Staf Keuangan'],
            ['role_id' => 'R07', 'name' => 'Manajer Gudang'], // Tinggal Pengadaan
            ['role_id' => 'R08', 'name' => 'Manajer RnD'], // Cek pengadaan
            ['role_id' => 'R09', 'name' => 'Manajer Pengadaan'],
            ['role_id' => 'R10', 'name' => 'Manajer Keuangan'],
        ]);
    }
}
