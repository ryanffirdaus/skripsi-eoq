<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pemasok;

class PemasokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main pemasoks
        $pemasoks = [
            [
                'pemasok_id' => 'PM001',
                'nama_pemasok' => 'PT Bahan Baku Utama',
                'narahubung' => 'Andi Setiawan',
                'email' => 'andi@bahan-utama.com',
                'nomor_telepon' => '021-1234567',
                'alamat' => 'Jl. Industri Raya No. 123, Jakarta',
                'catatan' => 'pemasok utama untuk bahan baku kimia',
                'dibuat_oleh' => 'US001',
            ],
            [
                'pemasok_id' => 'PM002',
                'nama_pemasok' => 'CV Sumber Plastik',
                'narahubung' => 'Budi Raharja',
                'email' => 'budi@sumber-plastik.com',
                'nomor_telepon' => '031-2345678',
                'alamat' => 'Jl. Raya Surabaya No. 456, Surabaya',
                'catatan' => 'Spesialis plastik dan packaging',
                'dibuat_oleh' => 'US001',
            ],
            [
                'pemasok_id' => 'PM003',
                'nama_pemasok' => 'PT Logam Berkualitas',
                'narahubung' => 'Sari Wijaya',
                'email' => 'sari@logam-berkualitas.com',
                'nomor_telepon' => '022-3456789',
                'alamat' => 'Jl. Logam Mulia No. 789, Bandung',
                'catatan' => 'pemasok bahan logam dan komponen',
                'dibuat_oleh' => 'US001',
            ],
            [
                'pemasok_id' => 'PM004',
                'nama_pemasok' => 'UD Elektronik Jaya',
                'narahubung' => 'Dedi Kurniawan',
                'email' => 'dedi@elektronik-jaya.com',
                'nomor_telepon' => '024-4567890',
                'alamat' => 'Jl. Elektronik No. 101, Semarang',
                'catatan' => 'Komponen elektronik dan suku cadang',
                'dibuat_oleh' => 'US001',
            ],
            [
                'pemasok_id' => 'PM005',
                'nama_pemasok' => 'PT Kemasan Modern',
                'narahubung' => 'Lisa Handayani',
                'email' => 'lisa@kemasan-modern.com',
                'nomor_telepon' => '061-5678901',
                'alamat' => 'Jl. Kemasan Indah No. 202, Medan',
                'catatan' => 'Spesialis kemasan dan packaging premium',
                'dibuat_oleh' => 'US001',
            ]
        ];

        foreach ($pemasoks as $pemasok) {
            Pemasok::create($pemasok);
        }

        // Create additional random pemasoks
        Pemasok::factory()
            ->count(10)
            ->active()
            ->create();
    }
}
