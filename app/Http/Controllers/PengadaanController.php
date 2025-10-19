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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        $query = Pengadaan::with([
            'pesanan:pesanan_id',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        // Filter berdasarkan role - tentukan status mana saja yang bisa dilihat
        // Urutan status workflow: draft -> disetujui_gudang -> disetujui_pengadaan -> disetujui_keuangan -> diproses -> diterima
        if (in_array($user->role_id, ['R02', 'R07'])) {
            // Staf/Manajer Gudang - lihat SEMUA status (karena mereka yang membuat)
        } elseif (in_array($user->role_id, ['R04', 'R09'])) {
            // Staf/Manajer Pengadaan - lihat dari "disetujui_gudang" ke atas
            $query->whereIn('status', [
                'disetujui_gudang',
                'disetujui_pengadaan',
                'disetujui_keuangan',
                'diproses',
                'diterima',
            ]);
        } elseif (in_array($user->role_id, ['R06', 'R10'])) {
            // Staf/Manajer Keuangan - lihat dari "disetujui_pengadaan" ke atas
            $query->whereIn('status', [
                'disetujui_pengadaan',
                'disetujui_keuangan',
                'diproses',
                'diterima',
            ]);
        }

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
            ->select('pemasok_id', 'nama_pemasok', 'narahubung', 'nomor_telepon')
            ->orderBy('nama_pemasok')
            ->get();

        // Add pesanan dropdown - ONLY show orders where products exceed stock
        $pesanan = Pesanan::with(['pelanggan:pelanggan_id,nama_pelanggan', 'detail.produk'])
            ->select('pesanan_id', 'pelanggan_id', 'tanggal_pemesanan', 'total_harga', 'status')
            ->whereIn('status', ['pending', 'confirmed', 'processing']) // Only active orders
            ->orderBy('tanggal_pemesanan', 'desc')
            ->get()
            ->filter(function ($item) {
                // Filter: Only include orders where at least one product qty exceeds stock
                $hasExceedingProduct = false;
                foreach ($item->detail as $detail) {
                    if ($detail->jumlah_produk > $detail->produk->stok_produk) {
                        $hasExceedingProduct = true;
                        break;
                    }
                }
                return $hasExceedingProduct;
            })
            ->map(function ($item) {
                return [
                    'pesanan_id' => $item->pesanan_id,
                    'pelanggan_id' => $item->pelanggan_id,
                    'pelanggan_nama' => $item->pelanggan->nama_pelanggan ?? 'Unknown',
                    'tanggal_pemesanan' => $item->tanggal_pemesanan,
                    'total_harga' => $item->total_harga,
                    'status' => $item->status,
                    'display_text' => $item->pesanan_id . ' - ' . ($item->pelanggan->nama_pelanggan ?? 'Unknown') . ' (' . date('d/m/Y', strtotime($item->tanggal_pemesanan)) . ')',
                    'produk' => $item->detail->map(function ($detail) {
                        $produk = $detail->produk;
                        return [
                            'produk_id' => $produk->produk_id,
                            'nama_produk' => $produk->nama_produk,
                            'jumlah_produk' => $detail->jumlah_produk,
                            'stok_produk' => $produk->stok_produk,
                            'eoq_produk' => $produk->eoq_produk,
                            'hpp_produk' => $produk->hpp_produk,
                            'satuan_produk' => $produk->satuan_produk,
                        ];
                    })
                ];
            })
            ->values(); // Re-index array after filter

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
            'pemasoks' => $pemasok,  // ✅ FIXED: Frontend expect 'pemasoks' plural
            'pesanan' => $pesanan,
            'bahanBaku' => $bahanBaku,
            'produk' => $produk,
        ]);
    }

    // Add this new method to get procurement calculation for a specific order
    public function calculateProcurement(Request $request)
    {
        $pesananId = $request->input('pesanan_id');
        $pesanan = Pesanan::with(['detail.produk.bahanBaku'])->findOrFail($pesananId);

        $procurementItems = [];
        $bahanBakuNeeded = [];

        // 1. Hitung kebutuhan produk
        foreach ($pesanan->detail as $detail) {
            $produk = $detail->produk;
            $jumlahDipesan = $detail->jumlah_produk;
            $stokSaatIni = $produk->stok_produk;
            $eoq = $produk->eoq_produk;

            if ($stokSaatIni < $jumlahDipesan) {
                $kekuranganProduk = $jumlahDipesan - $stokSaatIni;
                $procurementItems[] = [
                    'jenis_barang'    => 'produk',
                    'barang_id'       => $produk->produk_id,
                    'nama_item'       => $produk->nama_produk,
                    'satuan'          => $produk->satuan_produk,
                    'qty_needed'      => $kekuranganProduk,
                    'qty_diminta'     => $eoq + $kekuranganProduk,  // SOURCE OF TRUTH
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
                            'jenis_barang'     => 'bahan_baku',
                            'barang_id'        => $bahanBaku->bahan_baku_id,
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
                    'jenis_barang'    => 'bahan_baku',
                    'barang_id'       => $bahan['barang_id'],
                    'nama_item'       => $bahan['nama_item'],
                    'satuan'          => $bahan['satuan'],
                    'qty_needed'      => $kekurangan,
                    'qty_diminta'     => $qtyProcurement,  // SOURCE OF TRUTH
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
                    return $item['qty_diminta'] * $item['harga_satuan'];  // SOURCE OF TRUTH
                }, $procurementItems))
            ]
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Pengadaan Store - Request Data:', $request->all());

        // SOURCE OF TRUTH: Validation menggunakan field name dari database migration
        $validator = Validator::make($request->all(), [
            'pesanan_id'        => 'required|exists:pesanan,pesanan_id',
            'catatan'           => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.jenis_barang' => 'required|in:bahan_baku,produk',
            'items.*.barang_id'   => 'required|string',
            'items.*.qty_diminta' => 'required|numeric|min:1',
            'items.*.harga_satuan' => 'nullable|numeric|min:0',
            'items.*.catatan'   => 'nullable|string',
            'items.*.pemasok_id' => 'nullable|exists:pemasok,pemasok_id',
        ]);

        // Custom validation for barang_id based on jenis_barang
        $validator->after(function ($validator) use ($request) {
            if ($request->has('items')) {
                foreach ($request->items as $index => $item) {
                    if (isset($item['jenis_barang']) && isset($item['barang_id'])) {
                        if ($item['jenis_barang'] === 'bahan_baku') {
                            if (!BahanBaku::where('bahan_baku_id', $item['barang_id'])->exists()) {
                                $validator->errors()->add("items.{$index}.barang_id", "Bahan baku dengan ID {$item['barang_id']} tidak ditemukan.");
                            }
                        } elseif ($item['jenis_barang'] === 'produk') {
                            if (!Produk::where('produk_id', $item['barang_id'])->exists()) {
                                $validator->errors()->add("items.{$index}.barang_id", "Produk dengan ID {$item['barang_id']} tidak ditemukan.");
                            }
                        }
                    }
                }
            }
        });

        if ($validator->fails()) {
            Log::error('Pengadaan Store - Validation Failed:', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            Log::info('Pengadaan Store - Creating header');

            // Create pengadaan header
            $pengadaan = Pengadaan::create([
                'pesanan_id'     => $request->pesanan_id,
                'catatan'        => $request->catatan,
                'jenis_pengadaan' => 'pesanan',
            ]);

            Log::info('Pengadaan Store - Header created:', ['pengadaan_id' => $pengadaan->pengadaan_id]);

            // Create pengadaan details - SOURCE OF TRUTH: Langsung pakai field name dari request
            foreach ($request->items as $index => $item) {
                Log::info("Pengadaan Store - Creating detail {$index}:", $item);

                PengadaanDetail::create([
                    'pengadaan_id'  => $pengadaan->pengadaan_id,
                    'pemasok_id'    => $item['pemasok_id'] ?? null,
                    'jenis_barang'  => $item['jenis_barang'],
                    'barang_id'     => $item['barang_id'],
                    'qty_diminta'   => $item['qty_diminta'],
                    'harga_satuan'  => $item['harga_satuan'] ?? 0,
                    'catatan'       => $item['catatan'] ?? null,
                ]);
            }

            Log::info('Pengadaan Store - All details created, updating total');

            $pengadaan->updateTotalBiaya();

            Log::info('Pengadaan Store - Success!');

            return redirect()->route('pengadaan.index')
                ->with('flash', [
                    'message' => 'Pengadaan berhasil dibuat!',
                    'type'    => 'success'
                ]);
        } catch (\Exception $e) {
            Log::error('Pengadaan Store - Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Gagal membuat pengadaan: ' . $e->getMessage()])
                ->withInput();
        }
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
                'total_biaya'       => $pengadaan->total_biaya,
                'status'            => $pengadaan->status,
                'status_label'      => $this->getStatusLabel($pengadaan->status),
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
                        'pemasok_id'          => $detail->pemasok_id,
                        'nama_pemasok'        => $detail->pemasok?->nama_pemasok,
                        'jenis_barang'        => $detail->jenis_barang,
                        'barang_id'           => $detail->barang_id,
                        'nama_item'           => $detail->nama_item,
                        'satuan'              => $detail->satuan,
                        'qty_diminta'         => $detail->qty_diminta,
                        'qty_disetujui'       => $detail->qty_disetujui,
                        'qty_diterima'        => $detail->qty_diterima,
                        'harga_satuan'        => $detail->harga_satuan,
                        'total_harga'         => $detail->total_harga,
                        'catatan'             => $detail->catatan,
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

        // Status options untuk update status di halaman edit (SOURCE OF TRUTH: migration)
        $statusOptions = [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'disetujui_gudang', 'label' => 'Disetujui Gudang'],
            ['value' => 'disetujui_pengadaan', 'label' => 'Disetujui Pengadaan'],
            ['value' => 'disetujui_keuangan', 'label' => 'Disetujui Keuangan'],
            ['value' => 'diproses', 'label' => 'Diproses'],
            ['value' => 'diterima', 'label' => 'Diterima'],
            ['value' => 'dibatalkan', 'label' => 'Dibatalkan'],
        ];

        return Inertia::render('pengadaan/edit', [
            'pengadaan' => [
                'pengadaan_id'      => $pengadaan->pengadaan_id,
                'jenis_pengadaan'   => $pengadaan->jenis_pengadaan,
                'pesanan_id'        => $pengadaan->pesanan_id,
                'status'            => $pengadaan->status,
                'catatan'           => $pengadaan->catatan,
                'total_biaya'       => $pengadaan->total_biaya,
                'detail'            => $pengadaan->detail->map(function ($detail) {
                    return [
                        'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                        'pemasok_id'          => $detail->pemasok_id,
                        'jenis_barang'        => $detail->jenis_barang,
                        'barang_id'           => $detail->barang_id,
                        'nama_item'           => $detail->nama_item,
                        'satuan'              => $detail->satuan,
                        'qty_diminta'         => $detail->qty_diminta,
                        'harga_satuan'        => $detail->harga_satuan,
                        'catatan'             => $detail->catatan,
                    ];
                }),
            ],
            'pemasoks' => $pemasok,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pengadaan $pengadaan)
    {
        $user = Auth::user();

        if (!$pengadaan->canBeEdited()) {
            return redirect()->route('pengadaan.index')
                ->with('flash', [
                    'message' => 'Pengadaan tidak dapat diedit karena statusnya sudah ' . $pengadaan->status,
                    'type' => 'error'
                ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:draft,disetujui_gudang,disetujui_pengadaan,disetujui_keuangan,diproses,diterima,dibatalkan',
            'catatan' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.pengadaan_detail_id' => 'required|exists:pengadaan_detail,pengadaan_detail_id',
            'details.*.pemasok_id' => 'nullable|exists:pemasok,pemasok_id',
            'details.*.harga_satuan' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validasi status transition dan role permission
        if ($request->has('status') && $request->status !== $pengadaan->status) {
            // Cek apakah status transition valid
            if (!$pengadaan->isValidStatusTransition($request->status)) {
                return redirect()->back()
                    ->with('flash', [
                        'message' => 'Perubahan status dari "' . $pengadaan->status . '" ke "' . $request->status . '" tidak diperbolehkan.',
                        'type' => 'error'
                    ])
                    ->withInput();
            }

            // Cek role permission untuk status tertentu
            // Only Manajer Gudang (R07) bisa approve ke disetujui_gudang
            if ($request->status === 'disetujui_gudang' && $user->role_id !== 'R07') {
                return redirect()->back()
                    ->with('flash', [
                        'message' => 'Hanya Manajer Gudang yang bisa menyetujui pengadaan di tahap ini.',
                        'type' => 'error'
                    ])
                    ->withInput();
            }

            // Only Manajer Pengadaan (R09) bisa approve ke disetujui_pengadaan
            if ($request->status === 'disetujui_pengadaan' && $user->role_id !== 'R09') {
                return redirect()->back()
                    ->with('flash', [
                        'message' => 'Hanya Manajer Pengadaan yang bisa menyetujui pengadaan di tahap ini.',
                        'type' => 'error'
                    ])
                    ->withInput();
            }

            // Only Manajer Keuangan (R10) bisa approve ke disetujui_keuangan
            if ($request->status === 'disetujui_keuangan' && $user->role_id !== 'R10') {
                return redirect()->back()
                    ->with('flash', [
                        'message' => 'Hanya Manajer Keuangan yang bisa menyetujui pengadaan di tahap ini.',
                        'type' => 'error'
                    ])
                    ->withInput();
            }
        }

        // Update pengadaan data (termasuk status jika ada)
        $updateData = ['catatan' => $request->catatan];
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }
        $pengadaan->update($updateData);

        // Update each detail's pemasok_id dan harga_satuan
        foreach ($request->details as $detailData) {
            $updateDetailData = ['pemasok_id' => $detailData['pemasok_id']];

            // Hanya update harga jika masih draft atau disetujui_gudang (sebelum approval dari Pengadaan)
            $canEditPrice = in_array($pengadaan->status, ['draft', 'disetujui_gudang']);
            if ($canEditPrice && isset($detailData['harga_satuan'])) {
                $updateDetailData['harga_satuan'] = $detailData['harga_satuan'];
            }

            PengadaanDetail::where('pengadaan_detail_id', $detailData['pengadaan_detail_id'])
                ->update($updateDetailData);
        }

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
            'disetujui_gudang' => 'Disetujui Gudang',
            'disetujui_pengadaan' => 'Disetujui Pengadaan',
            'disetujui_keuangan' => 'Disetujui Keuangan',
            'diproses' => 'Diproses',
            'diterima' => 'Diterima',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($status)
        };
    }
}
