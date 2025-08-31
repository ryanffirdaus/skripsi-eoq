<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main suppliers
        $suppliers = [
            [
                'supplier_id' => 'SUP0000001',
                'nama_supplier' => 'PT Bahan Baku Utama',
                'kontak_person' => 'Andi Setiawan',
                'email' => 'andi@bahan-utama.com',
                'telepon' => '021-1234567',
                'alamat' => 'Jl. Industri Raya No. 123',
                'kota' => 'Jakarta',
                'provinsi' => 'DKI Jakarta',
                'kode_pos' => '12345',
                'status' => 'active',
                'catatan' => 'Supplier utama untuk bahan baku kimia',
                'created_by' => 'US001',
            ],
            [
                'supplier_id' => 'SUP0000002',
                'nama_supplier' => 'CV Sumber Plastik',
                'kontak_person' => 'Budi Raharja',
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
                'supplier_id' => 'SUP0000003',
                'nama_supplier' => 'PT Logam Berkualitas',
                'kontak_person' => 'Sari Wijaya',
                'email' => 'sari@logam-berkualitas.com',
                'telepon' => '022-3456789',
                'alamat' => 'Jl. Logam Mulia No. 789',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'kode_pos' => '40123',
                'status' => 'active',
                'catatan' => 'Supplier bahan logam dan komponen',
                'created_by' => 'US001',
            ],
            [
                'supplier_id' => 'SUP0000004',
                'nama_supplier' => 'UD Elektronik Jaya',
                'kontak_person' => 'Dedi Kurniawan',
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
                'supplier_id' => 'SUP0000005',
                'nama_supplier' => 'PT Kemasan Modern',
                'kontak_person' => 'Lisa Handayani',
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

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }

        // Create additional random suppliers
        Supplier::factory()
            ->count(10)
            ->active()
            ->create();

        echo "Supplier seeder completed successfully!\n";
    }
}
