<?php

namespace App\Http\Controllers;

use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Supplier;
use App\Models\BahanBaku;
use App\Models\Produk;
use App\Services\PengadaanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class PengadaanController extends Controller
{
    protected $pengadaanService;

    public function __construct(PengadaanService $pengadaanService)
    {
        $this->pengadaanService = $pengadaanService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pengadaan::with([
            'supplier:supplier_id,nama_supplier',
            'pesanan:pesanan_id',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pengadaan_id', 'like', "%{$search}%")
                    ->orWhere('nomor_po', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('nama_supplier', 'like', "%{$search}%");
                    });
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply jenis pengadaan filter
        if ($request->filled('jenis_pengadaan')) {
            $query->where('jenis_pengadaan', $request->jenis_pengadaan);
        }

        // Apply prioritas filter
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Apply pagination
        $perPage = $request->get('per_page', 10);
        $pengadaan = $query->paginate($perPage);

        // Transform data
        $pengadaan->getCollection()->transform(function ($item) {
            return [
                'pengadaan_id' => $item->pengadaan_id,
                'supplier_id' => $item->supplier_id,
                'jenis_pengadaan' => $item->jenis_pengadaan,
                'pesanan_id' => $item->pesanan_id,
                'tanggal_pengadaan' => $item->tanggal_pengadaan?->format('Y-m-d'),
                'tanggal_dibutuhkan' => $item->tanggal_dibutuhkan?->format('Y-m-d'),
                'tanggal_delivery' => $item->tanggal_delivery?->format('Y-m-d'),
                'total_biaya' => $item->total_biaya,
                'status' => $item->status,
                'status_label' => $this->getStatusLabel($item->status),
                'prioritas' => $item->prioritas,
                'prioritas_label' => $this->getPrioritasLabel($item->prioritas),
                'nomor_po' => $item->nomor_po,
                'alasan_pengadaan' => $item->alasan_pengadaan,
                'supplier' => $item->supplier ? [
                    'supplier_id' => $item->supplier->supplier_id,
                    'nama_supplier' => $item->supplier->nama_supplier,
                ] : null,
                'can_edit' => $item->canBeEdited(),
                'can_cancel' => $item->canBeCancelled(),
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $item->updated_at?->format('Y-m-d H:i:s'),
            ];
        });

        $filters = [
            'search' => $request->search,
            'status' => $request->status,
            'jenis_pengadaan' => $request->jenis_pengadaan,
            'prioritas' => $request->prioritas,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page' => (int) $perPage,
        ];

        return Inertia::render('pengadaan/index', [
            'pengadaan' => $pengadaan,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::active()
            ->select('supplier_id', 'nama_supplier', 'kontak_person', 'telepon')
            ->orderBy('nama_supplier')
            ->get();

        $bahanBaku = BahanBaku::select('bahan_baku_id', 'nama_bahan', 'satuan_bahan as satuan', 'harga_bahan as harga_per_unit', 'stok_bahan as stok_saat_ini', 'rop_bahan as reorder_point', 'eoq_bahan as eoq')
            ->orderBy('nama_bahan')
            ->get();

        $produk = Produk::select('produk_id', 'nama_produk', 'satuan_produk as satuan', 'hpp_produk as hpp', 'stok_produk as stok_saat_ini', 'rop_produk as reorder_point', 'eoq_produk as eoq')
            ->orderBy('nama_produk')
            ->get();

        return Inertia::render('pengadaan/create', [
            'suppliers' => $suppliers,
            'bahanBaku' => $bahanBaku,
            'produk' => $produk,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:supplier,supplier_id',
            'jenis_pengadaan' => 'required|in:pesanan,rop',
            'pesanan_id' => 'nullable|exists:pesanan,pesanan_id',
            'tanggal_pengadaan' => 'required|date',
            'tanggal_dibutuhkan' => 'required|date|after_or_equal:tanggal_pengadaan',
            'prioritas' => 'required|in:low,normal,high,urgent',
            'alasan_pengadaan' => 'nullable|string',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:bahan_baku,produk',
            'items.*.item_id' => 'required|string',
            'items.*.catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $pengadaan = Pengadaan::create($request->only([
            'supplier_id',
            'jenis_pengadaan',
            'pesanan_id',
            'tanggal_pengadaan',
            'tanggal_dibutuhkan',
            'prioritas',
            'alasan_pengadaan',
            'catatan'
        ]));

        // Create pengadaan details
        foreach ($request->items as $item) {
            $itemModel = null;
            $namaItem = '';
            $satuan = '';
            $harga = 0;
            $eoq = 1;

            if ($item['item_type'] === 'bahan_baku') {
                $itemModel = BahanBaku::find($item['item_id']);
                if ($itemModel) {
                    $namaItem = $itemModel->nama_bahan;
                    $satuan = $itemModel->satuan_bahan;
                    $harga = $itemModel->harga_bahan;
                    $eoq = $itemModel->eoq_bahan;
                }
            } elseif ($item['item_type'] === 'produk') {
                $itemModel = Produk::find($item['item_id']);
                if ($itemModel) {
                    $namaItem = $itemModel->nama_produk;
                    $satuan = $itemModel->satuan_produk;
                    $harga = $itemModel->hpp_produk;
                    $eoq = $itemModel->eoq_produk;
                }
            }

            PengadaanDetail::create([
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'item_type' => $item['item_type'],
                'item_id' => $item['item_id'],
                'nama_item' => $namaItem,
                'satuan' => $satuan,
                'qty_diminta' => $eoq, // Gunakan EOQ
                'harga_satuan' => $harga,
                'total_harga' => $eoq * $harga,
                'catatan' => $item['catatan'] ?? null,
            ]);
        }

        $pengadaan->updateTotalBiaya();

        return redirect()->route('pengadaan.index')
            ->with('flash', [
                'message' => 'Pengadaan berhasil dibuat!',
                'type' => 'success'
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Pengadaan $pengadaan)
    {
        $pengadaan->load([
            'supplier',
            'pesanan.pelanggan',
            'detail',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('pengadaan/show', [
            'pengadaan' => [
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'supplier_id' => $pengadaan->supplier_id,
                'jenis_pengadaan' => $pengadaan->jenis_pengadaan,
                'pesanan_id' => $pengadaan->pesanan_id,
                'tanggal_pengadaan' => $pengadaan->tanggal_pengadaan?->format('Y-m-d'),
                'tanggal_dibutuhkan' => $pengadaan->tanggal_dibutuhkan?->format('Y-m-d'),
                'tanggal_delivery' => $pengadaan->tanggal_delivery?->format('Y-m-d'),
                'total_biaya' => $pengadaan->total_biaya,
                'status' => $pengadaan->status,
                'status_label' => $this->getStatusLabel($pengadaan->status),
                'prioritas' => $pengadaan->prioritas,
                'prioritas_label' => $this->getPrioritasLabel($pengadaan->prioritas),
                'nomor_po' => $pengadaan->nomor_po,
                'alasan_pengadaan' => $pengadaan->alasan_pengadaan,
                'catatan' => $pengadaan->catatan,
                'supplier' => [
                    'supplier_id' => $pengadaan->supplier->supplier_id,
                    'nama_supplier' => $pengadaan->supplier->nama_supplier,
                    'kontak_person' => $pengadaan->supplier->kontak_person,
                    'telepon' => $pengadaan->supplier->telepon,
                    'email' => $pengadaan->supplier->email,
                ],
                'pesanan' => $pengadaan->pesanan ? [
                    'pesanan_id' => $pengadaan->pesanan->pesanan_id,
                    'tanggal_pemesanan' => $pengadaan->pesanan->tanggal_pemesanan,
                    'total_harga' => $pengadaan->pesanan->total_harga,
                    'pelanggan' => $pengadaan->pesanan->pelanggan ? [
                        'nama_pelanggan' => $pengadaan->pesanan->pelanggan->nama_pelanggan,
                    ] : null,
                ] : null,
                'detail' => $pengadaan->detail->map(function ($detail) {
                    return [
                        'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                        'item_type' => $detail->item_type,
                        'item_id' => $detail->item_id,
                        'nama_item' => $detail->nama_item,
                        'satuan' => $detail->satuan,
                        'qty_diminta' => $detail->qty_diminta,
                        'qty_disetujui' => $detail->qty_disetujui,
                        'qty_diterima' => $detail->qty_diterima,
                        'harga_satuan' => $detail->harga_satuan,
                        'total_harga' => $detail->total_harga,
                        'catatan' => $detail->catatan,
                        'outstanding_qty' => $detail->getOutstandingQty(),
                        'is_fully_received' => $detail->isFullyReceived(),
                    ];
                }),
                'can_edit' => $pengadaan->canBeEdited(),
                'can_cancel' => $pengadaan->canBeCancelled(),
                'created_at' => $pengadaan->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $pengadaan->updated_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pengadaan $pengadaan)
    {
        if (!$pengadaan->canBeEdited()) {
            return redirect()->route('pengadaan.index')
                ->with('flash', [
                    'message' => 'Pengadaan tidak dapat diedit karena statusnya sudah ' . $pengadaan->status,
                    'type' => 'error'
                ]);
        }

        $pengadaan->load('detail');

        $suppliers = Supplier::active()
            ->select('supplier_id', 'nama_supplier', 'kontak_person', 'telepon')
            ->orderBy('nama_supplier')
            ->get();

        return Inertia::render('pengadaan/edit', [
            'pengadaan' => [
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'supplier_id' => $pengadaan->supplier_id,
                'jenis_pengadaan' => $pengadaan->jenis_pengadaan,
                'pesanan_id' => $pengadaan->pesanan_id,
                'tanggal_pengadaan' => $pengadaan->tanggal_pengadaan?->format('Y-m-d'),
                'tanggal_dibutuhkan' => $pengadaan->tanggal_dibutuhkan?->format('Y-m-d'),
                'prioritas' => $pengadaan->prioritas,
                'alasan_pengadaan' => $pengadaan->alasan_pengadaan,
                'catatan' => $pengadaan->catatan,
                'detail' => $pengadaan->detail,
            ],
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pengadaan $pengadaan)
    {
        if (!$pengadaan->canBeEdited()) {
            return redirect()->route('pengadaan.index')
                ->with('flash', [
                    'message' => 'Pengadaan tidak dapat diedit karena statusnya sudah ' . $pengadaan->status,
                    'type' => 'error'
                ]);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:supplier,supplier_id',
            'tanggal_pengadaan' => 'required|date',
            'tanggal_dibutuhkan' => 'required|date|after_or_equal:tanggal_pengadaan',
            'prioritas' => 'required|in:low,normal,high,urgent',
            'alasan_pengadaan' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $pengadaan->update($request->only([
            'supplier_id',
            'tanggal_pengadaan',
            'tanggal_dibutuhkan',
            'prioritas',
            'alasan_pengadaan',
            'catatan'
        ]));

        return redirect()->route('pengadaan.index')
            ->with('flash', [
                'message' => 'Pengadaan berhasil diperbarui!',
                'type' => 'success'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pengadaan $pengadaan)
    {
        if (!$pengadaan->canBeCancelled()) {
            return redirect()->route('pengadaan.index')
                ->with('flash', [
                    'message' => 'Pengadaan tidak dapat dihapus karena statusnya sudah ' . $pengadaan->status,
                    'type' => 'error'
                ]);
        }

        $pengadaan->delete();

        return redirect()->route('pengadaan.index')
            ->with('flash', [
                'message' => 'Pengadaan berhasil dihapus!',
                'type' => 'success'
            ]);
    }

    /**
     * Update status pengadaan
     */
    public function updateStatus(Request $request, Pengadaan $pengadaan)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,pending,approved,ordered,partial_received,received,cancelled',
            'nomor_po' => 'nullable|string|max:255',
            'tanggal_delivery' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pengadaan->update($request->only(['status', 'nomor_po', 'tanggal_delivery']));

        return response()->json([
            'message' => 'Status pengadaan berhasil diperbarui!',
            'pengadaan' => $pengadaan->fresh()
        ]);
    }

    /**
     * Dashboard pengadaan
     */
    public function dashboard()
    {
        $belowROPItems = $this->pengadaanService->detectBelowROP();
        $recommendations = $this->pengadaanService->getProcurementRecommendations();

        $pengadaanSummary = [
            'total' => Pengadaan::count(),
            'draft' => Pengadaan::where('status', 'draft')->count(),
            'pending' => Pengadaan::where('status', 'pending')->count(),
            'approved' => Pengadaan::where('status', 'approved')->count(),
            'ordered' => Pengadaan::where('status', 'ordered')->count(),
            'received' => Pengadaan::where('status', 'received')->count(),
        ];

        $urgentPengadaan = Pengadaan::needingAttention()
            ->with('supplier:supplier_id,nama_supplier')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'pengadaan_id' => $item->pengadaan_id,
                    'tanggal_dibutuhkan' => $item->tanggal_dibutuhkan?->format('Y-m-d'),
                    'total_biaya' => $item->total_biaya,
                    'prioritas' => $item->prioritas,
                    'status' => $item->status,
                ];
            });

        // Transform below ROP items
        $belowROPFormatted = [];
        foreach ($belowROPItems['bahan_baku'] as $item) {
            $belowROPFormatted[] = [
                'id' => $item->bahan_baku_id,
                'nama' => $item->nama_bahan,
                'stok_saat_ini' => $item->stok_saat_ini,
                'reorder_point' => $item->reorder_point,
                'type' => 'bahan_baku'
            ];
        }
        foreach ($belowROPItems['produk'] as $item) {
            $belowROPFormatted[] = [
                'id' => $item->produk_id,
                'nama' => $item->nama_produk,
                'stok_saat_ini' => $item->stok_saat_ini,
                'reorder_point' => $item->reorder_point,
                'type' => 'produk'
            ];
        }

        return Inertia::render('pengadaan/dashboard', [
            'belowROPItems' => $belowROPFormatted,
            'recommendations' => $recommendations,
            'pengadaanSummary' => $pengadaanSummary,
            'urgentPengadaan' => $urgentPengadaan,
        ]);
    }

    /**
     * Auto generate ROP procurement
     */
    public function autoGenerateROP(Request $request)
    {
        $supplierId = $request->supplier_id ?? 'SUP0000001';

        $pengadaan = $this->pengadaanService->generateROPProcurement($supplierId);

        if (!$pengadaan) {
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Tidak ada item yang perlu diadakan berdasarkan ROP',
                    'type' => 'info'
                ]);
        }

        return redirect()->route('pengadaan.show', $pengadaan->pengadaan_id)
            ->with('flash', [
                'message' => 'Pengadaan otomatis berdasarkan ROP berhasil dibuat!',
                'type' => 'success'
            ]);
    }

    // Helper methods
    private function getStatusLabel($status)
    {
        return match ($status) {
            'draft' => 'Draft',
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'ordered' => 'Dipesan',
            'partial_received' => 'Diterima Sebagian',
            'received' => 'Diterima',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($status)
        };
    }

    private function getPrioritasLabel($prioritas)
    {
        return match ($prioritas) {
            'low' => 'Rendah',
            'normal' => 'Normal',
            'high' => 'Tinggi',
            'urgent' => 'Mendesak',
            default => ucfirst($prioritas)
        };
    }
}
