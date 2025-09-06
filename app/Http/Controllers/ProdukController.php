<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\BahanBaku;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    /**
     * Display a listing of the produk.
     */
    public function index(Request $request)
    {
        // Get query parameters with default values
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'produk_id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $perPage = $request->input('per_page', 10);

        // Get location and unit filters
        $lokasiProduk = $request->input('lokasi_produk');
        $satuanProduk = $request->input('satuan_produk');

        // Build the query
        $query = Produk::query();

        // Apply search filter if a search term is present
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', '%' . $search . '%')
                    ->orWhere('lokasi_produk', 'like', '%' . $search . '%');
            });
        }

        // Apply location and unit filters
        if ($lokasiProduk && $lokasiProduk !== 'all') {
            $query->where('lokasi_produk', $lokasiProduk);
        }

        if ($satuanProduk && $satuanProduk !== 'all') {
            $query->where('satuan_produk', $satuanProduk);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Paginate the results
        $produks = $query->paginate($perPage)->withQueryString();

        return Inertia::render('produk/index', [
            'produk' => $produks,
            'filters' => [
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => (int) $perPage,
                'lokasi_produk' => $lokasiProduk,
                'satuan_produk' => $satuanProduk,
            ],
            'uniqueLokasi' => Produk::select('lokasi_produk')->distinct()->orderBy('lokasi_produk')->pluck('lokasi_produk'),
            'uniqueSatuan' => Produk::select('satuan_produk')->distinct()->orderBy('satuan_produk')->pluck('satuan_produk'),
            'flash' => [
                'message' => session('message'),
                'type' => session('type', 'success'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new produk.
     */
    public function create()
    {
        $bahanBakus = BahanBaku::select('bahan_baku_id', 'nama_bahan', 'satuan_bahan')
            ->orderBy('nama_bahan')
            ->get();

        return Inertia::render('produk/create', [
            'bahanBakus' => $bahanBakus
        ]);
    }

    /**
     * Display the specified produk.
     */
    public function show(Produk $produk)
    {
        $produk->load([
            'bahanBaku',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('produk/show', [
            'produk' => $produk
        ]);
    }

    /**
     * Store a newly created produk in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_produk' => ['required', 'string', 'max:255'],
            'lokasi_produk' => ['required', 'string', 'max:255'],
            'stok_produk' => ['nullable', 'numeric', 'min:0'],
            'satuan_produk' => ['required', 'string', 'max:50'],
            'hpp_produk' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'permintaan_harian_rata2_produk' => ['required', 'numeric', 'min:0'],
            'permintaan_harian_maksimum_produk' => ['required', 'numeric', 'min:0'],
            'waktu_tunggu_rata2_produk' => ['required', 'numeric', 'min:0'],
            'waktu_tunggu_maksimum_produk' => ['required', 'numeric', 'min:0'],
            'permintaan_tahunan' => ['required', 'numeric', 'min:0'],
            'biaya_pemesanan_produk' => ['required', 'numeric', 'min:0'],
            'biaya_penyimpanan_produk' => ['required', 'numeric', 'min:0'],
            'bahan_baku' => ['required', 'array', 'min:1'],
            'bahan_baku.*.bahan_baku_id' => ['required', 'string', 'exists:bahan_baku,bahan_baku_id'],
            'bahan_baku.*.jumlah_bahan_baku' => ['required', 'numeric', 'min:0.01'],
        ]);

        $produk = DB::transaction(function () use ($validated) {
            // Calculate safety stock
            $safety_stock_produk = ($validated['permintaan_harian_maksimum_produk'] * $validated['waktu_tunggu_maksimum_produk']) -
                ($validated['permintaan_harian_rata2_produk'] * $validated['waktu_tunggu_rata2_produk']);

            // Calculate reorder point (ROP)
            $rop_produk = ($validated['permintaan_harian_rata2_produk'] * $validated['waktu_tunggu_rata2_produk']) + $safety_stock_produk;

            // Calculate EOQ
            $eoq_produk = sqrt((2 * $validated['permintaan_tahunan'] * $validated['biaya_pemesanan_produk']) /
                $validated['biaya_penyimpanan_produk']);

            // Create produk
            $produk = Produk::create([
                'nama_produk' => $validated['nama_produk'],
                'lokasi_produk' => $validated['lokasi_produk'],
                'stok_produk' => $validated['stok_produk'] ?? 0,
                'satuan_produk' => $validated['satuan_produk'],
                'hpp_produk' => $validated['hpp_produk'],
                'harga_jual' => $validated['harga_jual'],
                'permintaan_harian_rata2_produk' => $validated['permintaan_harian_rata2_produk'],
                'permintaan_harian_maksimum_produk' => $validated['permintaan_harian_maksimum_produk'],
                'waktu_tunggu_rata2_produk' => $validated['waktu_tunggu_rata2_produk'],
                'waktu_tunggu_maksimum_produk' => $validated['waktu_tunggu_maksimum_produk'],
                'permintaan_tahunan' => $validated['permintaan_tahunan'],
                'biaya_pemesanan_produk' => $validated['biaya_pemesanan_produk'],
                'biaya_penyimpanan_produk' => $validated['biaya_penyimpanan_produk'],
                'safety_stock_produk' => $safety_stock_produk,
                'rop_produk' => $rop_produk,
                'eoq_produk' => $eoq_produk,
            ]);

            // Insert bahan baku relationships
            foreach ($validated['bahan_baku'] as $bahan) {
                DB::table('bahan_produksi')->insert([
                    'produk_id' => $produk->produk_id,
                    'bahan_baku_id' => $bahan['bahan_baku_id'],
                    'jumlah_bahan_baku' => $bahan['jumlah_bahan_baku'],
                ]);
            }

            return $produk; // ✅ return di sini
        });

        return redirect()->route('produk.index')
            ->with('message', "Produk '{$validated['nama_produk']}' has been successfully created with ID: {$produk->produk_id}.")
            ->with('type', 'success');
    }

    /**
     * Show the form for editing the specified produk.
     */
    public function edit(Produk $produk)
    {
        // Load bahan baku yang digunakan produk ini
        $bahanProduksi = DB::table('bahan_produksi')
            ->join('bahan_baku', 'bahan_produksi.bahan_baku_id', '=', 'bahan_baku.bahan_baku_id')
            ->where('bahan_produksi.produk_id', $produk->produk_id)
            ->select('bahan_baku.*', 'bahan_produksi.jumlah_bahan_baku')
            ->get();

        // Load semua bahan baku untuk dropdown
        $bahanBakus = BahanBaku::select('bahan_baku_id', 'nama_bahan', 'satuan_bahan')
            ->orderBy('nama_bahan')
            ->get();

        return Inertia::render('produk/edit', [
            'produk' => $produk,
            'bahanProduksi' => $bahanProduksi,
            'bahanBakus' => $bahanBakus
        ]);
    }

    /**
     * Update the specified produk in storage.
     */
    public function update(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'nama_produk' => ['required', 'string', 'max:255'],
            'lokasi_produk' => ['required', 'string', 'max:255'],
            'stok_produk' => ['required', 'numeric', 'min:0'],
            'satuan_produk' => ['required', 'string', 'max:50'],
            'hpp_produk' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'permintaan_harian_rata2_produk' => ['required', 'numeric', 'min:0'],
            'permintaan_harian_maksimum_produk' => ['required', 'numeric', 'min:0'],
            'waktu_tunggu_rata2_produk' => ['required', 'numeric', 'min:0'],
            'waktu_tunggu_maksimum_produk' => ['required', 'numeric', 'min:0'],
            'permintaan_tahunan' => ['required', 'numeric', 'min:0'],
            'biaya_pemesanan_produk' => ['required', 'numeric', 'min:0'],
            'biaya_penyimpanan_produk' => ['required', 'numeric', 'min:0'],
            'bahan_baku' => ['required', 'array', 'min:1'],
            'bahan_baku.*.bahan_baku_id' => ['required', 'string', 'exists:bahan_baku,bahan_baku_id'],
            'bahan_baku.*.jumlah_bahan_baku' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($validated, $produk) {
            // Recalculate safety stock
            $safety_stock_produk = ($validated['permintaan_harian_maksimum_produk'] * $validated['waktu_tunggu_maksimum_produk']) -
                ($validated['permintaan_harian_rata2_produk'] * $validated['waktu_tunggu_rata2_produk']);

            // Recalculate reorder point (ROP)
            $rop_produk = ($validated['permintaan_harian_rata2_produk'] * $validated['waktu_tunggu_rata2_produk']) + $safety_stock_produk;

            // Recalculate EOQ
            $eoq_produk = sqrt((2 * $validated['permintaan_tahunan'] * $validated['biaya_pemesanan_produk']) /
                $validated['biaya_penyimpanan_produk']);

            // Update produk with validated data and recalculated values
            $produk->update([
                ...$validated,
                'safety_stock_produk' => $safety_stock_produk,
                'rop_produk' => $rop_produk,
                'eoq_produk' => $eoq_produk,
            ]);

            // Delete existing bahan baku relationships
            DB::table('bahan_produksi')->where('produk_id', $produk->produk_id)->delete();

            // Insert new bahan baku relationships
            foreach ($validated['bahan_baku'] as $bahan) {
                DB::table('bahan_produksi')->insert([
                    'produk_id' => $produk->produk_id,
                    'bahan_baku_id' => $bahan['bahan_baku_id'],
                    'jumlah_bahan_baku' => $bahan['jumlah_bahan_baku'],
                ]);
            }
        });

        return redirect()->route('produk.index')
            ->with('message', "Produk '{$validated['nama_produk']}' has been successfully updated.")
            ->with('type', 'success');
    }

    /**
     * Remove the specified produk from storage.
     */
    public function destroy(Produk $produk)
    {
        try {
            $namaProduk = $produk->nama_produk;
            $produkId = $produk->produk_id;

            DB::transaction(function () use ($produk) {
                // Delete bahan baku relationships
                DB::table('bahan_produksi')->where('produk_id', $produk->produk_id)->delete();

                // Delete produk
                $produk->delete();
            });

            return redirect()->route('produk.index')
                ->with('message', "Produk '{$namaProduk}' (ID: {$produkId}) has been successfully deleted.")
                ->with('type', 'success');
        } catch (\Exception $e) {
            return redirect()->route('produk.index')
                ->with('message', 'Failed to delete produk. Please try again.')
                ->with('type', 'error');
        }
    }
}
