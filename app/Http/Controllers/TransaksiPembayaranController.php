<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\TransaksiPembayaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransaksiPembayaranController extends Controller
{
    public function index()
    {
        $transaksiPembayaran = TransaksiPembayaran::with('pembelian.pemasok')->latest()->paginate(10);
        return Inertia::render('transaksi-pembayaran/index', [
            'transaksiPembayaran' => $transaksiPembayaran,
        ]);
    }

    public function create()
    {
        $pembelian = Pembelian::where('status', 'PO Disetujui')->get();
        return Inertia::render('transaksi-pembayaran/create', [
            'pembelian' => $pembelian,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pembelian_id' => 'required|exists:pembelian,pembelian_id',
            'tanggal_pembayaran' => 'required|date',
            'total_pembayaran' => 'required|numeric',
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'deskripsi' => 'nullable|string',
        ]);

        $imageName = time() . '.' . $request->bukti_pembayaran->extension();
        $request->bukti_pembayaran->move(public_path('images'), $imageName);

        TransaksiPembayaran::create([
            'pembelian_id' => $request->pembelian_id,
            'tanggal_pembayaran' => $request->tanggal_pembayaran,
            'total_pembayaran' => $request->total_pembayaran,
            'bukti_pembayaran' => $imageName,
            'deskripsi' => $request->deskripsi,
        ]);

        return redirect()->route('transaksi-pembayaran.index');
    }
}
