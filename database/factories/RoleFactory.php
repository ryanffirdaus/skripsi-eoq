<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;

        $roles = [
            ['role_id' => 'RL001', 'nama' => 'Admin'],
            ['role_id' => 'RL002', 'nama' => 'Staf Gudang'],
            ['role_id' => 'RL003', 'nama' => 'Staf RnD'],
            ['role_id' => 'RL004', 'nama' => 'Staf Pengadaan'],
            ['role_id' => 'RL005', 'nama' => 'Staf Penjualan'],
            ['role_id' => 'RL006', 'nama' => 'Staf Keuangan'],
            ['role_id' => 'RL007', 'nama' => 'Manajer Gudang'],
            ['role_id' => 'RL008', 'nama' => 'Manajer RnD'],
            ['role_id' => 'RL009', 'nama' => 'Manajer Pengadaan'],
            ['role_id' => 'RL010', 'nama' => 'Manajer Keuangan'],
        ];

        $role = $roles[$counter - 1] ?? $roles[0];
        $counter++;

        return [
            'role_id' => $role['role_id'],
            'nama' => $role['nama'],
        ];
    }
}
