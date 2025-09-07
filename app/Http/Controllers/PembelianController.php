<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PembelianController extends Controller
{
    /**
     * Display a listing of the resource.
     * Menampilkan daftar semua Purchase Order.
     */
    public function index(Request $request)
    {
        $query = Pembelian::with([
            'supplier:supplier_id,nama_supplier',
            'pengadaan:pengadaan_id,jenis_pengadaan',
            'createdBy:user_id,nama_lengkap',
        ]);

        // Terapkan filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pembelian_id', 'like', "%{$search}%")
                    ->orWhere('nomor_po', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($subq) use ($search) {
                        $subq->where('nama_supplier', 'like', "%{$search}%");
                    });
            });
        }

        // Terapkan filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Terapkan filter supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Terapkan sorting
        $sortBy = $request->get('sort_by', 'tanggal_pembelian');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Terapkan paginasi
        $perPage = $request->get('per_page', 10);
        $pembelian = $query->paginate($perPage);

        // Transformasi data untuk frontend
        $pembelian->getCollection()->transform(function ($item) {
            return [
                'pembelian_id'      => $item->pembelian_id,
                'nomor_po'          => $item->nomor_po,
                'pengadaan_id'      => $item->pengadaan_id,
                'supplier_nama'     => $item->supplier->nama_supplier ?? 'N/A',
                'tanggal_pembelian' => $item->tanggal_pembelian?->format('d M Y'),
                'tanggal_kirim'     => $item->tanggal_kirim?->format('d M Y'),
                'total_biaya'       => (float) $item->total_biaya,
                'status'            => $item->status,
                'status_label'      => $this->getStatusLabel($item->status),
                'dibuat_oleh'       => $item->createdBy->nama_lengkap ?? 'N/A',
                'can_edit'          => $item->canBeEdited(),
                'can_cancel'        => $item->canBeCancelled(),
                'created_at'        => $item->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        // Data untuk filter di frontend
        $suppliers = Supplier::select('supplier_id', 'nama_supplier')->orderBy('nama_supplier')->get();

        $filters = [
            'search'         => $request->search,
            'status'         => $request->status,
            'supplier_id'    => $request->supplier_id,
            'sort_by'        => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page'       => (int) $perPage,
        ];

        return Inertia::render('pembelian/index', [
            'pembelian' => $pembelian,
            'filters'   => $filters,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Show the form for creating a new resource from a Pengadaan.
     * Halaman ini untuk generate PO dari permintaan pengadaan yang sudah disetujui.
     */
    public function create()
    {
        // 1. Ambil data Pengadaan yang sudah disetujui keuangan dan belum diproses menjadi PO.
        $pengadaans = Pengadaan::where('status', 'finance_approved')
            ->with(['detail.supplier:supplier_id,nama_supplier'])
            ->orderBy('tanggal_pengadaan', 'desc')
            ->get()
            ->map(function ($pengadaan) {
                // Hanya sertakan pengadaan yang memiliki detail item
                if ($pengadaan->detail->isEmpty()) {
                    return null;
                }
                return [
                    'pengadaan_id' => $pengadaan->pengadaan_id,
                    'display_text' => $pengadaan->pengadaan_id . ' (' . $pengadaan->tanggal_pengadaan->format('d M Y') . ')',
                    'detail' => $pengadaan->detail->map(function ($detail) {
                        return [
                            'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                            'supplier_id' => $detail->supplier_id,
                            'supplier_nama' => $detail->supplier->nama_supplier ?? 'N/A',
                            'item_type' => $detail->item_type,
                            'item_id' => $detail->item_id,
                            'nama_item' => $detail->nama_item,
                            'satuan' => $detail->satuan,
                            'qty_disetujui' => $detail->qty_disetujui,
                            'harga_satuan' => $detail->harga_satuan,
                        ];
                    }),
                ];
            })->filter()->values(); // Hapus null dari koleksi dan re-index

        // 2. Ambil semua supplier untuk data dropdown.
        $suppliers = Supplier::select(['supplier_id', 'nama_supplier'])->orderBy('nama_supplier')->get();

        // 3. Render komponen Inertia dengan data yang dibutuhkan.
        return Inertia::render('pembelian/create', [
            'pengadaans' => $pengadaans,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $validator = Validator::make($request->all(), [
            'pengadaan_id' => 'required|exists:pengadaan,pengadaan_id',
            'supplier_id' => 'required|exists:supplier,supplier_id',
            'tanggal_pembelian' => 'required|date',
            'nomor_po' => 'nullable|string|max:50|unique:pembelian,nomor_po',
            'tanggal_kirim_diharapkan' => 'nullable|date|after_or_equal:tanggal_pembelian',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.pengadaan_detail_id' => 'required|exists:pengadaan_detail,pengadaan_detail_id',
            'items.*.qty_dipesan' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // 2. Gunakan DB Transaction untuk memastikan integritas data
        DB::beginTransaction();
        try {
            // 3. Buat header data Pembelian (Purchase Order)
            $pembelian = Pembelian::create([
                'pengadaan_id' => $request->pengadaan_id,
                'supplier_id' => $request->supplier_id,
                'nomor_po' => $request->nomor_po, // Model akan generate otomatis jika kosong
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'tanggal_kirim_diharapkan' => $request->tanggal_kirim_diharapkan,
                'catatan' => $request->catatan,
                'status' => 'draft', // Status awal untuk PO baru
            ]);

            // 4. Simpan setiap item ke dalam detail pembelian
            foreach ($request->items as $item) {
                PembelianDetail::create([
                    'pembelian_id' => $pembelian->pembelian_id,
                    'pengadaan_detail_id' => $item['pengadaan_detail_id'],
                    'item_type' => $item['item_type'],
                    'item_id' => $item['item_id'],
                    'nama_item' => $item['nama_item'],
                    'satuan' => $item['satuan'],
                    'qty_dipesan' => $item['qty_dipesan'],
                    'harga_satuan' => $item['harga_satuan'],
                    'total_harga' => $item['qty_dipesan'] * $item['harga_satuan'],
                ]);
            }

            // 5. Update status Pengadaan menjadi 'ordered'
            $pengadaan = Pengadaan::find($request->pengadaan_id);
            if ($pengadaan) {
                $pengadaan->status = 'ordered';
                $pengadaan->save();
            }

            DB::commit();

            return redirect()->route('pembelian.index')->with('flash', [
                'message' => 'Purchase Order (PO) berhasil dibuat.',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('flash', [
                'message' => 'Terjadi kesalahan saat membuat PO: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Display the specified resource.
     * Menampilkan detail dari satu Purchase Order.
     */
    public function show(Pembelian $pembelian)
    {
        $pembelian->load([
            'supplier',
            'pengadaan:pengadaan_id,pesanan_id',
            'detail.pengadaanDetail',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('pembelian/show', [
            'pembelian' => [
                'pembelian_id' => $pembelian->pembelian_id,
                'nomor_po' => $pembelian->nomor_po,
                'pengadaan_id' => $pembelian->pengadaan_id,
                'supplier' => $pembelian->supplier,
                'tanggal_pembelian' => $pembelian->tanggal_pembelian?->format('Y-m-d'),
                'tanggal_kirim_diharapkan' => $pembelian->tanggal_kirim_diharapkan?->format('Y-m-d'),
                'total_biaya' => $pembelian->total_biaya,
                'status' => $pembelian->status,
                'status_label' => $this->getStatusLabel($pembelian->status),
                'catatan' => $pembelian->catatan,
                'created_by' => $pembelian->createdBy,
                'updated_by' => $pembelian->updatedBy,
                'created_at' => $pembelian->created_at?->format('d-m-Y H:i'),
                'updated_at' => $pembelian->updated_at?->format('d-m-Y H:i'),
                'can_edit' => $pembelian->canBeEdited(),
                'can_cancel' => $pembelian->canBeCancelled(),
                'detail' => $pembelian->detail->map(function ($detail) {
                    return [
                        'pembelian_detail_id' => $detail->pembelian_detail_id,
                        'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                        'item_type' => $detail->item_type,
                        'item_id' => $detail->item_id,
                        'nama_item' => $detail->nama_item,
                        'satuan' => $detail->satuan,
                        'qty_dipesan' => $detail->qty_dipesan,
                        'qty_diterima' => $detail->qty_diterima,
                        'harga_satuan' => $detail->harga_satuan,
                        'total_harga' => $detail->total_harga,
                        'outstanding_qty' => $detail->getOutstandingQty(),
                        'is_fully_received' => $detail->isFullyReceived(),
                    ];
                }),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pembelian $pembelian)
    {
        // 1. Eager load relasi yang dibutuhkan
        $pembelian->load(['supplier', 'detail']);

        // 2. Ambil semua supplier untuk dropdown
        $suppliers = Supplier::select('supplier_id', 'nama_supplier')->orderBy('nama_supplier')->get();

        // 3. Format data untuk dikirim ke frontend
        $pembelianData = [
            'pembelian_id' => $pembelian->pembelian_id,
            'pengadaan_id' => $pembelian->pengadaan_id,
            'nomor_po' => $pembelian->nomor_po,
            'supplier_id' => $pembelian->supplier_id,
            'tanggal_pembelian' => $pembelian->tanggal_pembelian->format('Y-m-d'),
            'tanggal_kirim_diharapkan' => $pembelian->tanggal_kirim_diharapkan?->format('Y-m-d'),
            'total_biaya' => $pembelian->total_biaya,
            'status' => $pembelian->status,
            'catatan' => $pembelian->catatan,
            'can_be_edited' => $pembelian->canBeEdited(),
            'detail' => $pembelian->detail->map(function ($item) {
                return [
                    'pembelian_detail_id' => $item->pembelian_detail_id,
                    'nama_item' => $item->nama_item,
                    'satuan' => $item->satuan,
                    'qty_dipesan' => $item->qty_dipesan,
                    'harga_satuan' => $item->harga_satuan,
                ];
            }),
        ];

        return Inertia::render('pembelian/edit', [
            'pembelian' => $pembelianData,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pembelian $pembelian)
    {
        if (!$pembelian->canBeEdited()) {
            return redirect()->back()->with('flash', [
                'message' => 'Pembelian dengan status "' . $pembelian->status . '" tidak dapat diubah.',
                'type' => 'error',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:supplier,supplier_id',
            'tanggal_pembelian' => 'required|date',
            'tanggal_kirim_diharapkan' => 'nullable|date|after_or_equal:tanggal_pembelian',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.pembelian_detail_id' => 'required|exists:pembelian_detail,pembelian_detail_id',
            'items.*.qty_dipesan' => 'required|numeric|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Update header pembelian
            $pembelian->update($request->only([
                'supplier_id',
                'tanggal_pembelian',
                'tanggal_kirim_diharapkan',
                'catatan',
            ]));

            // Update detail pembelian
            foreach ($request->items as $itemData) {
                $detail = PembelianDetail::find($itemData['pembelian_detail_id']);
                if ($detail && $detail->pembelian_id === $pembelian->pembelian_id) {
                    $detail->update([
                        'qty_dipesan' => $itemData['qty_dipesan'],
                        'harga_satuan' => $itemData['harga_satuan'],
                        'total_harga' => $itemData['qty_dipesan'] * $itemData['harga_satuan'],
                    ]);
                }
            }
            // Model event akan otomatis update total biaya

            DB::commit();

            return redirect()->route('pembelian.index')->with('flash', [
                'message' => 'Purchase Order berhasil diperbarui.',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('flash', [
                'message' => 'Terjadi kesalahan saat memperbarui PO: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pembelian $pembelian)
    {
        if (!$pembelian->canBeCancelled()) {
            return redirect()->route('pembelian.index')
                ->with('flash', ['message' => 'PO tidak dapat dibatalkan karena statusnya.', 'type' => 'error']);
        }

        // Alternatifnya bisa dengan update status menjadi 'cancelled'
        // $pembelian->update(['status' => 'cancelled']);
        $pembelian->delete();

        return redirect()->route('pembelian.index')
            ->with('flash', ['message' => 'Purchase Order berhasil dihapus/dibatalkan!', 'type' => 'success']);
    }

    /**
     * Helper untuk mendapatkan label status yang lebih ramah pengguna.
     */
    private function getStatusLabel($status)
    {
        return match ($status) {
            'draft' => 'Draft',
            'sent' => 'Terkirim ke Supplier',
            'confirmed' => 'Dikonfirmasi Supplier',
            'partially_received' => 'Diterima Sebagian',
            'fully_received' => 'Diterima Lengkap',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($status),
        };
    }
}
