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
            'pesanan.pelanggan',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pengiriman_id', 'like', "%{$search}%")
                    ->orWhere('nomor_resi', 'like', "%{$search}%")
                    ->orWhere('kurir', 'like', "%{$search}%")
                    ->orWhereHas('pesanan.pelanggan', function ($q) use ($search) {
                        $q->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply kurir filter
        if ($request->filled('kurir')) {
            $query->where('kurir', $request->kurir);
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
                'nomor_resi' => $item->nomor_resi,
                'kurir' => $item->kurir,
                'biaya_pengiriman' => $item->biaya_pengiriman,
                'estimasi_hari' => $item->estimasi_hari,
                'status' => $item->status,
                'status_label' => $item->status_label,
                'tanggal_kirim' => $item->tanggal_kirim?->format('Y-m-d'),
                'tanggal_diterima' => $item->tanggal_diterima?->format('Y-m-d'),
                'pesanan' => $item->pesanan ? [
                    'pesanan_id' => $item->pesanan->pesanan_id,
                    'total_harga' => $item->pesanan->total_harga,
                    'pelanggan' => $item->pesanan->pelanggan ? [
                        'nama' => $item->pesanan->pelanggan->nama_pelanggan,
                    ] : null,
                ] : null,
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $item->updated_at?->format('Y-m-d H:i:s'),
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
        $pesanan = Pesanan::with('pelanggan')
            ->whereIn('status', ['confirmed', 'processing'])
            ->whereDoesntHave('pengiriman')
            ->get()
            ->map(function ($item) {
                return [
                    'pesanan_id' => $item->pesanan_id,
                    'pelanggan_id' => $item->pelanggan_id,
                    'total_harga' => $item->total_harga,
                    'pelanggan' => $item->pelanggan ? [
                        'nama_pelanggan' => $item->pelanggan->nama_pelanggan,
                        'alamat_pelanggan' => $item->pelanggan->alamat_pelanggan,
                        'kota_pelanggan' => $item->pelanggan->kota_pelanggan,
                        'telepon_pelanggan' => $item->pelanggan->telepon_pelanggan,
                    ] : null,
                ];
            });

        return Inertia::render('pengiriman/create', [
            'pesanan' => $pesanan,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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

        $pengiriman = Pengiriman::create($request->all());

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
                        'alamat_pelanggan' => $pengiriman->pesanan->pelanggan->alamat_pelanggan,
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
        $validator = Validator::make($request->all(), [
            'nomor_resi' => 'nullable|string|max:255|unique:pengiriman,nomor_resi,' . $pengiriman->pengiriman_id . ',pengiriman_id',
            'kurir' => 'required|string|max:255',
            'biaya_pengiriman' => 'required|numeric|min:0',
            'estimasi_hari' => 'required|integer|min:1',
            'status' => 'required|in:pending,shipped,delivered,cancelled',
            'tanggal_kirim' => 'nullable|date',
            'tanggal_diterima' => 'nullable|date|after_or_equal:tanggal_kirim',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $pengiriman->update($request->all());

        return redirect()->route('pengiriman.index')
            ->with('flash', [
                'message' => 'Pengiriman berhasil diperbarui!',
                'type' => 'success'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pengiriman $pengiriman)
    {
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
            'status' => 'required|in:pending,shipped,delivered,cancelled',
            'tanggal_kirim' => 'nullable|date',
            'tanggal_diterima' => 'nullable|date|after_or_equal:tanggal_kirim',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pengiriman->update($request->only(['status', 'tanggal_kirim', 'tanggal_diterima']));

        return response()->json([
            'message' => 'Status pengiriman berhasil diperbarui!',
            'pengiriman' => $pengiriman->fresh()
        ]);
    }
}
