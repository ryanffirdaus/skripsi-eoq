<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanBahanBaku;
use App\Models\PenerimaanBahanBakuDetail;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\BahanBaku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PenerimaanBahanBakuController extends Controller
{
    public function index(Request $request)
    {
        $query = PenerimaanBahanBaku::with(['pembelian:pembelian_id,nomor_po', 'pemasok:pemasok_id,nama_pemasok']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penerimaan', 'like', "%{$search}%")
                    ->orWhere('nomor_surat_jalan', 'like', "%{$search}%")
                    ->orWhereHas('pembelian', fn($subq) => $subq->where('nomor_po', 'like', "%{$search}%"));
            });
        }

        $penerimaan = $query->orderBy('tanggal_penerimaan', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('penerimaan-bahan-baku/index', [
            'penerimaan' => $penerimaan,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        $pembelians = Pembelian::whereIn('status', ['confirmed', 'partial_received'])
            ->whereHas('detail', fn($q) => $q->where('item_type', 'bahan_baku')->whereRaw('qty_dipesan > qty_diterima'))
            ->orderBy('tanggal_pembelian', 'desc')
            ->with('pemasok:pemasok_id,nama_pemasok')
            ->get()
            ->map(fn($pembelian) => [
                'pembelian_id' => $pembelian->pembelian_id,
                'display_text' => $pembelian->nomor_po . ' - ' . ($pembelian->pemasok->nama_pemasok ?? 'N/A'),
            ]);

        return Inertia::render('penerimaan-bahan-baku/create', ['pembelians' => $pembelians]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pembelian_id' => 'required|exists:pembelian,pembelian_id',
            'nomor_surat_jalan' => 'required|string|max:255',
            'tanggal_penerimaan' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.pembelian_detail_id' => 'required|exists:pembelian_detail,pembelian_detail_id',
            'items.*.qty_diterima' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $itemsToProcess = array_filter($request->items, fn($item) => $item['qty_diterima'] > 0);
        if (empty($itemsToProcess)) {
            return redirect()->back()->withErrors(['items' => 'Harap masukkan kuantitas diterima minimal untuk satu item.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $pembelian = Pembelian::with('detail')->findOrFail($request->pembelian_id);

            // 1. Buat Header Penerimaan
            $penerimaan = PenerimaanBahanBaku::create($request->only(['pembelian_id', 'nomor_surat_jalan', 'tanggal_penerimaan', 'catatan']) + [
                'pemasok_id' => $pembelian->pemasok_id,
                'status' => 'confirmed',
            ]);

            // 2. Proses setiap item untuk Penerimaan
            foreach ($itemsToProcess as $itemData) {
                $pembelianDetail = $pembelian->detail->find($itemData['pembelian_detail_id']);
                if ($pembelianDetail && $pembelianDetail->item_type === 'bahan_baku') {
                    PenerimaanBahanBakuDetail::create([
                        'penerimaan_id' => $penerimaan->penerimaan_id,
                        'pembelian_detail_id' => $pembelianDetail->pembelian_detail_id,
                        'bahan_baku_id' => $pembelianDetail->item_id,
                        'qty_diterima' => $itemData['qty_diterima'],
                    ]);

                    // Update kuantitas & stok untuk yang diterima
                    $pembelianDetail->increment('qty_diterima', $itemData['qty_diterima']);
                    BahanBaku::find($pembelianDetail->item_id)->increment('stok_bahan', $itemData['qty_diterima']);
                }
            }

            // 3. Update status Pembelian
            $allReceived = $pembelian->fresh()->detail->every(fn($detail) => $detail->isFullyReceived());
            $pembelian->status = $allReceived ? 'fully_received' : 'partial_received';
            $pembelian->save();

            DB::commit();

            return redirect()->route('penerimaan-bahan-baku.index')->with('flash', ['message' => 'Penerimaan bahan baku berhasil dicatat.', 'type' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('flash', ['message' => 'Terjadi kesalahan: ' . $e->getMessage(), 'type' => 'error']);
        }
    }

    public function show(PenerimaanBahanBaku $penerimaanBahanBaku)
    {
        $penerimaanBahanBaku->load([
            'pembelian:pembelian_id,nomor_po,tanggal_pembelian',
            'pemasok:pemasok_id,nama_pemasok,alamat',
            'detail.bahanBaku'
        ]);

        return Inertia::render('penerimaan-bahan-baku/show', [
            'penerimaan' => $penerimaanBahanBaku
        ]);
    }

    public function getPembelianDetails(Pembelian $pembelian)
    {
        $pembelian->load(['detail' => fn($query) => $query->where('item_type', 'bahan_baku')->whereRaw('qty_dipesan > qty_diterima')]);
        return response()->json($pembelian->detail->map(fn($detail) => [
            'pembelian_detail_id' => $detail->pembelian_detail_id,
            'item_id' => $detail->item_id,
            'nama_item' => $detail->nama_item,
            'satuan' => $detail->satuan,
            'qty_dipesan' => $detail->qty_dipesan,
            'qty_diterima_sebelumnya' => $detail->qty_diterima,
            'qty_sisa' => $detail->getOutstandingQty(),
        ]));
    }
}
