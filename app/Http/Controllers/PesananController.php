<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Pelanggan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PesananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pesanan::with(['pelanggan', 'produk']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pesanan_id', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($q) use ($search) {
                        $q->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $pesanan = $query->paginate($perPage)->withQueryString();

        return Inertia::render('pesanan/index', [
            'pesanan' => $pesanan,
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_direction', 'per_page']),
            'flash' => session('flash'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pelanggan = Pelanggan::all();
        $produk = Produk::all();

        return Inertia::render('pesanan/create', [
            'pelanggan' => $pelanggan,
            'produk' => $produk,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,pelanggan_id',
            'tanggal_pemesanan' => 'required|date',
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'produk' => 'required|array|min:1',
            'produk.*.produk_id' => 'required|exists:produk,produk_id',
            'produk.*.jumlah_produk' => 'required|integer|min:1',
            'produk.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            // Calculate total
            $totalHarga = 0;
            foreach ($validated['produk'] as $item) {
                $totalHarga += $item['jumlah_produk'] * $item['harga_satuan'];
            }

            // Create pesanan
            $pesanan = Pesanan::create([
                'pelanggan_id' => $validated['pelanggan_id'],
                'tanggal_pemesanan' => $validated['tanggal_pemesanan'],
                'status' => $validated['status'],
                'total_harga' => $totalHarga,
            ]);

            // Attach products with pivot data
            foreach ($validated['produk'] as $item) {
                $subtotal = $item['jumlah_produk'] * $item['harga_satuan'];
                $pesanan->produk()->attach($item['produk_id'], [
                    'jumlah_produk' => $item['jumlah_produk'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $subtotal,
                ]);
            }
        });

        return redirect()->route('pesanan.index')->with('flash', [
            'message' => 'Pesanan berhasil dibuat.',
            'type' => 'success'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($pesanan_id)
    {
        $pesanan = Pesanan::with([
            'pelanggan',
            'produk',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ])->where('pesanan_id', $pesanan_id)->firstOrFail();

        return Inertia::render('pesanan/show', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($pesanan_id)
    {
        $pesanan = Pesanan::with(['pelanggan', 'produk'])->where('pesanan_id', $pesanan_id)->firstOrFail();
        $pelanggan = Pelanggan::all();
        $produk = Produk::all();

        return Inertia::render('pesanan/edit', [
            'pesanan' => $pesanan,
            'pelanggan' => $pelanggan,
            'produk' => $produk,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $pesanan_id)
    {
        $pesanan = Pesanan::where('pesanan_id', $pesanan_id)->firstOrFail();
        $validated = $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,pelanggan_id',
            'tanggal_pesanan' => 'required|date',
            'status' => 'required|in:pending,diproses,dikirim,selesai,dibatalkan',
            'catatan' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.produk_id' => 'required|exists:produk,produk_id',
            'products.*.jumlah' => 'required|integer|min:1',
            'products.*.harga' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $pesanan) {
            // Calculate total
            $totalHarga = 0;
            foreach ($validated['products'] as $item) {
                $totalHarga += $item['jumlah'] * $item['harga'];
            }

            // Update pesanan
            $pesanan->update([
                'pelanggan_id' => $validated['pelanggan_id'],
                'tanggal_pesanan' => $validated['tanggal_pesanan'],
                'status' => $validated['status'],
                'catatan' => $validated['catatan'],
                'total_harga' => $totalHarga,
            ]);

            // Sync products with pivot data
            $syncData = [];
            foreach ($validated['products'] as $item) {
                $subtotal = $item['jumlah'] * $item['harga'];
                $syncData[$item['produk_id']] = [
                    'jumlah' => $item['jumlah'],
                    'harga' => $item['harga'],
                    'subtotal' => $subtotal,
                ];
            }
            $pesanan->produk()->sync($syncData);
        });

        return redirect()->route('pesanan.index')->with('flash', [
            'message' => 'Pesanan berhasil diperbarui.',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($pesanan_id)
    {
        $pesanan = Pesanan::where('pesanan_id', $pesanan_id)->firstOrFail();
        $pesanan->delete();

        return redirect()->route('pesanan.index')->with('flash', [
            'message' => 'Pesanan berhasil dihapus.',
            'type' => 'success'
        ]);
    }
}
