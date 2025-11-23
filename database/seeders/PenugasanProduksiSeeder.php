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
        $users = User::where('role_id', 'R03')->take(3)->get(); // Ambil 3 production staff/workers (Staf RnD)
        $creator = User::whereIn('role_id', ['R01', 'R09'])->first() ?? User::first(); // Admin or Manajer RnD

        if ($pengadaanDetails->isEmpty() || $users->isEmpty() || !$creator) {
            $this->command->info('Skipping PenugasanProduksi seeder: insufficient data');
            return;
        }

        $statuses = ['ditugaskan', 'proses', 'selesai'];
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
                    'dibuat_oleh' => $creator->user_id,
                    'diubah_oleh' => $status === 'proses' ? $user->user_id : null,
                    'dihapus_oleh' => null,
                ]);

                if ($counter > 10) break;
            }
            if ($counter > 10) break;
        }

        $this->command->info('PenugasanProduksiSeeder: ' . $counter . ' records created');
    }
}
