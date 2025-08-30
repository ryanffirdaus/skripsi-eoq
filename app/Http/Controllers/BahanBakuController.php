<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class BahanBakuController extends Controller
{
    /**
     * Display a listing of the bahan baku.
     */
    public function index(Request $request)
    {
        // Get query parameters with default values
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'bahan_baku_id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $perPage = $request->input('per_page', 10);

        // Get location and unit filters
        $lokasiBahan = $request->input('lokasi_bahan');
        $satuanBahan = $request->input('satuan_bahan');

        // Build the query
        $query = BahanBaku::query();

        // Apply search filter if a search term is present
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_bahan', 'like', '%' . $search . '%')
                  ->orWhere('lokasi_bahan', 'like', '%' . $search . '%');
            });
        }

        // Apply location and unit filters
        if ($lokasiBahan && $lokasiBahan !== 'all') {
            $query->where('lokasi_bahan', $lokasiBahan);
        }

        if ($satuanBahan && $satuanBahan !== 'all') {
            $query->where('satuan_bahan', $satuanBahan);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Paginate the results
        $bahanBakus = $query->paginate($perPage)->withQueryString();

        return Inertia::render('bahan-baku/index', [
            'bahanBaku' => $bahanBakus,
            'filters' => [
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => (int) $perPage,
                'lokasi_bahan' => $lokasiBahan,
                'satuan_bahan' => $satuanBahan,
            ],
            'uniqueLokasi' => BahanBaku::select('lokasi_bahan')->distinct()->orderBy('lokasi_bahan')->pluck('lokasi_bahan'),
            'uniqueSatuan' => BahanBaku::select('satuan_bahan')->distinct()->orderBy('satuan_bahan')->pluck('satuan_bahan'),
            'flash' => [
                'message' => session('message'),
                'type' => session('type', 'success'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new bahan baku.
     */
    public function create()
    {
        return Inertia::render('bahan-baku/create');
    }

    /**
     * Store a newly created bahan baku in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_bahan' => ['required', 'string', 'max:255'],
            'lokasi_bahan' => ['required', 'string', 'max:255'],
            'stok_bahan' => ['nullable', 'numeric', 'min:0'],
            'satuan_bahan' => ['required', 'string', 'max:50'],
            'harga_bahan' => ['required', 'numeric', 'min:0'],
            'permintaan_harian_rata2_bahan' => ['required', 'numeric', 'min:0'],
            'permintaan_harian_maksimum_bahan' => ['required', 'numeric', 'min:0'],
            'waktu_tunggu_rata2_bahan' => ['required', 'numeric', 'min:0'],
            'waktu_tunggu_maksimum_bahan' => ['required', 'numeric', 'min:0'],
            'permintaan_tahunan' => ['required', 'numeric', 'min:0'],
            'biaya_pemesanan_bahan' => ['required', 'numeric', 'min:0'],
            'biaya_penyimpanan_bahan' => ['required', 'numeric', 'min:0'],
        ]);

        // Generate a unique bahan_baku_id with pattern BA001, BA002, etc.
        $latestBahan = BahanBaku::latest('bahan_baku_id')->first();
        $nextId = $latestBahan ? intval(substr($latestBahan->bahan_baku_id, 2)) + 1 : 1;
        $bahan_baku_id = 'BA' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        // Calculate safety stock
        $safety_stock_bahan = ($validated['permintaan_harian_maksimum_bahan'] * $validated['waktu_tunggu_maksimum_bahan']) -
                              ($validated['permintaan_harian_rata2_bahan'] * $validated['waktu_tunggu_rata2_bahan']);

        // Calculate reorder point (ROP)
        $rop_bahan = ($validated['permintaan_harian_rata2_bahan'] * $validated['waktu_tunggu_rata2_bahan']) + $safety_stock_bahan;

        // Calculate EOQ
        $eoq_bahan = sqrt((2 * $validated['permintaan_tahunan'] * $validated['biaya_pemesanan_bahan']) /
                           $validated['biaya_penyimpanan_bahan']);

        BahanBaku::create([
            'bahan_baku_id' => $bahan_baku_id,
            'nama_bahan' => $validated['nama_bahan'],
            'lokasi_bahan' => $validated['lokasi_bahan'],
            'stok_bahan' => $validated['stok_bahan'] ?? 0,
            'satuan_bahan' => $validated['satuan_bahan'],
            'harga_bahan' => $validated['harga_bahan'],
            'permintaan_harian_rata2_bahan' => $validated['permintaan_harian_rata2_bahan'],
            'permintaan_harian_maksimum_bahan' => $validated['permintaan_harian_maksimum_bahan'],
            'waktu_tunggu_rata2_bahan' => $validated['waktu_tunggu_rata2_bahan'],
            'waktu_tunggu_maksimum_bahan' => $validated['waktu_tunggu_maksimum_bahan'],
            'permintaan_tahunan' => $validated['permintaan_tahunan'],
            'biaya_pemesanan_bahan' => $validated['biaya_pemesanan_bahan'],
            'biaya_penyimpanan_bahan' => $validated['biaya_penyimpanan_bahan'],
            'safety_stock_bahan' => $safety_stock_bahan,
            'rop_bahan' => $rop_bahan,
            'eoq_bahan' => $eoq_bahan,
        ]);

        return redirect()->route('bahan-baku.index')
            ->with('message', "Bahan Baku '{$validated['nama_bahan']}' has been successfully created with ID: {$bahan_baku_id}.")
            ->with('type', 'success');
    }

    /**
     * Show the form for editing the specified bahan baku.
     */
    public function edit(BahanBaku $bahanBaku)
    {
        return Inertia::render('bahan-baku/edit', [
            'bahanBaku' => $bahanBaku
        ]);
    }

    /**
     * Update the specified bahan baku in storage.
     */
    public function update(Request $request, BahanBaku $bahanBaku)
    {
        $validated = $request->validate([
            'nama_bahan' => ['required', 'string', 'max:255'],
            'lokasi_bahan' => ['required', 'string', 'max:255'],
            'stok_bahan' => ['required', 'numeric', 'min:0'],
            'satuan_bahan' => ['required', 'string', 'max:50'],
            'harga_bahan' => ['required', 'numeric', 'min:0'],
            'permintaan_harian_rata2_bahan' => ['required', 'numeric', 'min:0'],
            'permintaan_harian_maksimum_bahan' => ['required', 'numeric', 'min:0'],
            'waktu_tunggu_rata2_bahan' => ['required', 'numeric', 'min:0'],
            'waktu_tunggu_maksimum_bahan' => ['required', 'numeric', 'min:0'],
            'permintaan_tahunan' => ['required', 'numeric', 'min:0'],
            'biaya_pemesanan_bahan' => ['required', 'numeric', 'min:0'],
            'biaya_penyimpanan_bahan' => ['required', 'numeric', 'min:0'],
        ]);

        // Recalculate safety stock
        $safety_stock_bahan = ($validated['permintaan_harian_maksimum_bahan'] * $validated['waktu_tunggu_maksimum_bahan']) -
                              ($validated['permintaan_harian_rata2_bahan'] * $validated['waktu_tunggu_rata2_bahan']);

        // Recalculate reorder point (ROP)
        $rop_bahan = ($validated['permintaan_harian_rata2_bahan'] * $validated['waktu_tunggu_rata2_bahan']) + $safety_stock_bahan;

        // Recalculate EOQ
        $eoq_bahan = sqrt((2 * $validated['permintaan_tahunan'] * $validated['biaya_pemesanan_bahan']) /
                           $validated['biaya_penyimpanan_bahan']);

        // Update with validated data and recalculated values
        $bahanBaku->update([
            ...$validated,
            'safety_stock_bahan' => $safety_stock_bahan,
            'rop_bahan' => $rop_bahan,
            'eoq_bahan' => $eoq_bahan,
        ]);

        return redirect()->route('bahan-baku.index')
            ->with('message', "Bahan Baku '{$validated['nama_bahan']}' has been successfully updated.")
            ->with('type', 'success');
    }

    /**
     * Remove the specified bahan baku from storage.
     */
    public function destroy(BahanBaku $bahanBaku)
    {
        try {
            $namaBahan = $bahanBaku->nama_bahan;
            $bahanBakuId = $bahanBaku->bahan_baku_id;

            $bahanBaku->delete();

            return redirect()->route('bahan-baku.index')
                ->with('message', "Bahan Baku '{$namaBahan}' (ID: {$bahanBakuId}) has been successfully deleted.")
                ->with('type', 'success');
        } catch (\Exception $e) {
            return redirect()->route('bahan-baku.index')
                ->with('message', 'Failed to delete bahan baku. Please try again.')
                ->with('type', 'error');
        }
    }
}
