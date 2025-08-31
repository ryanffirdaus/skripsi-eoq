<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Pengadaan;
use App\Models\PengadaanDetail;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PembelianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pembelian::with(['supplier', 'pengadaan'])
            ->select([
                'pembelian_id',
                'pengadaan_id',
                'supplier_id',
                'nomor_po',
                'tanggal_pembelian',
                'tanggal_jatuh_tempo',
                'total_biaya',
                'status',
                'metode_pembayaran',
                'created_at'
            ]);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('tanggal_pembelian', [
                $request->date_from,
                $request->date_to
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pembelian_id', 'like', "%{$search}%")
                    ->orWhere('nomor_po', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplier) use ($search) {
                        $supplier->where('nama_supplier', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $pembelian = $query->paginate(15)->withQueryString();

        // Transform data
        $pembelian->getCollection()->transform(function ($item) {
            return [
                'pembelian_id' => $item->pembelian_id,
                'pengadaan_id' => $item->pengadaan_id,
                'nomor_po' => $item->nomor_po,
                'supplier' => [
                    'supplier_id' => $item->supplier->supplier_id,
                    'nama_supplier' => $item->supplier->nama_supplier,
                ],
                'tanggal_pembelian' => $item->tanggal_pembelian?->format('Y-m-d'),
                'tanggal_jatuh_tempo' => $item->tanggal_jatuh_tempo?->format('Y-m-d'),
                'total_biaya' => $item->total_biaya,
                'status' => $item->status,
                'status_label' => $item->status_label,
                'metode_pembayaran' => $item->metode_pembayaran,
                'can_edit' => $item->canBeEdited(),
                'can_cancel' => $item->canBeCancelled(),
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        // Get suppliers for filter
        $suppliers = Supplier::active()
            ->select('supplier_id', 'nama_supplier')
            ->orderBy('nama_supplier')
            ->get();

        return Inertia::render('pembelian/index', [
            'pembelian' => $pembelian,
            'suppliers' => $suppliers,
            'filters' => $request->only(['status', 'supplier_id', 'date_from', 'date_to', 'search']),
            'sort' => ['field' => $sortField, 'order' => $sortOrder],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $pengadaanId = $request->get('pengadaan_id');

        if ($pengadaanId) {
            // Create PO from existing pengadaan
            $pengadaan = Pengadaan::with(['detail', 'supplier'])
                ->where('pengadaan_id', $pengadaanId)
                ->where('status', 'approved')
                ->firstOrFail();

            return Inertia::render('pembelian/create', [
                'pengadaan' => [
                    'pengadaan_id' => $pengadaan->pengadaan_id,
                    'supplier_id' => $pengadaan->supplier_id,
                    'tanggal_dibutuhkan' => $pengadaan->tanggal_dibutuhkan?->format('Y-m-d'),
                    'supplier' => [
                        'supplier_id' => $pengadaan->supplier->supplier_id,
                        'nama_supplier' => $pengadaan->supplier->nama_supplier,
                        'kontak_person' => $pengadaan->supplier->kontak_person,
                        'telepon' => $pengadaan->supplier->telepon,
                    ],
                    'detail' => $pengadaan->detail->map(function ($detail) {
                        return [
                            'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                            'item_type' => $detail->item_type,
                            'item_id' => $detail->item_id,
                            'nama_item' => $detail->nama_item,
                            'satuan' => $detail->satuan,
                            'qty_disetujui' => $detail->qty_disetujui,
                            'harga_satuan' => $detail->harga_satuan,
                            'total_harga' => $detail->total_harga,
                        ];
                    }),
                ],
            ]);
        }

        // Manual PO creation
        $suppliers = Supplier::active()
            ->select('supplier_id', 'nama_supplier', 'kontak_person', 'telepon')
            ->orderBy('nama_supplier')
            ->get();

        return Inertia::render('pembelian/create', [
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pengadaan_id' => 'nullable|exists:pengadaan,pengadaan_id',
            'supplier_id' => 'required|exists:supplier,supplier_id',
            'tanggal_pembelian' => 'required|date',
            'tanggal_jatuh_tempo' => 'nullable|date|after:tanggal_pembelian',
            'pajak' => 'numeric|min:0|max:999999999.99',
            'diskon' => 'numeric|min:0|max:999999999.99',
            'metode_pembayaran' => 'nullable|in:cash,transfer,credit,cheque',
            'terms_conditions' => 'nullable|string|max:1000',
            'catatan' => 'nullable|string|max:500',
            'detail' => 'required|array|min:1',
            'detail.*.pengadaan_detail_id' => 'nullable|exists:pengadaan_detail,pengadaan_detail_id',
            'detail.*.item_type' => 'required|in:bahan_baku,produk',
            'detail.*.item_id' => 'required|string',
            'detail.*.nama_item' => 'required|string|max:255',
            'detail.*.satuan' => 'required|string|max:50',
            'detail.*.qty_po' => 'required|integer|min:1',
            'detail.*.harga_satuan' => 'required|numeric|min:0',
            'detail.*.spesifikasi' => 'nullable|string|max:500',
            'detail.*.catatan' => 'nullable|string|max:255',
        ]);

        // Validate detail quantities if creating from pengadaan
        if ($validated['pengadaan_id']) {
            $quantityErrors = $this->validateDetailQuantities($validated['detail']);
            if (!empty($quantityErrors)) {
                return back()->withErrors($quantityErrors)->withInput();
            }
        }

        try {
            DB::beginTransaction();

            // Generate nomor PO
            $nomorPo = $this->generateNomorPO();

            // Calculate totals
            $subtotal = collect($validated['detail'])->sum(function ($item) {
                return $item['qty_po'] * $item['harga_satuan'];
            });

            $totalBiaya = $subtotal + ($validated['pajak'] ?? 0) - ($validated['diskon'] ?? 0);

            // Create pembelian
            $pembelian = Pembelian::create([
                'pengadaan_id' => $validated['pengadaan_id'],
                'supplier_id' => $validated['supplier_id'],
                'nomor_po' => $nomorPo,
                'tanggal_pembelian' => $validated['tanggal_pembelian'],
                'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                'subtotal' => $subtotal,
                'pajak' => $validated['pajak'] ?? 0,
                'diskon' => $validated['diskon'] ?? 0,
                'total_biaya' => $totalBiaya,
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'terms_conditions' => $validated['terms_conditions'],
                'catatan' => $validated['catatan'],
                'created_by' => Auth::id(),
            ]);

            // Create pembelian detail
            foreach ($validated['detail'] as $detail) {
                PembelianDetail::create([
                    'pembelian_id' => $pembelian->pembelian_id,
                    'pengadaan_detail_id' => $detail['pengadaan_detail_id'],
                    'item_type' => $detail['item_type'],
                    'item_id' => $detail['item_id'],
                    'nama_item' => $detail['nama_item'],
                    'satuan' => $detail['satuan'],
                    'qty_po' => $detail['qty_po'],
                    'harga_satuan' => $detail['harga_satuan'],
                    'spesifikasi' => $detail['spesifikasi'],
                    'catatan' => $detail['catatan'],
                ]);
            }

            // Update pengadaan status if this PO is from pengadaan
            if ($validated['pengadaan_id']) {
                $pengadaan = Pengadaan::find($validated['pengadaan_id']);
                if ($pengadaan && $pengadaan->status === 'approved') {
                    $pengadaan->update([
                        'status' => 'po_sent',
                        'nomor_po' => $nomorPo,
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('pembelian.show', $pembelian->pembelian_id)
                ->with('flash', [
                    'message' => 'Purchase Order berhasil dibuat!',
                    'type' => 'success'
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('flash', [
                    'message' => 'Gagal membuat Purchase Order: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pembelian $pembelian)
    {
        $pembelian->load([
            'supplier',
            'pengadaan',
            'detail.pengadaanDetail',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('pembelian/show', [
            'pembelian' => [
                'pembelian_id' => $pembelian->pembelian_id,
                'pengadaan_id' => $pembelian->pengadaan_id,
                'supplier_id' => $pembelian->supplier_id,
                'nomor_po' => $pembelian->nomor_po,
                'tanggal_pembelian' => $pembelian->tanggal_pembelian?->format('Y-m-d'),
                'tanggal_jatuh_tempo' => $pembelian->tanggal_jatuh_tempo?->format('Y-m-d'),
                'subtotal' => $pembelian->subtotal,
                'pajak' => $pembelian->pajak,
                'diskon' => $pembelian->diskon,
                'total_biaya' => $pembelian->total_biaya,
                'status' => $pembelian->status,
                'status_label' => $pembelian->status_label,
                'metode_pembayaran' => $pembelian->metode_pembayaran,
                'terms_conditions' => $pembelian->terms_conditions,
                'catatan' => $pembelian->catatan,
                'supplier' => [
                    'supplier_id' => $pembelian->supplier->supplier_id,
                    'nama_supplier' => $pembelian->supplier->nama_supplier,
                    'kontak_person' => $pembelian->supplier->kontak_person,
                    'telepon' => $pembelian->supplier->telepon,
                    'email' => $pembelian->supplier->email,
                ],
                'pengadaan' => $pembelian->pengadaan ? [
                    'pengadaan_id' => $pembelian->pengadaan->pengadaan_id,
                    'jenis_pengadaan' => $pembelian->pengadaan->jenis_pengadaan,
                    'prioritas' => $pembelian->pengadaan->prioritas,
                ] : null,
                'detail' => $pembelian->detail->map(function ($detail) {
                    return [
                        'pembelian_detail_id' => $detail->pembelian_detail_id,
                        'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                        'item_type' => $detail->item_type,
                        'item_id' => $detail->item_id,
                        'nama_item' => $detail->nama_item,
                        'satuan' => $detail->satuan,
                        'qty_po' => $detail->qty_po,
                        'qty_diterima' => $detail->qty_diterima,
                        'harga_satuan' => $detail->harga_satuan,
                        'total_harga' => $detail->total_harga,
                        'spesifikasi' => $detail->spesifikasi,
                        'catatan' => $detail->catatan,
                        'outstanding_qty' => $detail->outstanding_qty,
                        'received_percentage' => $detail->received_percentage,
                        'is_fully_received' => $detail->is_fully_received,
                    ];
                }),
                'received_percentage' => $pembelian->getReceivedPercentage(),
                'is_fully_received' => $pembelian->isFullyReceived(),
                'can_edit' => $pembelian->canBeEdited(),
                'can_cancel' => $pembelian->canBeCancelled(),
                'can_receive' => $pembelian->canBeReceived(),
                'created_by' => $pembelian->createdBy?->nama_lengkap,
                'updated_by' => $pembelian->updatedBy?->nama_lengkap,
                'created_at' => $pembelian->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $pembelian->updated_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pembelian $pembelian)
    {
        if (!$pembelian->canBeEdited()) {
            return redirect()->route('pembelian.index')
                ->with('flash', [
                    'message' => 'Purchase Order tidak dapat diedit karena statusnya sudah ' . $pembelian->status_label,
                    'type' => 'error'
                ]);
        }

        $pembelian->load(['detail', 'supplier']);

        $suppliers = Supplier::active()
            ->select('supplier_id', 'nama_supplier', 'kontak_person', 'telepon')
            ->orderBy('nama_supplier')
            ->get();

        return Inertia::render('pembelian/edit', [
            'pembelian' => [
                'pembelian_id' => $pembelian->pembelian_id,
                'pengadaan_id' => $pembelian->pengadaan_id,
                'supplier_id' => $pembelian->supplier_id,
                'nomor_po' => $pembelian->nomor_po,
                'tanggal_pembelian' => $pembelian->tanggal_pembelian?->format('Y-m-d'),
                'tanggal_jatuh_tempo' => $pembelian->tanggal_jatuh_tempo?->format('Y-m-d'),
                'pajak' => $pembelian->pajak,
                'diskon' => $pembelian->diskon,
                'metode_pembayaran' => $pembelian->metode_pembayaran,
                'terms_conditions' => $pembelian->terms_conditions,
                'catatan' => $pembelian->catatan,
                'detail' => $pembelian->detail->map(function ($detail) {
                    return [
                        'pembelian_detail_id' => $detail->pembelian_detail_id,
                        'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                        'item_type' => $detail->item_type,
                        'item_id' => $detail->item_id,
                        'nama_item' => $detail->nama_item,
                        'satuan' => $detail->satuan,
                        'qty_po' => $detail->qty_po,
                        'harga_satuan' => $detail->harga_satuan,
                        'spesifikasi' => $detail->spesifikasi,
                        'catatan' => $detail->catatan,
                    ];
                }),
            ],
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pembelian $pembelian)
    {
        if (!$pembelian->canBeEdited()) {
            return redirect()->route('pembelian.show', $pembelian->pembelian_id)
                ->with('flash', [
                    'message' => 'Purchase Order tidak dapat diedit karena statusnya sudah ' . $pembelian->status_label,
                    'type' => 'error'
                ]);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:supplier,supplier_id',
            'tanggal_pembelian' => 'required|date',
            'tanggal_jatuh_tempo' => 'nullable|date|after:tanggal_pembelian',
            'pajak' => 'numeric|min:0|max:999999999.99',
            'diskon' => 'numeric|min:0|max:999999999.99',
            'metode_pembayaran' => 'nullable|in:cash,transfer,credit,cheque',
            'terms_conditions' => 'nullable|string|max:1000',
            'catatan' => 'nullable|string|max:500',
            'detail' => 'required|array|min:1',
            'detail.*.pembelian_detail_id' => 'nullable|exists:pembelian_detail,pembelian_detail_id',
            'detail.*.qty_po' => 'required|integer|min:1',
            'detail.*.harga_satuan' => 'required|numeric|min:0',
            'detail.*.spesifikasi' => 'nullable|string|max:500',
            'detail.*.catatan' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Update pembelian
            $pembelian->update([
                'supplier_id' => $validated['supplier_id'],
                'tanggal_pembelian' => $validated['tanggal_pembelian'],
                'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                'pajak' => $validated['pajak'] ?? 0,
                'diskon' => $validated['diskon'] ?? 0,
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'terms_conditions' => $validated['terms_conditions'],
                'catatan' => $validated['catatan'],
                'updated_by' => Auth::id(),
            ]);

            // Update detail
            foreach ($validated['detail'] as $detailData) {
                if (isset($detailData['pembelian_detail_id'])) {
                    // Update existing detail
                    $detail = PembelianDetail::find($detailData['pembelian_detail_id']);
                    if ($detail) {
                        $detail->update([
                            'qty_po' => $detailData['qty_po'],
                            'harga_satuan' => $detailData['harga_satuan'],
                            'spesifikasi' => $detailData['spesifikasi'],
                            'catatan' => $detailData['catatan'],
                        ]);
                    }
                }
            }

            // Recalculate totals
            $pembelian->calculateTotals();

            DB::commit();

            return redirect()->route('pembelian.show', $pembelian->pembelian_id)
                ->with('flash', [
                    'message' => 'Purchase Order berhasil diupdate!',
                    'type' => 'success'
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('flash', [
                    'message' => 'Gagal mengupdate Purchase Order: ' . $e->getMessage(),
                    'type' => 'error'
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
                ->with('flash', [
                    'message' => 'Purchase Order tidak dapat dibatalkan karena statusnya sudah ' . $pembelian->status_label,
                    'type' => 'error'
                ]);
        }

        try {
            DB::beginTransaction();

            // Update status to cancelled instead of deleting
            $pembelian->update([
                'status' => 'cancelled',
                'updated_by' => Auth::id(),
            ]);

            // If linked to pengadaan, revert pengadaan status
            if ($pembelian->pengadaan_id) {
                $pengadaan = Pengadaan::find($pembelian->pengadaan_id);
                if ($pengadaan && $pengadaan->status === 'po_sent') {
                    $pengadaan->update([
                        'status' => 'approved',
                        'nomor_po' => null,
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('pembelian.index')
                ->with('flash', [
                    'message' => 'Purchase Order berhasil dibatalkan!',
                    'type' => 'success'
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('flash', [
                    'message' => 'Gagal membatalkan Purchase Order: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    /**
     * Update purchase order status
     */
    public function updateStatus(Request $request, Pembelian $pembelian)
    {
        $validated = $request->validate([
            'status' => 'required|in:sent,confirmed,received,invoiced,paid,cancelled',
            'catatan' => 'nullable|string|max:500',
        ]);

        if (!$pembelian->updateStatus($validated['status'])) {
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Status tidak dapat diubah dari ' . $pembelian->status_label . ' ke ' . $validated['status'],
                    'type' => 'error'
                ]);
        }

        // Add status change note
        if (!empty($validated['catatan'])) {
            $currentNote = $pembelian->catatan ?? '';
            $newNote = $currentNote . "\n" . date('Y-m-d H:i') . " - Status changed to {$validated['status']}: " . $validated['catatan'];
            $pembelian->update([
                'catatan' => $newNote,
                'updated_by' => Auth::id(),
            ]);
        }

        return redirect()->back()
            ->with('flash', [
                'message' => 'Status Purchase Order berhasil diupdate!',
                'type' => 'success'
            ]);
    }

    /**
     * Receive items from purchase order
     */
    public function receive(Request $request, Pembelian $pembelian)
    {
        if (!$pembelian->canBeReceived()) {
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Purchase Order tidak dapat diterima karena statusnya ' . $pembelian->status_label,
                    'type' => 'error'
                ]);
        }

        $validated = $request->validate([
            'detail' => 'required|array',
            'detail.*.pembelian_detail_id' => 'required|exists:pembelian_detail,pembelian_detail_id',
            'detail.*.qty_diterima' => 'required|integer|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['detail'] as $detailData) {
                $detail = PembelianDetail::find($detailData['pembelian_detail_id']);
                if ($detail && $detail->pembelian_id === $pembelian->pembelian_id) {
                    $detail->receiveQuantity($detailData['qty_diterima']);
                }
            }

            // Update pembelian status based on received items
            if ($pembelian->isFullyReceived()) {
                $pembelian->updateStatus('received');
            } else {
                // Create custom status for partial received if not exists
                $pembelian->update(['status' => 'partial_received']);
            }

            // Add receive note
            if (!empty($validated['catatan'])) {
                $currentNote = $pembelian->catatan ?? '';
                $newNote = $currentNote . "\n" . date('Y-m-d H:i') . " - Items received: " . $validated['catatan'];
                $pembelian->update(['catatan' => $newNote]);
            }

            DB::commit();

            return redirect()->back()
                ->with('flash', [
                    'message' => 'Items berhasil diterima!',
                    'type' => 'success'
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('flash', [
                    'message' => 'Gagal menerima items: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    /**
     * Get remaining quantity for pengadaan detail
     */
    private function getRemainingQuantity(string $pengadaanDetailId): int
    {
        $pengadaanDetail = PengadaanDetail::find($pengadaanDetailId);
        if (!$pengadaanDetail) {
            return 0;
        }

        $qtyTelahDipesan = PembelianDetail::where('pengadaan_detail_id', $pengadaanDetailId)
            ->sum('qty_po');

        return max(0, $pengadaanDetail->qty_disetujui - $qtyTelahDipesan);
    }

    /**
     * Validate detail quantities against pengadaan
     */
    private function validateDetailQuantities(array $details): array
    {
        $errors = [];

        foreach ($details as $index => $detail) {
            if (isset($detail['pengadaan_detail_id']) && $detail['pengadaan_detail_id']) {
                $remainingQty = $this->getRemainingQuantity($detail['pengadaan_detail_id']);

                if ($detail['qty_po'] > $remainingQty) {
                    $errors["detail.{$index}.qty_po"] = "Kuantitas melebihi sisa yang tersedia ({$remainingQty} {$detail['satuan']})";
                }
            }
        }

        return $errors;
    }

    /**
     * Get pengadaan details with remaining quantities
     */
    public function createFromPengadaan(Pengadaan $pengadaan)
    {
        if ($pengadaan->status !== 'approved') {
            abort(403, 'Pengadaan belum disetujui');
        }

        $suppliers = Supplier::active()
            ->select('supplier_id', 'nama_supplier', 'alamat', 'telepon', 'email', 'kontak_person')
            ->orderBy('nama_supplier')
            ->get();

        // Get pengadaan details with remaining quantities
        $pengadaanDetails = $pengadaan->detail->map(function ($detail) {
            $remainingQty = $this->getRemainingQuantity($detail->pengadaan_detail_id);

            return [
                'pengadaan_detail_id' => $detail->pengadaan_detail_id,
                'item_type' => $detail->item_type,
                'item_id' => $detail->item_id,
                'nama_item' => $detail->nama_item,
                'satuan' => $detail->satuan,
                'qty_disetujui' => $detail->qty_disetujui,
                'qty_tersisa' => $remainingQty,
                'harga_satuan' => $detail->harga_satuan,
                'total_harga' => $detail->total_harga,
                'alasan_kebutuhan' => $detail->alasan_kebutuhan,
                'catatan' => $detail->catatan,
            ];
        })->filter(function ($detail) {
            // Only include details that still have remaining quantity
            return $detail['qty_tersisa'] > 0;
        });

        if ($pengadaanDetails->isEmpty()) {
            return redirect()->route('pengadaan.show', $pengadaan->pengadaan_id)
                ->with('flash', [
                    'message' => 'Semua item dalam pengadaan ini sudah dipesan.',
                    'type' => 'warning'
                ]);
        }

        return Inertia::render('pembelian/create', [
            'suppliers' => $suppliers,
            'pengadaan' => [
                'pengadaan_id' => $pengadaan->pengadaan_id,
                'nomor_pengadaan' => $pengadaan->pengadaan_id,
                'tanggal_pengadaan' => $pengadaan->tanggal_pengadaan?->format('Y-m-d'),
                'total_biaya' => $pengadaan->total_biaya,
                'supplier_id' => $pengadaan->supplier_id,
            ],
            'pengadaanDetails' => $pengadaanDetails->values(),
        ]);
    }

    /**
     * Generate PO number
     */
    private function generateNomorPO(): string
    {
        $prefix = 'PO';
        $date = date('Ymd');

        $lastPo = Pembelian::where('nomor_po', 'like', "{$prefix}-{$date}-%")
            ->orderBy('nomor_po', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->nomor_po, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}
