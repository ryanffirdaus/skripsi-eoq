<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanBahanBaku;
use App\Models\PenerimaanBahanBakuDetail;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\BahanBaku;
use App\Http\Traits\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PenerimaanBahanBakuController extends Controller
{
    use RoleAccess;

    public function index(Request $request)
    {
        $query = PenerimaanBahanBaku::with(['pembelianDetail.pembelian.pemasok', 'pembelianDetail.pengadaanDetail']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('penerimaan_id', 'like', "%{$search}%")
                    ->orWhereHas('pembelianDetail.pembelian', fn($subq) => $subq->where('pembelian_id', 'like', "%{$search}%"));
            });
        }

        $penerimaan = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $penerimaan->getCollection()->transform(function ($item) {
            $pembelian = $item->pembelianDetail?->pembelian;
            $pemasok = $pembelian?->pemasok;
            $pengadaanDetail = $item->pembelianDetail?->pengadaanDetail;

            return [
                'penerimaan_id' => $item->penerimaan_id,
                'pembelian_detail_id' => $item->pembelian_detail_id,
                'nomor_penerimaan' => $item->penerimaan_id, // Using ID as nomor for now
                'nomor_surat_jalan' => 'SJ-' . $item->penerimaan_id, // Generate placeholder
                'tanggal_penerimaan' => $item->created_at?->format('Y-m-d'),
                'status' => $pembelian?->status ?? '-', // Get status from Pembelian
                'qty_diterima' => $item->qty_diterima,
                'nama_item' => $pengadaanDetail?->nama_item ?? '-',
                'pembelian' => [
                    'pembelian_id' => $pembelian?->pembelian_id ?? '-',
                ],
                'pemasok' => [
                    'nama_pemasok' => $pemasok?->nama_pemasok ?? '-',
                ],
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        return Inertia::render('penerimaan-bahan-baku/index', [
            'penerimaan' => $penerimaan,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        // Authorization: Admin (R01), Staf Gudang (R02) dan Manajer Gudang (R07) yang bisa create
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk membuat penerimaan bahan baku baru.');
        }

        $pembelians = Pembelian::whereIn('status', ['dikonfirmasi', 'dipesan', 'dikirim'])
            ->with(['pemasok:pemasok_id,nama_pemasok', 'detail.pengadaanDetail'])
            ->orderBy('tanggal_pembelian', 'desc')
            ->get()
            ->map(fn($pembelian) => [
                'pembelian_id' => $pembelian->pembelian_id,
                'pemasok_nama' => $pembelian->pemasok->nama_pemasok ?? 'N/A',
                'display_text' => $pembelian->pembelian_id . ' - ' . ($pembelian->pemasok->nama_pemasok ?? 'N/A'),
                'details' => $pembelian->detail->map(function ($detail) {
                    $pengadaanDetail = $detail->pengadaanDetail;
                    if (!$pengadaanDetail) return null;

                    $qtyDiterima = $detail->penerimaanBahanBaku->sum('qty_diterima');
                    $outstanding = $pengadaanDetail->qty_diminta - $qtyDiterima;  // FIX: qty → qty_diminta

                    return [
                        'pembelian_detail_id' => $detail->pembelian_detail_id,
                        'nama_item' => $pengadaanDetail->nama_item,
                        'satuan' => $pengadaanDetail->satuan,
                        'qty_dipesan' => $pengadaanDetail->qty_diminta,  // FIX: qty → qty_diminta
                        'qty_diterima' => $qtyDiterima,
                        'outstanding_qty' => $outstanding,
                    ];
                })->filter(fn($d) => $d && $d['outstanding_qty'] > 0)->values(),
            ])
            ->filter(fn($p) => $p['details']->isNotEmpty())
            ->values();

        return Inertia::render('penerimaan-bahan-baku/create', ['pembelians' => $pembelians]);
    }

    public function store(Request $request)
    {
        // Authorization: Admin (R01), Staf Gudang (R02) dan Manajer Gudang (R07) yang bisa store
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk membuat penerimaan bahan baku baru.');
        }

        $validator = Validator::make($request->all(), [
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
            // Proses setiap item untuk Penerimaan
            foreach ($itemsToProcess as $itemData) {
                $pembelianDetail = PembelianDetail::with('pengadaanDetail')->findOrFail($itemData['pembelian_detail_id']);
                $pengadaanDetail = $pembelianDetail->pengadaanDetail;

                // Create penerimaan record
                PenerimaanBahanBaku::create([
                    'pembelian_detail_id' => $pembelianDetail->pembelian_detail_id,
                    'qty_diterima' => $itemData['qty_diterima'],
                ]);

                // Update stok bahan baku atau produk
                if ($pengadaanDetail->jenis_barang === 'bahan_baku') {
                    BahanBaku::find($pengadaanDetail->barang_id)->increment('stok_bahan', $itemData['qty_diterima']);
                } elseif ($pengadaanDetail->jenis_barang === 'produk') {
                    \App\Models\Produk::find($pengadaanDetail->barang_id)->increment('stok_produk', $itemData['qty_diterima']);
                }
            }

            // Update status Pembelian
            $pembelianId = PembelianDetail::find($itemsToProcess[0]['pembelian_detail_id'])->pembelian_id;
            $pembelian = Pembelian::with('detail')->find($pembelianId);
            $allReceived = $pembelian->detail->every(fn($detail) => $detail->isFullyReceived());
            $pembelian->status = $allReceived ? 'diterima' : 'dikirim';
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
            'pembelianDetail.pembelian.pemasok',
            'pembelianDetail.pengadaanDetail'
        ]);

        $pembelianDetail = $penerimaanBahanBaku->pembelianDetail;
        $pembelian = $pembelianDetail->pembelian;
        $pengadaanDetail = $pembelianDetail->pengadaanDetail;

        return Inertia::render('penerimaan-bahan-baku/show', [
            'penerimaan' => [
                'penerimaan_id' => $penerimaanBahanBaku->penerimaan_id,
                'pembelian_detail_id' => $penerimaanBahanBaku->pembelian_detail_id,
                'qty_diterima' => $penerimaanBahanBaku->qty_diterima,
                'pembelian' => [
                    'pembelian_id' => $pembelian?->pembelian_id ?? 'N/A',
                    'tanggal_pembelian' => $pembelian ? \Carbon\Carbon::parse($pembelian->tanggal_pembelian)->format('Y-m-d') : '-',
                    'pemasok_nama' => $pembelian?->pemasok?->nama_pemasok ?? '-',
                ],
                'item' => [
                    'nama_item' => $pengadaanDetail?->nama_item ?? '-',
                    'satuan' => $pengadaanDetail?->satuan ?? '-',
                    'qty_dipesan' => $pengadaanDetail?->qty_diminta ?? 0,
                    'harga_satuan' => $pengadaanDetail?->harga_satuan ?? 0,
                    'total_harga' => $pengadaanDetail?->total_harga ?? 0,
                ],
                'created_at' => $penerimaanBahanBaku->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $penerimaanBahanBaku->updated_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function getPembelianDetails(Pembelian $pembelian)
    {
        $pembelian->load('detail.pengadaanDetail');

        return response()->json($pembelian->detail->map(function ($detail) {
            $pengadaanDetail = $detail->pengadaanDetail;
            if (!$pengadaanDetail) return null;

            $qtyDiterima = $detail->penerimaanBahanBaku->sum('qty_diterima');
            $outstanding = $pengadaanDetail->qty_diminta - $qtyDiterima;  // FIX: qty → qty_diminta

            return [
                'pembelian_detail_id' => $detail->pembelian_detail_id,
                'nama_item' => $pengadaanDetail->nama_item,
                'satuan' => $pengadaanDetail->satuan,
                'qty_dipesan' => $pengadaanDetail->qty_diminta,  // FIX: qty → qty_diminta
                'qty_diterima_sebelumnya' => $qtyDiterima,
                'qty_sisa' => $outstanding,
            ];
        })->filter(fn($d) => $d && $d['qty_sisa'] > 0)->values());
    }
}
