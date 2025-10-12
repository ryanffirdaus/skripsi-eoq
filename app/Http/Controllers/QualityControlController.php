<?php

namespace App\Http\Controllers;

use App\Models\MasterProduk;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QualityControlController extends Controller
{
    public function index()
    {
        // Menampilkan semua produk yang menunggu untuk di-QC
        $pendingQc = MasterProduk::where('status', 'Pending QC')
            ->with('produk', 'penugasan.staf')
            ->latest()
            ->paginate(15);

        return Inertia::render('quality-control/index', compact('pendingQc'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:Good,Reject',
        ]);

        $masterProduk = MasterProduk::findOrFail($id);

        DB::transaction(function () use ($request, $masterProduk) {
            // 1. Update status produk individual
            $masterProduk->update([
                'status' => $request->status,
                'qc_by' => Auth::id(),
                'qc_at' => now(),
            ]);

            // 2. Jika statusnya 'Good', tambahkan stok di tabel produk utama
            if ($request->status === 'Good') {
                Produk::find($masterProduk->produk_id)->increment('stok');

                // Ubah statusnya menjadi 'Ready' agar tidak bisa diubah lagi
                $masterProduk->update(['status' => 'Ready']);
            }
        });

        return redirect()->route('qc.index')->with('success', 'Status produk berhasil diperbarui.');
    }
}
