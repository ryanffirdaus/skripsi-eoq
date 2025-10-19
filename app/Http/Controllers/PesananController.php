<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Pelanggan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PesananController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check authorization
        $this->authorize('viewAny', Pesanan::class);

        // Ambil query params
        $search = $request->input('search');
        $status = $request->input('status'); // filter status
        $perPage = $request->input('per_page', 10);

        // Daftar kolom yang boleh di-sort
        $allowedSorts = ['pesanan_id', 'nama_pelanggan', 'status', 'total_harga', 'created_at'];

        // Ambil sort_by & arah
        $sortBy = trim($request->get('sort_by') ?? '') ?: 'created_at';
        $sortDirection = $request->get('sort_direction', 'desc');

        // Pastikan kolomnya valid
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        // Query awal
        $query = Pesanan::with(['pelanggan', 'detail.produk']);

        // Filter search (misalnya cari nama pelanggan / id pesanan)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('pesanan_id', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($q2) use ($search) {
                        $q2->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }

        // Filter status (kecuali "all")
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Terapkan sorting
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $pesanan = $query->paginate($perPage)->withQueryString();

        // Transform data untuk frontend
        $pesanan->getCollection()->transform(function ($item) {
            return [
                'pesanan_id' => $item->pesanan_id,
                'pelanggan_id' => $item->pelanggan_id,
                'nama_pelanggan' => $item->pelanggan->nama_pelanggan ?? 'N/A',
                'tanggal_pemesanan' => $item->tanggal_pemesanan,
                'status' => $item->status,
                'total_harga' => $item->total_harga,
                'jumlah_produk' => $item->detail->sum('jumlah_produk'),
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return Inertia::render('pesanan/index', [
            'pesanan' => $pesanan,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => (int) $perPage,
            ],
            'flash' => [
                'message' => session('message'),
                'type' => session('type', 'success'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Pesanan::class);

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
        $this->authorize('create', Pesanan::class);

        $validated = $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,pelanggan_id',
            'tanggal_pemesanan' => 'required|date',
            'produk' => 'required|array|min:1',
            'produk.*.produk_id' => 'required|exists:produk,produk_id',
            'produk.*.jumlah_produk' => 'required|integer|min:1',
            'produk.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            // Create pesanan (total_harga will be auto-calculated by model)
            $pesanan = Pesanan::create([
                'pelanggan_id' => $validated['pelanggan_id'],
                'tanggal_pemesanan' => $validated['tanggal_pemesanan'],
                'total_harga' => 0, // Will be updated by model event
            ]);

            // Create detail records using PesananDetail model
            foreach ($validated['produk'] as $item) {
                \App\Models\PesananDetail::create([
                    'pesanan_id' => $pesanan->pesanan_id,
                    'produk_id' => $item['produk_id'],
                    'jumlah_produk' => $item['jumlah_produk'],
                    'harga_satuan' => $item['harga_satuan'],
                    // subtotal will be auto-calculated by model
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
            'detail.produk',
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
        $pesanan = Pesanan::with(['pelanggan', 'detail.produk'])->where('pesanan_id', $pesanan_id)->firstOrFail();
        $this->authorize('update', $pesanan);

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
        $this->authorize('update', $pesanan);

        $validated = $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,pelanggan_id',
            'tanggal_pemesanan' => 'required|date',
            'status' => 'required|in:pending,diproses,dikirim,selesai,dibatalkan',
            'catatan' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.produk_id' => 'required|exists:produk,produk_id',
            'products.*.jumlah_produk' => 'required|integer|min:1',
            'products.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $pesanan) {
            // Update pesanan
            $pesanan->update([
                'pelanggan_id' => $validated['pelanggan_id'],
                'tanggal_pemesanan' => $validated['tanggal_pemesanan'],
                'status' => $validated['status'],
                'catatan' => $validated['catatan'],
            ]);

            // Delete old details and create new ones
            $pesanan->detail()->delete();

            foreach ($validated['products'] as $item) {
                \App\Models\PesananDetail::create([
                    'pesanan_id' => $pesanan->pesanan_id,
                    'produk_id' => $item['produk_id'],
                    'jumlah_produk' => $item['jumlah_produk'],
                    'harga_satuan' => $item['harga_satuan'],
                    // subtotal will be auto-calculated by model
                ]);
            }
            // total_harga will be auto-updated by model event
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
        $this->authorize('delete', $pesanan);

        $pesanan->delete();

        return redirect()->route('pesanan.index')->with('flash', [
            'message' => 'Pesanan berhasil dihapus.',
            'type' => 'success'
        ]);
    }
}
