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
                'pemasok_id' => 'PMS0000001',
                'nama_pemasok' => 'PT Bahan Baku Utama',
                'narahubung' => 'Andi Setiawan',
                'email' => 'andi@bahan-utama.com',
                'telepon' => '021-1234567',
                'alamat' => 'Jl. Industri Raya No. 123',
                'kota' => 'Jakarta',
                'provinsi' => 'DKI Jakarta',
                'kode_pos' => '12345',
                'status' => 'active',
                'catatan' => 'pemasok utama untuk bahan baku kimia',
                'created_by' => 'US001',
            ],
            [
                'pemasok_id' => 'PMS0000002',
                'nama_pemasok' => 'CV Sumber Plastik',
                'narahubung' => 'Budi Raharja',
                'email' => 'budi@sumber-plastik.com',
                'telepon' => '031-2345678',
                'alamat' => 'Jl. Raya Surabaya No. 456',
                'kota' => 'Surabaya',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '60123',
                'status' => 'active',
                'catatan' => 'Spesialis plastik dan packaging',
                'created_by' => 'US001',
            ],
            [
                'pemasok_id' => 'PMS0000003',
                'nama_pemasok' => 'PT Logam Berkualitas',
                'narahubung' => 'Sari Wijaya',
                'email' => 'sari@logam-berkualitas.com',
                'telepon' => '022-3456789',
                'alamat' => 'Jl. Logam Mulia No. 789',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'kode_pos' => '40123',
                'status' => 'active',
                'catatan' => 'pemasok bahan logam dan komponen',
                'created_by' => 'US001',
            ],
            [
                'pemasok_id' => 'PMS0000004',
                'nama_pemasok' => 'UD Elektronik Jaya',
                'narahubung' => 'Dedi Kurniawan',
                'email' => 'dedi@elektronik-jaya.com',
                'telepon' => '024-4567890',
                'alamat' => 'Jl. Elektronik No. 101',
                'kota' => 'Semarang',
                'provinsi' => 'Jawa Tengah',
                'kode_pos' => '50123',
                'status' => 'active',
                'catatan' => 'Komponen elektronik dan suku cadang',
                'created_by' => 'US001',
            ],
            [
                'pemasok_id' => 'PMS0000005',
                'nama_pemasok' => 'PT Kemasan Modern',
                'narahubung' => 'Lisa Handayani',
                'email' => 'lisa@kemasan-modern.com',
                'telepon' => '061-5678901',
                'alamat' => 'Jl. Kemasan Indah No. 202',
                'kota' => 'Medan',
                'provinsi' => 'Sumatera Utara',
                'kode_pos' => '20123',
                'status' => 'active',
                'catatan' => 'Spesialis kemasan dan packaging premium',
                'created_by' => 'US001',
            ]
        ];

        foreach ($pemasoks as $pemasok) {
            pemasok::create($pemasok);
        }

        // Create additional random pemasoks
        pemasok::factory()
            ->count(10)
            ->active()
            ->create();

        echo "pemasok seeder completed successfully!\n";
    }
}
