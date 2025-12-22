<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class PengirimanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pengiriman::with([
            'pesanan.pelanggan'
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pengiriman_id', 'like', "%{$search}%")
                    ->orWhere('pesanan_id', 'like', "%{$search}%")
                    ->orWhere('nomor_resi', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('kurir', 'like', "%{$search}%")
                    ->orWhereHas('pesanan.pelanggan', function ($q) use ($search) {
                        $q->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Apply pagination
        $perPage = $request->get('per_page', 10);
        $pengiriman = $query->paginate($perPage);

        // Transform data
        $pengiriman->getCollection()->transform(function ($item) {
            return [
                'pengiriman_id' => $item->pengiriman_id,
                'pesanan_id' => $item->pesanan_id,
                'kurir' => $item->kurir,
                'status' => $item->status,
                'status_label' => $item->status_label,
                'pesanan' => $item->pesanan ? [
                    'pesanan_id' => $item->pesanan->pesanan_id,
                    'pelanggan' => $item->pesanan->pelanggan ? [
                        'nama' => $item->pesanan->pelanggan->nama_pelanggan,
                    ] : null,
                ] : null,
            ];
        });

        $filters = [
            'search' => $request->search,
            'status' => $request->status,
            'kurir' => $request->kurir,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'per_page' => (int) $perPage,
        ];

        return Inertia::render('pengiriman/index', [
            'pengiriman' => $pengiriman,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Authorization: Admin (R01), Staf Gudang (R02), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk membuat pengiriman baru.');
        }

        $pesananList = Pesanan::with(['pelanggan', 'detail.produk'])
            ->whereIn('status', ['menunggu', 'diproses'])
            ->whereDoesntHave('pengiriman')
            ->get();

        $pesanan = $pesananList->map(function ($item) {
            $details = $item->detail->map(function ($detail) {
                return [
                    'pesanan_detail_id' => $detail->pesanan_detail_id,
                    'produk_id' => $detail->produk_id,
                    'produk_nama' => $detail->produk->nama_produk ?? 'N/A',
                    'jumlah_produk' => $detail->jumlah_produk,
                    'stok_produk' => $detail->produk->stok_produk ?? 0,
                    'stok_cukup' => ($detail->produk->stok_produk ?? 0) >= $detail->jumlah_produk,
                    'harga_satuan' => $detail->harga_satuan,
                    'subtotal' => $detail->subtotal,
                ];
            })->toArray();

            $allStockSufficient = collect($details)->every(fn($d) => $d['stok_cukup']);

            return [
                'pesanan_id' => $item->pesanan_id,
                'pelanggan_id' => $item->pelanggan_id,
                'total_harga' => $item->total_harga,
                'tanggal_pemesanan' => $item->tanggal_pemesanan,
                'all_stock_sufficient' => $allStockSufficient,
                'pelanggan' => $item->pelanggan ? [
                    'nama_pelanggan' => $item->pelanggan->nama_pelanggan,
                    'alamat_pengiriman' => $item->pelanggan->alamat_pengiriman,
                    'nomor_telepon' => $item->pelanggan->nomor_telepon,
                ] : null,
                'detail' => $details,
            ];
        })->values()->all();

        return Inertia::render('pengiriman/create', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Authorization: Admin (R01), Staf Gudang (R02), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk menyimpan pengiriman.');
        }

        $validator = Validator::make($request->all(), [
            'pesanan_id' => 'required|exists:pesanan,pesanan_id',
            'kurir' => 'required|string|max:255',
            'biaya_pengiriman' => 'required|numeric|min:0',
            'estimasi_hari' => 'required|integer|min:1',
            'nomor_resi' => 'nullable|string|max:255|unique:pengiriman,nomor_resi',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validasi stok produk
        $pesanan = Pesanan::with('detail.produk')->find($request->pesanan_id);

        if (!$pesanan) {
            return redirect()->back()
                ->withErrors(['pesanan_id' => 'Pesanan tidak ditemukan.'])
                ->withInput();
        }

        // Cek stok setiap produk di pesanan
        $stockErrors = [];
        foreach ($pesanan->detail as $detail) {
            $produk = $detail->produk;
            if (!$produk) {
                $stockErrors[] = "Produk untuk item detail tidak ditemukan.";
                continue;
            }

            if ($produk->stok_produk < $detail->jumlah_produk) {
                $stockErrors[] = "Produk '{$produk->nama_produk}' memiliki stok {$produk->stok_produk}, tetapi pesanan membutuhkan {$detail->jumlah_produk} unit.";
            }
        }

        if (!empty($stockErrors)) {
            return redirect()->back()
                ->withErrors(['stock' => implode(' ', $stockErrors)])
                ->withInput();
        }

        $pengiriman = Pengiriman::create($request->all());

        // Status pesanan diupdate oleh PengirimanObserver:
        // - Jika pengiriman dibuat dengan status selain 'dikirim' → pesanan menjadi 'siap_dikirim'
        // - Jika pengiriman status berubah ke 'dikirim' → pesanan menjadi 'dikirim'

        return redirect()->route('pengiriman.index')
            ->with('flash', [
                'message' => 'Pengiriman berhasil dibuat dengan ID: ' . $pengiriman->pengiriman_id . '!',
                'type' => 'success'
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Pengiriman $pengiriman)
    {
        $pengiriman->load([
            'pesanan.pelanggan',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('pengiriman/show', [
            'pengiriman' => [
                'pengiriman_id' => $pengiriman->pengiriman_id,
                'pesanan_id' => $pengiriman->pesanan_id,
                'nomor_resi' => $pengiriman->nomor_resi,
                'kurir' => $pengiriman->kurir,
                'biaya_pengiriman' => $pengiriman->biaya_pengiriman,
                'estimasi_hari' => $pengiriman->estimasi_hari,
                'status' => $pengiriman->status,
                'status_label' => $pengiriman->status_label,
                'tanggal_kirim' => $pengiriman->tanggal_kirim?->format('Y-m-d'),
                'tanggal_diterima' => $pengiriman->tanggal_diterima?->format('Y-m-d'),
                'catatan' => $pengiriman->catatan,
                'pesanan' => [
                    'pesanan_id' => $pengiriman->pesanan->pesanan_id,
                    'tanggal_pesanan' => $pengiriman->pesanan->tanggal_pemesanan,
                    'total_harga' => $pengiriman->pesanan->total_harga,
                    'status' => $pengiriman->pesanan->status,
                    'pelanggan' => [
                        'nama_pelanggan' => $pengiriman->pesanan->pelanggan->nama_pelanggan,
                        'alamat_pengiriman' => $pengiriman->pesanan->pelanggan->alamat_pengiriman,
                        'kota_pelanggan' => $pengiriman->pesanan->pelanggan->kota_pelanggan,
                        'telepon_pelanggan' => $pengiriman->pesanan->pelanggan->telepon_pelanggan,
                    ]
                ],
                'createdBy' => $pengiriman->createdBy ? [
                    'user_id' => $pengiriman->createdBy->user_id,
                    'nama_lengkap' => $pengiriman->createdBy->nama_lengkap,
                ] : null,
                'updatedBy' => $pengiriman->updatedBy ? [
                    'user_id' => $pengiriman->updatedBy->user_id,
                    'nama_lengkap' => $pengiriman->updatedBy->nama_lengkap,
                ] : null,
                'created_at' => $pengiriman->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $pengiriman->updated_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pengiriman $pengiriman)
    {
        // Authorization: Admin (R01), Staf Gudang (R02), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit pengiriman.');
        }

        return Inertia::render('pengiriman/edit', [
            'pengiriman' => [
                'pengiriman_id' => $pengiriman->pengiriman_id,
                'pesanan_id' => $pengiriman->pesanan_id,
                'nomor_resi' => $pengiriman->nomor_resi,
                'kurir' => $pengiriman->kurir,
                'biaya_pengiriman' => $pengiriman->biaya_pengiriman,
                'estimasi_hari' => $pengiriman->estimasi_hari,
                'status' => $pengiriman->status,
                'tanggal_kirim' => $pengiriman->tanggal_kirim?->format('Y-m-d'),
                'tanggal_diterima' => $pengiriman->tanggal_diterima?->format('Y-m-d'),
                'catatan' => $pengiriman->catatan,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pengiriman $pengiriman)
    {
        // Authorization: Admin (R01), Staf Gudang (R02), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah pengiriman.');
        }

        // Gunakan method validated() untuk mendapatkan data yang sudah lolos validasi
        $validatedData = $request->validate([
            'nomor_resi' => 'nullable|string|max:255|unique:pengiriman,nomor_resi,' . $pengiriman->pengiriman_id . ',pengiriman_id',
            'kurir' => 'required|string|max:255',
            'biaya_pengiriman' => 'required|numeric|min:0',
            'estimasi_hari' => 'required|integer|min:1',
            'status' => 'required|in:menunggu,dikirim,selesai,dibatalkan',
            'tanggal_kirim' => 'nullable|date',
            'tanggal_diterima' => 'nullable|date|after_or_equal:tanggal_kirim',
            'catatan' => 'nullable|string',
        ]);

        // Validasi kondisional: jika status 'dikirim', maka tanggal kirim wajib diisi
        if ($validatedData['status'] === 'dikirim' && empty($validatedData['tanggal_kirim'])) {
            return back()
                ->withErrors(['tanggal_kirim' => 'Tanggal kirim wajib diisi jika status pesanan adalah "dikirim".'])
                ->withInput();
        }

        // Validasi kondisional: jika status 'selesai', maka tanggal diterima wajib diisi
        if ($validatedData['status'] === 'selesai' && empty($validatedData['tanggal_diterima'])) {
            return back()
                ->withErrors(['tanggal_diterima' => 'Tanggal diterima wajib diisi jika status pesanan adalah "selesai".'])
                ->withInput();
        }

        // Update data pengiriman dengan data yang sudah tervalidasi
        $oldStatus = $pengiriman->status;
        $pengiriman->update($validatedData);

        // Update status pesanan terkait berdasarkan status pengiriman
        if ($validatedData['status'] === 'dikirim' || $validatedData['status'] === 'selesai') {
            $pengiriman->pesanan()->update(['status' => $validatedData['status']]);
        }

        if ($oldStatus !== $pengiriman->status) {
             $usersToNotify = \App\Models\User::whereIn('role_id', ['R01', 'R02', 'R07'])->get(); // Admin, Staf Gudang, Manajer Gudang
             \Illuminate\Support\Facades\Notification::send($usersToNotify, new \App\Notifications\PengirimanStatusChangedNotification($pengiriman, $oldStatus, $pengiriman->status));
        }

        return redirect()->route('pengiriman.index')
            ->with('flash', [
                'message' => 'Data pengiriman berhasil diperbarui!',
                'type' => 'success'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pengiriman $pengiriman)
    {
        // Authorization: Admin (R01), Staf Gudang (R02), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus pengiriman.');
        }

        $pengiriman->delete();

        return redirect()->route('pengiriman.index')
            ->with('flash', [
                'message' => 'Pengiriman berhasil dihapus!',
                'type' => 'success'
            ]);
    }

    /**
     * Update status pengiriman
     */
    public function updateStatus(Request $request, Pengiriman $pengiriman)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:menunggu,dikirim,selesai,dibatalkan',
            'tanggal_kirim' => 'nullable|date',
            'tanggal_diterima' => 'nullable|date|after_or_equal:tanggal_kirim',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $pengiriman->status;
        $pengiriman->update($request->only(['status', 'tanggal_kirim', 'tanggal_diterima']));

        if ($oldStatus !== $pengiriman->status) {
             $usersToNotify = \App\Models\User::whereIn('role_id', ['R01', 'R02', 'R07'])->get(); // Admin, Staf Gudang, Manajer Gudang
             \Illuminate\Support\Facades\Notification::send($usersToNotify, new \App\Notifications\PengirimanStatusChangedNotification($pengiriman, $oldStatus, $pengiriman->status));

             // Update status pesanan terkait
             if ($pengiriman->status === 'dikirim' || $pengiriman->status === 'selesai') {
                 $pengiriman->pesanan()->update(['status' => $pengiriman->status]);
             }
        }

        return response()->json([
            'message' => 'Status pengiriman berhasil diperbarui!',
            'pengiriman' => $pengiriman->fresh()
        ]);
    }
}
