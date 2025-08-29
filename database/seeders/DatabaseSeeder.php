<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First create roles to avoid foreign key constraint violations
        $this->call(RoleSeeder::class);

        // Create users for each role from R01 to R11
        for ($i = 1; $i <= 11; $i++) {
            $roleId = 'R' . str_pad($i, 2, '0', STR_PAD_LEFT); // Creates R01, R02, ..., R11

            User::factory()->create([
                'user_id' => "US" . str_pad($i, 3, '0', STR_PAD_LEFT), // Creates US001, US002, ..., US011
                'nama_lengkap' => "User Role $roleId",
                'email' => "role$i@example.com",
                'role_id' => $roleId
            ]);
        }
    }
}
