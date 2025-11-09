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
            ['role_id' => 'R01', 'nama' => 'Admin'], // On Check
            ['role_id' => 'R02', 'nama' => 'Staf Gudang'], // Tinggal Pengadaan
            ['role_id' => 'R03', 'nama' => 'Staf RnD'], // Aman
            ['role_id' => 'R04', 'nama' => 'Staf Pengadaan'],
            ['role_id' => 'R05', 'nama' => 'Staf Penjualan'], // Aman
            ['role_id' => 'R06', 'nama' => 'Staf Keuangan'],
            ['role_id' => 'R07', 'nama' => 'Manajer Gudang'], // Tinggal Pengadaan
            ['role_id' => 'R08', 'nama' => 'Manajer RnD'], // Cek pengadaan
            ['role_id' => 'R09', 'nama' => 'Manajer Pengadaan'],
            ['role_id' => 'R10', 'nama' => 'Manajer Keuangan'],
        ]);
    }
}
