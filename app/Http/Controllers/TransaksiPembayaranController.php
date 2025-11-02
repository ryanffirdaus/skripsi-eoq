<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\TransaksiPembayaran;
use App\Http\Traits\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TransaksiPembayaranController extends Controller
{
    use RoleAccess;
    /**
     * Display a listing of the resource.
     * Menampilkan daftar semua transaksi pembayaran.
     */
    public function index(Request $request)
    {
        $query = TransaksiPembayaran::with([
            'pembelian.pemasok:pemasok_id,nama_pemasok',
            'pembelian:pembelian_id,pemasok_id,total_biaya',
        ]);

        // Terapkan filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaksi_pembayaran_id', 'like', "%{$search}%")
                    ->orWhereHas('pembelian', function ($subq) use ($search) {
                        $subq->where('pembelian_id', 'like', "%{$search}%")
                            ->orWhereHas('pemasok', function ($subq2) use ($search) {
                                $subq2->where('nama_pemasok', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Terapkan filter tanggal
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_pembayaran', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_pembayaran', '<=', $request->tanggal_sampai);
        }

        // Terapkan filter pembelian_id
        if ($request->filled('pembelian_id')) {
            $query->where('pembelian_id', $request->pembelian_id);
        }

        // Terapkan sorting
        $sortBy = $request->get('sort_by', 'tanggal_pembayaran');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Terapkan paginasi
        $perPage = $request->get('per_page', 10);
        $transaksiPembayaran = $query->paginate($perPage);

        // Transformasi data untuk frontend
        $transaksiPembayaran->getCollection()->transform(function ($item) {
            return [
                'transaksi_pembayaran_id' => $item->transaksi_pembayaran_id,
                'pembelian_id'            => $item->pembelian_id,
                'pemasok_nama'            => $item->pembelian->pemasok->nama_pemasok ?? 'N/A',
                'tanggal_pembayaran'      => $item->tanggal_pembayaran?->format('d M Y'),
                'jenis_pembayaran'        => $item->jenis_pembayaran,
                'jumlah_pembayaran'       => (float) $item->jumlah_pembayaran,
                'bukti_pembayaran'        => $item->bukti_pembayaran,
                'catatan'               => $item->catatan,
                'created_at'              => $item->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        // Data untuk filter di frontend
        $pembelians = Pembelian::with('pemasok:pemasok_id,nama_pemasok')
            ->select('pembelian_id', 'pemasok_id')
            ->whereIn('status', ['confirmed', 'partially_received', 'fully_received'])
            ->orderBy('pembelian_id')
            ->get()
            ->map(function ($item) {
                return [
                    'pembelian_id' => $item->pembelian_id,
                    'pemasok_nama' => $item->pemasok->nama_pemasok ?? 'N/A',
                ];
            });

        $filters = [
            'search'          => $request->search,
            'tanggal_dari'    => $request->tanggal_dari,
            'tanggal_sampai'  => $request->tanggal_sampai,
            'pembelian_id'    => $request->pembelian_id,
            'sort_by'         => $sortBy,
            'sort_direction'  => $sortDirection,
            'per_page'        => (int) $perPage,
        ];

        // Tentukan permissions berdasarkan role
        $permissions = [
            'canCreate' => $this->hasRoles(['R01', 'R06', 'R10']), // Admin, Staf Keuangan, Manajer Keuangan
            'canEdit' => $this->hasRoles(['R01', 'R06', 'R10']), // Admin, Staf Keuangan, Manajer Keuangan
            'canDelete' => $this->hasRoles(['R01', 'R06', 'R10']), // Admin, Staf Keuangan, Manajer Keuangan
        ];

        return Inertia::render('transaksi-pembayaran/index', [
            'transaksiPembayaran' => $transaksiPembayaran,
            'filters'             => $filters,
            'pembelians'          => $pembelians,
            'permissions'         => $permissions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Authorization: hanya Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa create
        if (!$this->isKeuanganRelated()) {
            return redirect()->route('transaksi-pembayaran.index')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk membuat transaksi pembayaran baru.',
                    'type' => 'error'
                ]);
        }

        // Ambil pembelian yang sudah dikonfirmasi (bisa dibayar)
        $pembelians = Pembelian::with('pemasok:pemasok_id,nama_pemasok')
            ->whereIn('status', ['confirmed', 'partially_received', 'fully_received'])
            ->select('pembelian_id', 'pemasok_id', 'total_biaya', 'tanggal_pembelian', 'metode_pembayaran', 'termin_pembayaran', 'jumlah_dp')
            ->orderBy('tanggal_pembelian', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'pembelian_id'       => $item->pembelian_id,
                    'pemasok_nama'       => $item->pemasok->nama_pemasok ?? 'N/A',
                    'total_biaya'        => (float) $item->total_biaya,
                    'tanggal_pembelian'  => $item->tanggal_pembelian,
                    'metode_pembayaran'  => $item->metode_pembayaran,
                    'termin_pembayaran'  => $item->termin_pembayaran,
                    'jumlah_dp'          => (float) $item->jumlah_dp,
                    'total_dibayar'      => (float) $item->total_dibayar,
                    'sisa_pembayaran'    => (float) $item->sisa_pembayaran,
                    'is_dp_paid'         => $item->isDpPaid(),
                    'is_fully_paid'      => $item->isFullyPaid(),
                    'display_text'       => "{$item->pembelian_id} - {$item->pemasok->nama_pemasok} - Rp " . number_format((float) $item->total_biaya, 0, ',', '.'),
                ];
            });

        return Inertia::render('transaksi-pembayaran/create', [
            'pembelians' => $pembelians,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Authorization: hanya Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa store
        if (!$this->isKeuanganRelated()) {
            return redirect()->route('transaksi-pembayaran.index')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk membuat transaksi pembayaran baru.',
                    'type' => 'error'
                ]);
        }

        // Get pembelian to validate payment
        $pembelian = Pembelian::findOrFail($request->pembelian_id);

        $validator = Validator::make($request->all(), [
            'pembelian_id'       => 'required|exists:pembelian,pembelian_id',
            'jenis_pembayaran'   => 'required|in:dp,termin,pelunasan',
            'tanggal_pembayaran' => 'required|date',
            'jumlah_pembayaran'  => 'required|numeric|min:0',
            'metode_pembayaran'  => 'required|in:tunai,transfer',
            'bukti_pembayaran'   => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'catatan'          => 'nullable|string|max:1000',
        ], [
            'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah',
            'bukti_pembayaran.file' => 'Bukti pembayaran harus berupa file',
            'bukti_pembayaran.mimes' => 'Bukti pembayaran harus berformat: jpeg, png, jpg, atau pdf',
            'bukti_pembayaran.max' => 'Ukuran file maksimal 2MB',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validasi berdasarkan jenis pembayaran
        if ($request->jenis_pembayaran === 'dp') {
            // DP harus sesuai dengan jumlah_dp di pembelian
            if ($pembelian->metode_pembayaran !== 'termin') {
                return redirect()->back()
                    ->withErrors(['jenis_pembayaran' => 'Pembelian ini tidak menggunakan sistem termin.'])
                    ->withInput();
            }

            if ($pembelian->isDpPaid()) {
                return redirect()->back()
                    ->withErrors(['jenis_pembayaran' => 'DP sudah dibayar sebelumnya.'])
                    ->withInput();
            }

            if ((float) $request->jumlah_pembayaran !== (float) $pembelian->jumlah_dp) {
                return redirect()->back()
                    ->withErrors(['jumlah_pembayaran' => "Jumlah pembayaran DP harus Rp " . number_format($pembelian->jumlah_dp, 0, ',', '.')])
                    ->withInput();
            }
        } elseif ($request->jenis_pembayaran === 'termin') {
            // Termin hanya bisa dilakukan setelah DP dibayar
            if (!$pembelian->isDpPaid()) {
                return redirect()->back()
                    ->withErrors(['jenis_pembayaran' => 'DP belum dibayar. Bayar DP terlebih dahulu.'])
                    ->withInput();
            }

            // Jumlah pembayaran tidak boleh melebihi sisa
            if ((float) $request->jumlah_pembayaran > (float) $pembelian->sisa_pembayaran) {
                return redirect()->back()
                    ->withErrors(['jumlah_pembayaran' => "Jumlah pembayaran tidak boleh melebihi sisa: Rp " . number_format($pembelian->sisa_pembayaran, 0, ',', '.')])
                    ->withInput();
            }
        } elseif ($request->jenis_pembayaran === 'pelunasan') {
            // Pelunasan = sisa pembayaran
            if ((float) $request->jumlah_pembayaran > (float) $pembelian->sisa_pembayaran) {
                return redirect()->back()
                    ->withErrors(['jumlah_pembayaran' => "Jumlah pelunasan harus Rp " . number_format($pembelian->sisa_pembayaran, 0, ',', '.')])
                    ->withInput();
            }
        }

        // Generate ID transaksi
        $lastTransaksi = TransaksiPembayaran::latest('transaksi_pembayaran_id')->first();
        $lastNumber = $lastTransaksi ? (int) substr($lastTransaksi->transaksi_pembayaran_id, 3) : 0;
        $newNumber = $lastNumber + 1;
        $transaksiId = 'TRX' . str_pad($newNumber, 8, '0', STR_PAD_LEFT);

        // Upload bukti pembayaran
        $buktiPath = null;
        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');
            $fileName = $transaksiId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $buktiPath = $file->storeAs('bukti_pembayaran', $fileName, 'public');
        }

        TransaksiPembayaran::create([
            'transaksi_pembayaran_id' => $transaksiId,
            'pembelian_id'            => $request->pembelian_id,
            'jenis_pembayaran'        => $request->jenis_pembayaran,
            'tanggal_pembayaran'      => $request->tanggal_pembayaran,
            'jumlah_pembayaran'       => $request->jumlah_pembayaran,
            'metode_pembayaran'       => $request->metode_pembayaran,
            'bukti_pembayaran'        => $buktiPath,
            'catatan'               => $request->catatan,
        ]);

        return redirect()->route('transaksi-pembayaran.index')
            ->with('flash', [
                'message' => 'Transaksi pembayaran berhasil dicatat.',
                'type'    => 'success',
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(TransaksiPembayaran $transaksiPembayaran)
    {
        $transaksiPembayaran->load([
            'pembelian.pemasok:pemasok_id,nama_pemasok,nomor_telepon,email,alamat',
            'pembelian.detail.pengadaanDetail.bahanBaku:bahan_baku_id,nama_bahan,satuan_bahan',
            'pembelian.detail.pengadaanDetail.produk:produk_id,nama_produk,satuan_produk',
        ]);

        $data = [
            'transaksi_pembayaran_id' => $transaksiPembayaran->transaksi_pembayaran_id,
            'pembelian'               => [
                'pembelian_id'       => $transaksiPembayaran->pembelian->pembelian_id,
                'tanggal_pembelian'  => $transaksiPembayaran->pembelian->tanggal_pembelian,
                'total_biaya'        => (float) $transaksiPembayaran->pembelian->total_biaya,
                'metode_pembayaran'  => $transaksiPembayaran->pembelian->metode_pembayaran,
                'termin_pembayaran'  => $transaksiPembayaran->pembelian->termin_pembayaran,
                'jumlah_dp'          => (float) $transaksiPembayaran->pembelian->jumlah_dp,
                'total_dibayar'      => (float) $transaksiPembayaran->pembelian->total_dibayar,
                'sisa_pembayaran'    => (float) $transaksiPembayaran->pembelian->sisa_pembayaran,
                'status'             => $transaksiPembayaran->pembelian->status,
                'catatan'            => $transaksiPembayaran->pembelian->catatan,
                'pemasok'            => [
                    'nama_pemasok'   => $transaksiPembayaran->pembelian->pemasok->nama_pemasok ?? 'N/A',
                    'nomor_telepon'  => $transaksiPembayaran->pembelian->pemasok->nomor_telepon ?? '-',
                    'email'          => $transaksiPembayaran->pembelian->pemasok->email ?? '-',
                    'alamat'         => $transaksiPembayaran->pembelian->pemasok->alamat ?? '-',
                ],
                'detail' => $transaksiPembayaran->pembelian->detail->map(function ($detail) {
                    $pengadaanDetail = $detail->pengadaanDetail;
                    return [
                        'nama_item'    => $pengadaanDetail->nama_item,
                        'jenis_barang' => $pengadaanDetail->jenis_barang,
                        'satuan'       => $pengadaanDetail->satuan,
                        'qty'          => (float) $pengadaanDetail->qty,
                        'harga_satuan' => (float) $pengadaanDetail->harga_satuan,
                        'subtotal'     => (float) $pengadaanDetail->total_harga,
                    ];
                }),
            ],
            'jenis_pembayaran'        => $transaksiPembayaran->jenis_pembayaran,
            'tanggal_pembayaran'      => $transaksiPembayaran->tanggal_pembayaran,
            'jumlah_pembayaran'       => (float) $transaksiPembayaran->jumlah_pembayaran,
            'metode_pembayaran'       => $transaksiPembayaran->metode_pembayaran,
            'bukti_pembayaran'        => $transaksiPembayaran->bukti_pembayaran
                ? Storage::url($transaksiPembayaran->bukti_pembayaran)
                : null,
            'catatan'               => $transaksiPembayaran->catatan,
            'created_at'              => $transaksiPembayaran->created_at?->format('d M Y H:i'),
        ];

        return Inertia::render('transaksi-pembayaran/show', [
            'transaksi' => $data,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransaksiPembayaran $transaksiPembayaran)
    {
        // Authorization: hanya Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa edit
        if (!$this->isKeuanganRelated()) {
            return redirect()->route('transaksi-pembayaran.index')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk mengedit transaksi pembayaran.',
                    'type' => 'error'
                ]);
        }

        $transaksiPembayaran->load('pembelian.pemasok:pemasok_id,nama_pemasok');

        $pembelians = Pembelian::with('pemasok:pemasok_id,nama_pemasok')
            ->whereIn('status', ['confirmed', 'partially_received', 'fully_received'])
            ->select('pembelian_id', 'pemasok_id', 'total_biaya')
            ->orderBy('pembelian_id')
            ->get()
            ->map(function ($item) {
                return [
                    'pembelian_id' => $item->pembelian_id,
                    'pemasok_nama' => $item->pemasok->nama_pemasok ?? 'N/A',
                    'total_biaya'  => (float) $item->total_biaya,
                    'display_text' => "{$item->pembelian_id} - {$item->pemasok->nama_pemasok}",
                ];
            });

        $data = [
            'transaksi_pembayaran_id' => $transaksiPembayaran->transaksi_pembayaran_id,
            'pembelian_id'            => $transaksiPembayaran->pembelian_id,
            'pemasok_nama'            => $transaksiPembayaran->pembelian->pemasok->nama_pemasok ?? 'N/A',
            'jenis_pembayaran'        => $transaksiPembayaran->jenis_pembayaran,
            'tanggal_pembayaran'      => $transaksiPembayaran->tanggal_pembayaran,
            'jumlah_pembayaran'       => (float) $transaksiPembayaran->jumlah_pembayaran,
            'metode_pembayaran'       => $transaksiPembayaran->metode_pembayaran,
            'bukti_pembayaran'        => $transaksiPembayaran->bukti_pembayaran
                ? Storage::url($transaksiPembayaran->bukti_pembayaran)
                : null,
            'catatan'               => $transaksiPembayaran->catatan,
        ];

        return Inertia::render('transaksi-pembayaran/edit', [
            'transaksi'  => $data,
            'pembelians' => $pembelians,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransaksiPembayaran $transaksiPembayaran)
    {
        // Authorization: hanya Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa update
        if (!$this->isKeuanganRelated()) {
            return redirect()->route('transaksi-pembayaran.index')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk mengubah transaksi pembayaran.',
                    'type' => 'error'
                ]);
        }

        $validator = Validator::make($request->all(), [
            'jenis_pembayaran'   => 'required|in:dp,termin,pelunasan',
            'tanggal_pembayaran' => 'required|date',
            'jumlah_pembayaran'  => 'required|numeric|min:0',
            'metode_pembayaran'  => 'required|in:tunai,transfer',
            'bukti_pembayaran'   => [
                function ($attribute, $value, $fail) use ($transaksiPembayaran) {
                    if (!$transaksiPembayaran->bukti_pembayaran && !$value) {
                        $fail('Bukti pembayaran wajib diunggah');
                    }
                },
                'nullable',
                'file',
                'mimes:jpeg,png,jpg,pdf',
                'max:2048'
            ],
            'catatan'          => 'nullable|string|max:1000',
        ], [
            'bukti_pembayaran.file' => 'Bukti pembayaran harus berupa file',
            'bukti_pembayaran.mimes' => 'Bukti pembayaran harus berformat: jpeg, png, jpg, atau pdf',
            'bukti_pembayaran.max' => 'Ukuran file maksimal 2MB',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = [
            'jenis_pembayaran'   => $request->jenis_pembayaran,
            'tanggal_pembayaran' => $request->tanggal_pembayaran,
            'jumlah_pembayaran'  => $request->jumlah_pembayaran,
            'metode_pembayaran'  => $request->metode_pembayaran,
            'catatan'          => $request->catatan,
        ];

        // Upload bukti pembayaran baru jika ada
        if ($request->hasFile('bukti_pembayaran')) {
            // Hapus bukti lama
            if ($transaksiPembayaran->bukti_pembayaran) {
                Storage::disk('public')->delete($transaksiPembayaran->bukti_pembayaran);
            }

            $file = $request->file('bukti_pembayaran');
            $fileName = $transaksiPembayaran->transaksi_pembayaran_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $buktiPath = $file->storeAs('bukti_pembayaran', $fileName, 'public');
            $data['bukti_pembayaran'] = $buktiPath;
        }

        $transaksiPembayaran->update($data);

        return redirect()->route('transaksi-pembayaran.index')
            ->with('flash', [
                'message' => 'Transaksi pembayaran berhasil diperbarui.',
                'type'    => 'success',
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransaksiPembayaran $transaksiPembayaran)
    {
        // Authorization: hanya Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa destroy
        if (!$this->isKeuanganRelated()) {
            return redirect()->route('transaksi-pembayaran.index')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk menghapus transaksi pembayaran.',
                    'type' => 'error'
                ]);
        }

        // Hapus bukti pembayaran
        if ($transaksiPembayaran->bukti_pembayaran) {
            Storage::disk('public')->delete($transaksiPembayaran->bukti_pembayaran);
        }

        $transaksiPembayaran->delete();

        return redirect()->route('transaksi-pembayaran.index')
            ->with('flash', [
                'message' => 'Transaksi pembayaran berhasil dihapus.',
                'type'    => 'success',
            ]);
    }
}
