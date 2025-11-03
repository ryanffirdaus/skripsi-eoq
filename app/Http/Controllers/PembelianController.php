<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Pemasok;
use App\Http\Traits\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PembelianController extends Controller
{
    use RoleAccess;

    /**
     * Display a listing of the resource.
     * Menampilkan daftar semua Purchase Order.
     */
    public function index(Request $request)
    {
        $query = Pembelian::with([
            'pemasok:pemasok_id,nama_pemasok',
            'pengadaan:pengadaan_id,jenis_pengadaan',
            'createdBy:user_id,nama_lengkap',
        ]);

        // Terapkan filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pembelian_id', 'like', "%{$search}%")
                    ->orWhereHas('pemasok', function ($subq) use ($search) {
                        $subq->where('nama_pemasok', 'like', "%{$search}%");
                    });
            });
        }

        // Terapkan filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Terapkan filter pemasok
        if ($request->filled('pemasok_id')) {
            $query->where('pemasok_id', $request->pemasok_id);
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
                'pengadaan_id'      => $item->pengadaan_id,
                'pemasok_nama'      => $item->pemasok->nama_pemasok ?? 'N/A',
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
        $pemasok = Pemasok::select('pemasok_id', 'nama_pemasok')->orderBy('nama_pemasok')->get();

        $filters = [
            'search'         => $request->search,
            'status'         => $request->status,
            'pemasok_id'     => $request->pemasok_id,
            'sort_by'        => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page'       => (int) $perPage,
        ];

        // Tentukan permissions berdasarkan role
        $permissions = [
            'canCreate' => $this->hasRoles(['R01', 'R04', 'R09', 'R10']), // Admin, Staf Pengadaan, Manajer Pengadaan, Manajer Keuangan
            'canEdit' => $this->hasRoles(['R01', 'R04', 'R09', 'R10']), // Admin, Staf Pengadaan, Manajer Pengadaan, Manajer Keuangan
            'canDelete' => $this->hasRoles(['R01', 'R04', 'R09', 'R10']), // Admin, Staf Pengadaan, Manajer Pengadaan, Manajer Keuangan
        ];

        return Inertia::render('pembelian/index', [
            'pembelian' => $pembelian,
            'filters'   => $filters,
            'pemasoks'  => $pemasok,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Show the form for creating a new resource from a Pengadaan.
     * Halaman ini untuk generate PO dari permintaan pengadaan yang sudah disetujui.
     */
    public function create()
    {
        // Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa create
        if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
            return redirect()->route('pembelian.index')->with('flash', [
                'message' => 'Anda tidak memiliki izin untuk membuat pembelian baru.',
                'type' => 'error',
            ]);
        }

        // 1. Ambil data Pengadaan yang sudah disetujui keuangan dan belum diproses menjadi PO.
        $pengadaans = Pengadaan::where('status', 'diproses')
            ->with(['detail.pemasok:pemasok_id,nama_pemasok'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($pengadaan) {
                // Hanya sertakan pengadaan yang memiliki detail item
                if ($pengadaan->detail->isEmpty()) {
                    return null;
                }
                return [
                    'pengadaan_id' => $pengadaan->pengadaan_id,
                    'display_text' => $pengadaan->pengadaan_id . ' (' . date('Y-m-d', strtotime($pengadaan->created_at)) . ')',
                    'detail' => $pengadaan->detail->map(function ($detail) {
                        return [
                            'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                            'pemasok_id' => $detail->pemasok_id,
                            'pemasok_nama' => $detail->pemasok->nama_pemasok ?? 'N/A',
                            'jenis_barang' => $detail->jenis_barang,
                            'barang_id' => $detail->barang_id,
                            'nama_item' => $detail->nama_item,
                            'satuan' => $detail->satuan,
                            'qty_disetujui' => $detail->qty_disetujui ?? $detail->qty_diminta,
                            'harga_satuan' => $detail->harga_satuan,
                            'total_harga' => $detail->total_harga,
                        ];
                    }),
                ];
            })->filter()->values(); // Hapus null dari koleksi dan re-index

        // 2. Ambil semua pemasok untuk data dropdown.
        $pemasok = Pemasok::select(['pemasok_id', 'nama_pemasok'])->orderBy('nama_pemasok')->get();

        // 3. Render komponen Inertia dengan data yang dibutuhkan.
        return Inertia::render('pembelian/create', [
            'pengadaans' => $pengadaans,
            'pemasoks' => $pemasok,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa store
        if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
            return redirect()->route('pembelian.index')->with('flash', [
                'message' => 'Anda tidak memiliki izin untuk membuat pembelian baru.',
                'type' => 'error',
            ]);
        }

        // 1. Validasi input dari user
        $validator = Validator::make($request->all(), [
            'pengadaan_id' => 'required|exists:pengadaan,pengadaan_id',
            'pemasok_id' => 'required|exists:pemasok,pemasok_id',
            'tanggal_pembelian' => 'required|date',
            'tanggal_kirim_diharapkan' => 'nullable|date|after_or_equal:tanggal_pembelian',
            'catatan' => 'nullable|string',
            'metode_pembayaran' => 'required|in:tunai,transfer,termin',
            'termin_pembayaran' => 'nullable|string',
            'jumlah_dp' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.pengadaan_detail_id' => 'required|exists:pengadaan_detail,pengadaan_detail_id',
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
                'pemasok_id' => $request->pemasok_id,
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'tanggal_kirim_diharapkan' => $request->tanggal_kirim_diharapkan,
                'catatan' => $request->catatan,
                'metode_pembayaran' => $request->metode_pembayaran,
                'termin_pembayaran' => $request->termin_pembayaran,
                'jumlah_dp' => $request->metode_pembayaran === 'termin' ? ($request->jumlah_dp ?: 0) : 0,
                'status' => 'draft', // Status awal untuk PO baru
            ]);

            // 4. Simpan setiap item ke dalam detail pembelian
            foreach ($request->items as $item) {
                PembelianDetail::create([
                    'pembelian_id' => $pembelian->pembelian_id,
                    'pengadaan_detail_id' => $item['pengadaan_detail_id'],
                ]);
            }

            // 5. Update status Pengadaan menjadi 'diproses'
            $pengadaan = Pengadaan::find($request->pengadaan_id);
            if ($pengadaan && $pengadaan->status === 'pending') {
                $pengadaan->status = 'diproses';
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
            'pemasok',
            'pengadaan:pengadaan_id,pesanan_id',
            'detail.pengadaanDetail.bahanBaku',
            'detail.pengadaanDetail.produk',
            'transaksiPembayaran',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('pembelian/show', [
            'pembelian' => [
                'pembelian_id' => $pembelian->pembelian_id,
                'pengadaan_id' => $pembelian->pengadaan_id,
                'pemasok' => $pembelian->pemasok,
                'tanggal_pembelian' => $pembelian->tanggal_pembelian,
                'tanggal_kirim_diharapkan' => $pembelian->tanggal_kirim_diharapkan,
                'total_biaya' => $pembelian->total_biaya,
                'status' => $pembelian->status,
                'status_label' => $this->getStatusLabel($pembelian->status),
                'catatan' => $pembelian->catatan,
                'metode_pembayaran' => $pembelian->metode_pembayaran,
                'termin_pembayaran' => $pembelian->termin_pembayaran,
                'jumlah_dp' => $pembelian->jumlah_dp,
                'total_dibayar' => $pembelian->total_dibayar,
                'sisa_pembayaran' => $pembelian->sisa_pembayaran,
                'is_dp_paid' => $pembelian->isDpPaid(),
                'is_fully_paid' => $pembelian->isFullyPaid(),
                'created_by' => $pembelian->createdBy,
                'updated_by' => $pembelian->updatedBy,
                'created_at' => $pembelian->created_at?->format('d-m-Y H:i'),
                'updated_at' => $pembelian->updated_at?->format('d-m-Y H:i'),
                'can_edit' => $pembelian->canBeEdited(),
                'can_cancel' => $pembelian->canBeCancelled(),
                'detail' => $pembelian->detail->map(function ($detail) {
                    $pengadaanDetail = $detail->pengadaanDetail;
                    return [
                        'pembelian_detail_id' => $detail->pembelian_detail_id,
                        'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                        'jenis_barang' => $pengadaanDetail->jenis_barang,
                        'barang_id' => $pengadaanDetail->barang_id,
                        'nama_item' => $pengadaanDetail->nama_item,
                        'satuan' => $pengadaanDetail->satuan,
                        'qty_dipesan' => $pengadaanDetail->qty_diminta,
                        'qty_diterima' => $detail->penerimaanBahanBaku->sum('qty_diterima'),
                        'harga_satuan' => $pengadaanDetail->harga_satuan,
                        'total_harga' => $pengadaanDetail->total_harga,
                        'outstanding_qty' => $detail->getOutstandingQty(),
                        'is_fully_received' => $detail->isFullyReceived(),
                    ];
                }),
                'transaksi_pembayaran' => $pembelian->transaksiPembayaran->map(function ($transaksi) {
                    return [
                        'transaksi_pembayaran_id' => $transaksi->transaksi_pembayaran_id,
                        'jenis_pembayaran' => $transaksi->jenis_pembayaran,
                        'tanggal_pembayaran' => $transaksi->tanggal_pembayaran,
                        'jumlah_pembayaran' => $transaksi->jumlah_pembayaran,
                        'metode_pembayaran' => $transaksi->metode_pembayaran,
                        'bukti_pembayaran' => $transaksi->bukti_pembayaran,
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
        // Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa edit
        if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
            return redirect()->route('pembelian.index')->with('flash', [
                'message' => 'Anda tidak memiliki izin untuk mengedit pembelian.',
                'type' => 'error',
            ]);
        }

        // 1. Eager load relasi yang dibutuhkan
        $pembelian->load(['pemasok', 'detail.pengadaanDetail']);

        // 2. Ambil semua pemasok untuk dropdown
        $pemasok = Pemasok::select('pemasok_id', 'nama_pemasok')->orderBy('nama_pemasok')->get();

        // 2b. Status options untuk update status di halaman edit
        $statusOptions = [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'menunggu', 'label' => 'Menunggu'],
            ['value' => 'dipesan', 'label' => 'Dipesan'],
            ['value' => 'dikirim', 'label' => 'Dikirim'],
            ['value' => 'dikonfirmasi', 'label' => 'Dikonfirmasi'],
            ['value' => 'diterima', 'label' => 'Diterima'],
            ['value' => 'dibatalkan', 'label' => 'Dibatalkan'],
        ];

        // 3. Format data untuk dikirim ke frontend
        $pembelianData = [
            'pembelian_id' => $pembelian->pembelian_id,
            'pengadaan_id' => $pembelian->pengadaan_id,
            'pemasok_id' => $pembelian->pemasok_id,
            'tanggal_pembelian' => $pembelian->tanggal_pembelian ? \Carbon\Carbon::parse($pembelian->tanggal_pembelian)->format('Y-m-d') : '',
            'tanggal_kirim_diharapkan' => $pembelian->tanggal_kirim_diharapkan ? \Carbon\Carbon::parse($pembelian->tanggal_kirim_diharapkan)->format('Y-m-d') : '',
            'total_biaya' => $pembelian->total_biaya,
            'status' => $pembelian->status,
            'catatan' => $pembelian->catatan,
            'metode_pembayaran' => $pembelian->metode_pembayaran,
            'termin_pembayaran' => $pembelian->termin_pembayaran,
            'jumlah_dp' => $pembelian->jumlah_dp,
            'can_be_edited' => $pembelian->canBeEdited(),
            'detail' => $pembelian->detail->map(function ($item) {
                $pengadaanDetail = $item->pengadaanDetail;
                return [
                    'pembelian_detail_id' => $item->pembelian_detail_id,
                    'pengadaan_detail_id' => $item->pengadaan_detail_id,
                    'nama_item' => $pengadaanDetail ? $pengadaanDetail->nama_item : '-',
                    'satuan' => $pengadaanDetail ? $pengadaanDetail->satuan : '-',
                    'qty_dipesan' => $pengadaanDetail ? $pengadaanDetail->qty_diminta : 0,  // FIX: qty → qty_diminta
                    'harga_satuan' => $pengadaanDetail ? $pengadaanDetail->harga_satuan : 0,
                ];
            }),
        ];

        return Inertia::render('pembelian/edit', [
            'pembelian' => $pembelianData,
            'pemasoks' => $pemasok,  // FIX: pemasok → pemasoks
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pembelian $pembelian)
    {
        // Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa update
        if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
            return redirect()->route('pembelian.index')->with('flash', [
                'message' => 'Anda tidak memiliki izin untuk mengubah pembelian.',
                'type' => 'error',
            ]);
        }

        // Admin bisa edit kapan saja, bypass canBeEdited check
        if (!$this->isAdmin() && !$pembelian->canBeEdited()) {
            return redirect()->back()->with('flash', [
                'message' => 'Pembelian dengan status "' . $pembelian->status . '" tidak dapat diubah.',
                'type' => 'error',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:draft,menunggu,dipesan,dikirim,dikonfirmasi,diterima,dibatalkan',
            'pemasok_id' => 'required|exists:pemasok,pemasok_id',
            'tanggal_pembelian' => 'required|date',
            'tanggal_kirim_diharapkan' => 'nullable|date|after_or_equal:tanggal_pembelian',
            'catatan' => 'nullable|string',
            'metode_pembayaran' => 'required|in:tunai,transfer,termin',
            'termin_pembayaran' => 'nullable|string|max:50',
            'jumlah_dp' => 'required_if:metode_pembayaran,termin|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Validasi status transition (Admin bisa bypass)
        if ($request->has('status') && $request->status !== $pembelian->status) {
            if (!$this->isAdmin() && !$pembelian->isValidStatusTransition($request->status)) {
                return redirect()->back()
                    ->with('flash', [
                        'message' => 'Perubahan status dari "' . $pembelian->status . '" ke "' . $request->status . '" tidak diperbolehkan.',
                        'type' => 'error'
                    ])
                    ->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Update header pembelian (termasuk status jika ada)
            $updateData = [
                'pemasok_id' => $request->pemasok_id,
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'tanggal_kirim_diharapkan' => $request->tanggal_kirim_diharapkan,
                'catatan' => $request->catatan,
                'metode_pembayaran' => $request->metode_pembayaran,
                'termin_pembayaran' => $request->termin_pembayaran,
                'jumlah_dp' => $request->metode_pembayaran === 'termin' ? $request->jumlah_dp : 0,
            ];

            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }

            $pembelian->update($updateData);

            // Detail items tidak perlu diupdate karena data diambil dari pengadaan_detail
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
        // Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa destroy
        if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
            return redirect()->route('pembelian.index')->with('flash', [
                'message' => 'Anda tidak memiliki izin untuk menghapus pembelian.',
                'type' => 'error',
            ]);
        }

        // Admin bisa cancel kapan saja, bypass canBeCancelled check
        if (!$this->isAdmin() && !$pembelian->canBeCancelled()) {
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
     * Update status pembelian
     */
    public function updateStatus(Request $request, $pembelian_id)
    {
        $pembelian = Pembelian::where('pembelian_id', $pembelian_id)->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:draft,menunggu,dipesan,dikirim,dikonfirmasi,diterima,dibatalkan',
        ]);

        $pembelian->update(['status' => $validated['status']]);

        return back()->with('flash', [
            'message' => 'Status pembelian berhasil diperbarui!',
            'type' => 'success'
        ]);
    }

    /**
     * Helper untuk mendapatkan label status yang lebih ramah pengguna.
     */
    private function getStatusLabel($status)
    {
        return match ($status) {
            'draft' => 'Draft',
            'menunggu' => 'Menunggu Konfirmasi',
            'dipesan' => 'Dipesan',
            'dikirim' => 'Sedang Dikirim',
            'dikonfirmasi' => 'Dikonfirmasi Pemasok',
            'diterima' => 'Diterima Lengkap',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($status),
        };
    }
}
