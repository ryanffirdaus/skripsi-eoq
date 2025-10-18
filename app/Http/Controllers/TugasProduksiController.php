<?php

namespace App\Http\Controllers;

use App\Models\MasterProduk;
use App\Models\PenugasanProduksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TugasProduksiController extends Controller
{
    public function index()
    {
        // Mengambil HANYA tugas milik user yang sedang login
        $tugasProduksi = PenugasanProduksi::where('user_id', Auth::id())
            ->with('pengadaan.detail.produk')
            ->latest()
            ->paginate(10);

        return Inertia::render('TugasProduksi/Index', compact('tugasProduksi'));
    }

    public function update(Request $request, string $id)
    {
        // Cari tugas spesifik milik user yang login untuk keamanan
        $penugasan = PenugasanProduksi::where('penugasan_produksi_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Validasi agar tugas tidak dieksekusi dua kali
        if ($penugasan->status !== 'Ditugaskan') {
            return back()->with('error', 'Tugas ini sudah berjalan atau telah selesai.');
        }

        try {
            DB::transaction(function () use ($penugasan) {
                // 1. Ubah status penugasan
                $penugasan->status = 'Berjalan';
                $penugasan->save();

                // 2. Ambil detail produk dan jumlah yang akan diproduksi
                $produkToProduce = $penugasan->pengadaan->detail->first()->produk;
                $jumlahToProduce = $penugasan->jumlah_produksi;

                // 3. Kurangi stok bahan baku
                foreach ($produkToProduce->bahanProduksi as $komponen) {
                    $komponen->bahanBaku->decrement('stok', $komponen->jumlah_bahan_baku * $jumlahToProduce);
                }

                // 4. Buat record di master_produk untuk setiap unit
                for ($i = 0; $i < $jumlahToProduce; $i++) {
                    MasterProduk::create([
                        'master_produk_id' => 'MP-' . $produkToProduce->produk_id . '-' . Str::upper(Str::random(6)),
                        'penugasan_id' => $penugasan->penugasan_produksi_id,
                        'produk_id' => $produkToProduce->produk_id,
                        'status' => 'Pending QC',
                    ]);
                }

                // 5. Update status penugasan menjadi selesai
                $penugasan->jumlah_telah_diproduksi = $jumlahToProduce;
                $penugasan->status = 'Selesai';
                $penugasan->save();
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses produksi: ' . $e->getMessage());
        }

        return redirect()->route('tugas-produksi.index')->with('success', 'Tugas produksi berhasil diselesaikan.');
    }
}
