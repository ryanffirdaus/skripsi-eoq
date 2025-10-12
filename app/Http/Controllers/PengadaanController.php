<?php

namespace App\Http\Controllers;

use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Pemasok;
use App\Models\BahanBaku;
use App\Models\Pesanan;
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
            'pesanan:pesanan_id',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pengadaan_id', 'like', "%{$search}%")
                    ->orWhere('nomor_po', 'like', "%{$search}%");
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
                'jenis_pengadaan' => $item->jenis_pengadaan,
                'pesanan_id' => $item->pesanan_id,
                'tanggal_pengadaan' => $item->tanggal_pengadaan?->format('Y-m-d'),
                'tanggal_delivery' => $item->tanggal_delivery?->format('Y-m-d'),
                'total_biaya' => $item->total_biaya,
                'status' => $item->status,
                'status_label' => $this->getStatusLabel($item->status),
                'nomor_po' => $item->nomor_po,
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
        $pemasok = Pemasok::active()
            ->select('pemasok_id', 'nama_pemasok', 'narahubung', 'telepon')
            ->orderBy('nama_pemasok')
            ->get();

        // Add pesanan dropdown - get orders that might need procurement
        $pesanan = Pesanan::with(['pelanggan:pelanggan_id,nama_pelanggan', 'produk'])
            ->select('pesanan_id', 'pelanggan_id', 'tanggal_pemesanan', 'total_harga', 'status')
            ->whereIn('status', ['pending', 'confirmed', 'processing']) // Only active orders
            ->orderBy('tanggal_pemesanan', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'pesanan_id' => $item->pesanan_id,
                    'pelanggan_id' => $item->pelanggan_id,
                    'pelanggan_nama' => $item->pelanggan->nama_pelanggan ?? 'Unknown',
                    'tanggal_pemesanan' => $item->tanggal_pemesanan,
                    'total_harga' => $item->total_harga,
                    'status' => $item->status,
                    'display_text' => $item->pesanan_id . ' - ' . ($item->pelanggan->nama_pelanggan ?? 'Unknown') . ' (' . date('d/m/Y', strtotime($item->tanggal_pemesanan)) . ')',
                    'produk' => $item->produk->map(function ($produk) {
                        return [
                            'produk_id' => $produk->produk_id,
                            'nama_produk' => $produk->nama_produk,
                            'jumlah_produk' => $produk->pivot->jumlah_produk,
                            'stok_produk' => $produk->stok_produk,
                            'eoq_produk' => $produk->eoq_produk,
                            'hpp_produk' => $produk->hpp_produk,
                            'satuan_produk' => $produk->satuan_produk,
                        ];
                    })
                ];
            });

        $bahanBaku = BahanBaku::select('bahan_baku_id', 'nama_bahan', 'satuan_bahan as satuan', 'harga_bahan as harga_per_unit', 'stok_bahan as stok_saat_ini', 'rop_bahan as reorder_point', 'eoq_bahan as eoq')
            ->orderBy('nama_bahan')
            ->get();

        $produk = Produk::with('bahanBaku')
            ->select('produk_id', 'nama_produk', 'satuan_produk as satuan', 'hpp_produk as hpp', 'stok_produk as stok_saat_ini', 'rop_produk as reorder_point', 'eoq_produk as eoq')
            ->orderBy('nama_produk')
            ->get()
            ->map(function ($item) {
                return [
                    'produk_id' => $item->produk_id,
                    'nama_produk' => $item->nama_produk,
                    'satuan' => $item->satuan,
                    'hpp' => $item->hpp,
                    'stok_saat_ini' => $item->stok_saat_ini,
                    'reorder_point' => $item->reorder_point,
                    'eoq' => $item->eoq,
                    'bahan_baku' => $item->bahanBaku->map(function ($bahan) {
                        return [
                            'bahan_baku_id' => $bahan->bahan_baku_id,
                            'nama_bahan' => $bahan->nama_bahan,
                            'jumlah_bahan_baku' => $bahan->pivot->jumlah_bahan_baku,
                            'stok_bahan' => $bahan->stok_bahan,
                            'satuan_bahan' => $bahan->satuan_bahan,
                            'harga_bahan' => $bahan->harga_bahan,
                            'eoq_bahan' => $bahan->eoq_bahan,
                            'rop_bahan' => $bahan->rop_bahan,
                        ];
                    })
                ];
            });

        return Inertia::render('pengadaan/create', [
            'pemasok' => $pemasok,
            'pesanan' => $pesanan,
            'bahanBaku' => $bahanBaku,
            'produk' => $produk,
        ]);
    }

    // Add this new method to get procurement calculation for a specific order
    public function calculateProcurement(Request $request)
    {
        $pesananId = $request->input('pesanan_id');
        $pesanan = Pesanan::with(['produk.bahanBaku'])->findOrFail($pesananId);

        $procurementItems = [];
        $bahanBakuNeeded = [];

        // 1. Hitung kebutuhan produk
        foreach ($pesanan->produk as $produk) {
            $jumlahDipesan = $produk->pivot->jumlah_produk;
            $stokSaatIni = $produk->stok_produk;
            $eoq = $produk->eoq_produk;

            if ($stokSaatIni < $jumlahDipesan) {
                $kekuranganProduk = $jumlahDipesan - $stokSaatIni;
                $procurementItems[] = [
                    'item_type'       => 'produk',
                    'item_id'         => $produk->produk_id,
                    'nama_item'       => $produk->nama_produk,
                    'satuan'          => $produk->satuan_produk,
                    'qty_needed'      => $kekuranganProduk,
                    'qty_procurement' => $eoq + $kekuranganProduk,
                    'harga_satuan'    => $produk->hpp_produk,
                    'catatan'         => "Produk dipesan: {$jumlahDipesan}, Stok: {$stokSaatIni}, Kekurangan: {$kekuranganProduk}"
                ];
            }

            // 2. Agregasi kebutuhan bahan baku dari semua produk dalam pesanan
            $totalProduksiDiperlukan = max(0, $jumlahDipesan - $stokSaatIni);

            if ($totalProduksiDiperlukan > 0) {
                foreach ($produk->bahanBaku as $bahanBaku) {
                    $jumlahBahanPerProduk = $bahanBaku->pivot->jumlah_bahan_baku;
                    $totalBahanDiperlukan = $totalProduksiDiperlukan * $jumlahBahanPerProduk;
                    $bahanBakuId = $bahanBaku->bahan_baku_id;

                    if (!isset($bahanBakuNeeded[$bahanBakuId])) {
                        $bahanBakuNeeded[$bahanBakuId] = [
                            'item_type'        => 'bahan_baku',
                            'item_id'          => $bahanBaku->bahan_baku_id,
                            'nama_item'        => $bahanBaku->nama_bahan,
                            // PERBAIKAN: Gunakan nama properti yang benar
                            'satuan'           => $bahanBaku->satuan,
                            'stok_saat_ini'    => $bahanBaku->stok_saat_ini,
                            'harga_satuan'     => $bahanBaku->harga_per_unit,
                            'eoq'              => $bahanBaku->eoq,
                            'rop'              => $bahanBaku->reorder_point,
                            // Akhir Perbaikan
                            'total_needed'     => 0,
                            'detail_kebutuhan' => []
                        ];
                    }

                    $bahanBakuNeeded[$bahanBakuId]['total_needed'] += $totalBahanDiperlukan;
                    $bahanBakuNeeded[$bahanBakuId]['detail_kebutuhan'][] = [
                        'produk'                    => $produk->nama_produk,
                        'jumlah_produksi'           => $totalProduksiDiperlukan,
                        'jumlah_bahan_per_produk'   => $jumlahBahanPerProduk,
                        'total_bahan'               => $totalBahanDiperlukan
                    ];
                }
            }
        }

        // 3. Proses kebutuhan bahan baku yang sudah diagregasi
        foreach ($bahanBakuNeeded as $bahan) {
            $stokSaatIni = $bahan['stok_saat_ini'];
            $totalDiperlukan = $bahan['total_needed'];
            $eoq = $bahan['eoq'];

            if ($stokSaatIni < $totalDiperlukan) {
                $kekurangan = $totalDiperlukan - $stokSaatIni;
                $qtyProcurement = $eoq + $kekurangan;

                $detailCatatan = "Total diperlukan: {$totalDiperlukan}, Stok: {$stokSaatIni}, Kekurangan: {$kekurangan}\n";
                foreach ($bahan['detail_kebutuhan'] as $detail) {
                    if ($detail['total_bahan'] > 0) {
                        $detailCatatan .= "- Utk '{$detail['produk']}': {$detail['jumlah_produksi']} x {$detail['jumlah_bahan_per_produk']} = {$detail['total_bahan']}\n";
                    }
                }

                $procurementItems[] = [
                    'item_type'       => 'bahan_baku',
                    'item_id'         => $bahan['item_id'],
                    'nama_item'       => $bahan['nama_item'],
                    'satuan'          => $bahan['satuan'],
                    'qty_needed'      => $kekurangan,
                    'qty_procurement' => $qtyProcurement,
                    'harga_satuan'    => $bahan['harga_satuan'],
                    'catatan'         => trim($detailCatatan)
                ];
            }
        }

        // 4. Kembalikan response
        return response()->json([
            'success' => true,
            'items'   => $procurementItems,
            'summary' => [
                'total_items' => count($procurementItems),
                'total_cost'  => array_sum(array_map(function ($item) {
                    return $item['qty_procurement'] * $item['harga_satuan'];
                }, $procurementItems))
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pesanan_id'        => 'required|exists:pesanan,pesanan_id',
            'tanggal_pengadaan' => 'required|date',
            'catatan'           => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.item_type' => 'required|in:bahan_baku,produk',
            'items.*.item_id'   => 'required|string',
            'items.*.qty_procurement' => 'required|numeric|min:1',
            'items.*.catatan'   => 'nullable|string',
            'items.*.pemasok_id' => 'nullable|exists:pemasok,pemasok_id',
        ]);


        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // --- MODIFIED PENGADAAN CREATION ---
        // Removed 'pemasok_id' from the main procurement record.
        $pengadaan = Pengadaan::create([
            'pesanan_id'        => $request->pesanan_id,
            'tanggal_pengadaan' => $request->tanggal_pengadaan,
            'catatan'           => $request->catatan,
            'jenis_pengadaan'   => 'pesanan',
        ]);

        // --- MODIFIED PENGADAAN DETAIL CREATION ---
        foreach ($request->items as $item) {
            $itemModel = null;
            $namaItem = '';
            $satuan = '';
            $harga = 0;
            $qtyProcurement = $item['qty_procurement'];

            if ($item['item_type'] === 'bahan_baku') {
                $itemModel = BahanBaku::find($item['item_id']);
                if ($itemModel) {
                    $namaItem = $itemModel->nama_bahan;
                    $satuan = $itemModel->satuan_bahan;
                    $harga = $itemModel->harga_bahan;
                }
            } elseif ($item['item_type'] === 'produk') {
                $itemModel = Produk::find($item['item_id']);
                if ($itemModel) {
                    $namaItem = $itemModel->nama_produk;
                    $satuan = $itemModel->satuan_produk;
                    $harga = $itemModel->hpp_produk;
                }
            }

            // 1. Added 'pemasok_id' to PengadaanDetail.
            // 2. Set pemasok_id only if item_type is 'bahan_baku', otherwise it's null.
            PengadaanDetail::create([
                'pengadaan_id'  => $pengadaan->pengadaan_id,
                'pemasok_id'   => $item['pemasok_id'] ?? null,
                'item_type'     => $item['item_type'],
                'item_id'       => $item['item_id'],
                'nama_item'     => $namaItem,
                'satuan'        => $satuan,
                'qty_diminta'   => $qtyProcurement,
                'harga_satuan'  => $harga,
                'total_harga'   => $qtyProcurement * $harga,
                'catatan'       => $item['catatan'] ?? null,
            ]);
        }

        $pengadaan->updateTotalBiaya();

        return redirect()->route('pengadaan.index')
            ->with('flash', [
                'message' => 'Pengadaan berhasil dibuat!',
                'type'    => 'success'
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Pengadaan $pengadaan)
    {
        // --- MODIFIED DATA LOADING ---
        // Changed load('pemasok') to load('detail.pemasok') to get the pemasok for each detail item.
        $pengadaan->load([
            'pesanan.pelanggan',
            'detail.pemasok', // Eager load pemasok on each detail
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('pengadaan/show', [
            'pengadaan' => [
                'pengadaan_id'      => $pengadaan->pengadaan_id,
                'jenis_pengadaan'   => $pengadaan->jenis_pengadaan,
                'pesanan_id'        => $pengadaan->pesanan_id,
                'tanggal_pengadaan' => $pengadaan->tanggal_pengadaan?->format('Y-m-d'),
                'tanggal_delivery'  => $pengadaan->tanggal_delivery?->format('Y-m-d'),
                'total_biaya'       => $pengadaan->total_biaya,
                'status'            => $pengadaan->status,
                'status_label'      => $this->getStatusLabel($pengadaan->status),
                'nomor_po'          => $pengadaan->nomor_po,
                'catatan'           => $pengadaan->catatan,
                // Main 'pemasok' object is removed from here
                'pesanan'           => $pengadaan->pesanan ? [
                    'pesanan_id'        => $pengadaan->pesanan->pesanan_id,
                    'tanggal_pemesanan' => $pengadaan->pesanan->tanggal_pemesanan,
                    'total_harga'       => $pengadaan->pesanan->total_harga,
                    'pelanggan'         => $pengadaan->pesanan->pelanggan ? [
                        'nama_pelanggan' => $pengadaan->pesanan->pelanggan->nama_pelanggan,
                    ] : null,
                ] : null,
                // --- MODIFIED DETAIL MAPPING ---
                // Added pemasok details to each item in the detail array.
                'detail'            => $pengadaan->detail->map(function ($detail) {
                    return [
                        'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                        'pemasok'             => $detail->pemasok ? [ // Include pemasok info if it exists
                            'pemasok_id'    => $detail->pemasok->pemasok_id,
                            'nama_pemasok'  => $detail->pemasok->nama_pemasok,
                        ] : null,
                        'item_type'           => $detail->item_type,
                        'item_id'             => $detail->item_id,
                        'nama_item'           => $detail->nama_item,
                        'satuan'              => $detail->satuan,
                        'qty_diminta'         => $detail->qty_diminta,
                        'qty_disetujui'       => $detail->qty_disetujui,
                        'qty_diterima'        => $detail->qty_diterima,
                        'harga_satuan'        => $detail->harga_satuan,
                        'total_harga'         => $detail->total_harga,
                        'catatan'             => $detail->catatan,
                        'outstanding_qty'     => $detail->getOutstandingQty(),
                        'is_fully_received'   => $detail->isFullyReceived(),
                    ];
                }),
                'can_edit'          => $pengadaan->canBeEdited(),
                'can_cancel'        => $pengadaan->canBeCancelled(),
                'created_at'        => $pengadaan->created_at?->format('Y-m-d H:i:s'),
                'updated_at'        => $pengadaan->updated_at?->format('Y-m-d H:i:s'),
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

        // FIX: Menggunakan nested eager loading untuk memuat pemasok di dalam setiap detail.
        $pengadaan->load('detail.pemasok');

        $pemasok = Pemasok::active()
            ->select('pemasok_id', 'nama_pemasok') // Hanya pilih kolom yang dibutuhkan frontend
            ->orderBy('nama_pemasok')
            ->get();

        // Strukturnya sudah benar, tidak perlu diubah karena Laravel akan
        // secara otomatis menyertakan objek 'pemasok' di dalam setiap 'detail'.
        return Inertia::render('pengadaan/edit', [
            'pengadaan' => [
                'pengadaan_id'      => $pengadaan->pengadaan_id,
                'jenis_pengadaan'   => $pengadaan->jenis_pengadaan,
                'pesanan_id'        => $pengadaan->pesanan_id,
                'tanggal_pengadaan' => $pengadaan->tanggal_pengadaan?->format('Y-m-d'),
                'catatan'           => $pengadaan->catatan,
                'detail'            => $pengadaan->detail,
            ],
            'pemasok' => $pemasok,
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
            'pemasok_id' => 'required|exists:pemasok,pemasok_id',
            'tanggal_pengadaan' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $pengadaan->update($request->only([
            'pemasok_id',
            'tanggal_pengadaan',
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
            'status' => 'required|in:pending,disetujui_procurement,disetujui_finance,diproses,diterima,dibatalkan',
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
            ->with('pemasok:pemasok_id,nama_pemasok')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'pengadaan_id' => $item->pengadaan_id,
                    'total_biaya' => $item->total_biaya,
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
        $pemasokId = $request->pemasok_id ?? 'SUP0000001';

        $pengadaan = $this->pengadaanService->generateROPProcurement($pemasokId);

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
            'procurement_approved' => 'Disetujui',
            'finance_approved' => 'Disetujui',
            'ordered' => 'Dipesan',
            'partial_received' => 'Diterima Sebagian',
            'received' => 'Diterima',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($status)
        };
    }
}
