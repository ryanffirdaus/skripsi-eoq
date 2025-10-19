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
            ['role_id' => 'R01', 'name' => 'Admin'],
            ['role_id' => 'R02', 'name' => 'Staf Gudang'],
            ['role_id' => 'R03', 'name' => 'Staf RnD'],
            ['role_id' => 'R04', 'name' => 'Staf Pengadaan'],
            ['role_id' => 'R05', 'name' => 'Staf Penjualan'],
            ['role_id' => 'R06', 'name' => 'Staf Keuangan'],
            ['role_id' => 'R07', 'name' => 'Manajer Gudang'],
            ['role_id' => 'R08', 'name' => 'Manajer RnD'],
            ['role_id' => 'R09', 'name' => 'Manajer Pengadaan'],
            ['role_id' => 'R10', 'name' => 'Manajer Keuangan'],
        ]);
    }
}
