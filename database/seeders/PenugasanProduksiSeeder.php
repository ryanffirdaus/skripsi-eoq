<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PenugasanProduksi;
use App\Models\PengadaanDetail;
use App\Models\User;
use Carbon\Carbon;

class PenugasanProduksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get sample data
        $pengadaanDetails = PengadaanDetail::take(5)->get();
        $users = User::where('role_id', 'ROLE003')->take(3)->get(); // Ambil 3 production staff/workers
        $creator = User::where('role_id', 'ROLE002')->first() ?? User::first(); // Supervisor/Manager

        if ($pengadaanDetails->isEmpty() || $users->isEmpty() || !$creator) {
            $this->command->info('Skipping PenugasanProduksi seeder: insufficient data');
            return;
        }

        $statuses = ['assigned', 'in_progress', 'completed'];
        $counter = 0;

        foreach ($pengadaanDetails as $detail) {
            foreach ($users as $user) {
                $counter++;
                if ($counter > 10) break;

                $status = $statuses[array_rand($statuses)];
                $deadline = Carbon::now()->addDays(rand(3, 14));
                $jumlahProduksi = max(1, intval($detail->qty_disetujui ?? $detail->qty_diminta / 2));

                PenugasanProduksi::create([
                    'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                    'user_id' => $user->user_id,
                    'jumlah_produksi' => $jumlahProduksi,
                    'status' => $status,
                    'deadline' => $deadline,
                    'catatan' => 'Penugasan produksi untuk item ' . $detail->nama_item,
                    'created_by' => $creator->user_id,
                    'updated_by' => $status === 'in_progress' ? $user->user_id : null,
                    'deleted_by' => null,
                ]);

                if ($counter > 10) break;
            }
            if ($counter > 10) break;
        }

        $this->command->info('PenugasanProduksiSeeder: ' . $counter . ' records created');
    }
}
