<?php

namespace App\Http\Controllers;

use App\Models\Pengadaan;
use App\Models\PenugasanProduksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PenugasanProduksiController extends Controller
{
    public function index()
    {
        // Menampilkan daftar semua penugasan yang pernah dibuat
        $penugasanProduksi = PenugasanProduksi::with(['pengadaan.detail.produk', 'staf'])->latest()->paginate(10);
        return Inertia::render('PenugasanProduksi/Index', compact('penugasanProduksi'));
    }

    public function create()
    {
        // Mengambil hanya Pengadaan 'Produksi' yang sudah disetujui & belum ditugaskan
        $pengadaanList = Pengadaan::where('jenis_pengadaan', 'Produksi')
            ->where('status', 'approved')
            ->whereDoesntHave('penugasan') // Cek relasi 'penugasan' di Model Pengadaan
            ->with('detail.produk')
            ->get();

        // Mengambil daftar Staf RnD
        $stafRnD = User::whereHas('role', fn($q) => $q->where('nama_role', 'Staf RnD'))->get();

        return Inertia::render('PenugasanProduksi/Create', compact('pengadaanList', 'stafRnD'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pengadaan_id' => 'required|exists:pengadaan,pengadaan_id',
            'staf_id' => 'required|exists:users,user_id',
        ]);

        // Mengambil jumlah dari detail pengadaan terkait
        $pengadaan = Pengadaan::with('detail')->find($request->pengadaan_id);
        $jumlahProduksi = $pengadaan->detail->first()->jumlah;

        PenugasanProduksi::create([
            'penugasan_produksi_id' => 'TGS-' . Str::upper(Str::random(8)),
            'pengadaan_id' => $request->pengadaan_id,
            'staf_id' => $request->staf_id,
            'jumlah_produksi' => $jumlahProduksi,
            'catatan' => $request->catatan,
        ]);

        return redirect()->route('penugasan-produksi.index')->with('success', 'Penugasan produksi berhasil dibuat.');
    }

    // Method show(), edit(), update(), destroy() bisa ditambahkan sesuai kebutuhan
}
