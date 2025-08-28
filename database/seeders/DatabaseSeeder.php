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
        // Create 5 random users
        User::factory(5)->create();

        // Create a test admin user
        User::factory()->create([
            'user_id' => 'US999', // Special ID for admin
            'nama_lengkap' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
    }
}
