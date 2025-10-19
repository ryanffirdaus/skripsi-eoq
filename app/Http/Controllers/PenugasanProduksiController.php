<?php

namespace App\Http\Controllers;

use App\Models\PenugasanProduksi;
use App\Models\PengadaanDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class PenugasanProduksiController extends Controller
{
    /**
     * Display a listing of production assignments
     *
     * Admin/Manager melihat SEMUA penugasan
     * Supervisor melihat penugasan yang dia buat
     * Worker melihat penugasan yang ditugaskan ke dia
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PenugasanProduksi::query();

        // Get sort parameters from request
        $sortBy = $request->input('sort_by', 'deadline');
        $sortDirection = $request->input('sort_direction', 'desc');
        $perPage = $request->input('per_page', 10);
        $mode = $request->input('mode', 'all');

        // Filter berdasarkan role dan mode
        if ($user->role_id === 'R01' || $user->role_id === 'R09') {
            // Admin/Manajer RnD - lihat SEMUA penugasan (mode=all)
            // Jika mode=assigned, tampilkan hanya tugas yang sudah ditugaskan ke workers (filter: status != cancelled)
            if ($mode === 'assigned') {
                $query->where('status', '!=', 'cancelled');
            }
            // Else: show all penugasan tanpa filter tambahan
        } elseif ($user->role_id === 'R03') {
            // Staf RnD - lihat tugas mereka sendiri
            $query->where('user_id', $user->user_id);
        } else {
            // Others - tidak punya akses
            $query->whereRaw('1 = 0');
        }

        // Filter berdasarkan status dari request
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Search filter
        if ($request->has('search') && $request->search !== '') {
            $query->whereHas('pengadaanDetail', function ($q) use ($request) {
                $q->where('catatan', 'like', '%' . $request->search . '%')
                    ->orWhere('nama_item', 'like', '%' . $request->search . '%');
            });
        }

        // Filter berdasarkan deadline
        if ($request->has('deadline_from') && $request->deadline_from !== '') {
            $query->whereDate('deadline', '>=', $request->deadline_from);
        }

        if ($request->has('deadline_to') && $request->deadline_to !== '') {
            $query->whereDate('deadline', '<=', $request->deadline_to);
        }

        $penugasan = $query->with([
            'pengadaanDetail.produk',
            'pengadaanDetail.bahanBaku',
            'user',
            'createdBy',
        ])->orderBy($sortBy, $sortDirection)->paginate($perPage)->withQueryString();

        return Inertia::render('penugasan-produksi/index', [
            'penugasan' => $penugasan,
            'userRole' => $user->role_id,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => (int) $perPage,
                'mode' => $mode,
            ],
        ]);
    }

    /**
     * Show the form for creating a new production assignment
     *
     * Hanya accessible oleh Supervisor/Manager
     */
    public function create()
    {
        // Get outstanding PengadaanDetails untuk PRODUK saja (yang belum selesai diproduksi)
        $pengadaanDetails = PengadaanDetail::with(['pengadaan', 'produk'])
            ->where('jenis_barang', 'produk')
            ->whereHas('pengadaan', function ($query) {
                $query->whereIn('status', ['disetujui_finance', 'diproses']);
            })
            ->get();

        // Get production workers (role_id = R03 = Staf RnD)
        $workers = User::where('role_id', 'R03')
            ->orderBy('nama_lengkap')
            ->get(['user_id', 'nama_lengkap']);

        return Inertia::render('penugasan-produksi/create', [
            'pengadaanDetails' => $pengadaanDetails,
            'workers' => $workers,
        ]);
    }

    /**
     * Store a newly created production assignment in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pengadaan_detail_id' => 'required|exists:pengadaan_detail,pengadaan_detail_id',
            'user_id' => 'required|exists:users,user_id',
            'jumlah_produksi' => 'required|integer|min:1',
            'deadline' => 'required|date|after_or_equal:today',
            'catatan' => 'nullable|string|max:500',
        ]);

        $pengadaanDetail = PengadaanDetail::findOrFail($validated['pengadaan_detail_id']);

        // Validasi bahwa pengadaan detail ini harus untuk produk
        if ($pengadaanDetail->jenis_barang !== 'produk') {
            return back()->withErrors([
                'pengadaan_detail_id' => 'Penugasan produksi hanya untuk produk, bukan bahan baku'
            ])->withInput();
        }

        // Validasi jumlah tidak melebihi qty_disetujui
        $maxQty = $pengadaanDetail->qty_disetujui ?? $pengadaanDetail->qty_diminta;
        if ($validated['jumlah_produksi'] > $maxQty) {
            return back()->withErrors([
                'jumlah_produksi' => "Jumlah produksi tidak boleh melebihi $maxQty"
            ])->withInput();
        }

        PenugasanProduksi::create([
            'pengadaan_detail_id' => $validated['pengadaan_detail_id'],
            'user_id' => $validated['user_id'],
            'jumlah_produksi' => $validated['jumlah_produksi'],
            'deadline' => $validated['deadline'],
            'catatan' => $validated['catatan'],
            'status' => 'assigned',
        ]);

        return redirect()->route('penugasan-produksi.index')
            ->with('success', 'Penugasan produksi berhasil dibuat');
    }

    /**
     * Display the specified production assignment
     */
    public function show(PenugasanProduksi $penugasan_produksi)
    {
        $penugasan_produksi->load([
            'pengadaanDetail.pengadaan',
            'pengadaanDetail.bahanBaku',
            'pengadaanDetail.produk',
            'user',
            'createdBy',
            'updatedBy',
            'deletedBy'
        ]);

        return Inertia::render('penugasan-produksi/show', [
            'penugasan' => $penugasan_produksi,
        ]);
    }

    /**
     * Show the form for editing the specified resource
     *
     * Worker: hanya bisa ubah status tugas mereka
     * Supervisor: hanya bisa edit tugas yang belum completed
     */
    public function edit(PenugasanProduksi $penugasan_produksi)
    {
        $user = Auth::user();

        // Staf RnD: hanya bisa edit tugas mereka sendiri
        if ($user->role_id === 'R03' && $penugasan_produksi->user_id !== $user->user_id) {
            abort(403, 'Unauthorized');
        }

        if ($penugasan_produksi->status === 'completed' || $penugasan_produksi->status === 'cancelled') {
            return back()->with('error', 'Tidak dapat mengubah penugasan yang sudah final');
        }

        $penugasan_produksi->load([
            'pengadaanDetail.pengadaan',
            'pengadaanDetail.bahanBaku',
            'pengadaanDetail.produk',
            'user'
        ]);

        // Get production workers (hanya jika admin/manajer rnd)
        $workers = null;
        if ($user->role_id !== 'R03') {
            $workers = User::where('role_id', 'R03')
                ->orderBy('nama_lengkap')
                ->get(['user_id', 'nama_lengkap']);
        }

        return Inertia::render('penugasan-produksi/edit', [
            'penugasan' => $penugasan_produksi,
            'workers' => $workers,
            'isWorker' => $user->role_id === 'R03',
        ]);
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, PenugasanProduksi $penugasan_produksi)
    {
        $user = Auth::user();

        // Check if status is already final
        if ($penugasan_produksi->status === 'completed' || $penugasan_produksi->status === 'cancelled') {
            return back()->with('error', 'Tidak dapat mengubah penugasan yang sudah final');
        }

        // Staf RnD hanya bisa ubah status
        if ($user->role_id === 'R03') {
            if ($penugasan_produksi->user_id !== $user->user_id) {
                abort(403, 'Unauthorized');
            }

            $validated = $request->validate([
                'status' => 'required|in:assigned,in_progress,completed,cancelled',
            ]);

            // Validasi transition
            if (!$penugasan_produksi->isValidStatusTransition($validated['status'])) {
                return back()->withErrors([
                    'status' => 'Transisi status tidak valid dari ' . $penugasan_produksi->status
                ])->withInput();
            }

            $penugasan_produksi->update([
                'status' => $validated['status'],
            ]);
        } else {
            // Supervisor bisa ubah semua field kecuali created_by
            $validated = $request->validate([
                'user_id' => 'required|exists:users,user_id',
                'jumlah_produksi' => 'required|integer|min:1',
                'deadline' => 'required|date|after_or_equal:today',
                'status' => 'required|in:assigned,in_progress,completed,cancelled',
                'catatan' => 'nullable|string|max:500',
            ]);

            // Validasi transition
            if (!$penugasan_produksi->isValidStatusTransition($validated['status'])) {
                return back()->withErrors([
                    'status' => 'Transisi status tidak valid dari ' . $penugasan_produksi->status
                ])->withInput();
            }

            $pengadaanDetail = $penugasan_produksi->pengadaanDetail;
            $maxQty = $pengadaanDetail->qty_disetujui ?? $pengadaanDetail->qty_diminta;

            if ($validated['jumlah_produksi'] > $maxQty) {
                return back()->withErrors([
                    'jumlah_produksi' => "Jumlah produksi tidak boleh melebihi $maxQty"
                ])->withInput();
            }

            $penugasan_produksi->update([
                'user_id' => $validated['user_id'],
                'jumlah_produksi' => $validated['jumlah_produksi'],
                'deadline' => $validated['deadline'],
                'status' => $validated['status'],
                'catatan' => $validated['catatan'],
            ]);
        }

        return redirect()->route('penugasan-produksi.index')
            ->with('success', 'Penugasan produksi berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage (Soft Delete)
     *
     * Hanya bisa dihapus oleh supervisor/manager
     */
    public function destroy(PenugasanProduksi $penugasan_produksi)
    {
        if ($penugasan_produksi->status === 'completed') {
            return back()->with('error', 'Tidak dapat menghapus penugasan yang sudah selesai');
        }

        $penugasan_produksi->delete();

        return redirect()->route('penugasan-produksi.index')
            ->with('success', 'Penugasan produksi berhasil dihapus');
    }

    /**
     * Update production assignment status via AJAX
     *
     * Quick action untuk worker mengubah status
     */
    public function updateStatus(Request $request, PenugasanProduksi $penugasan_produksi)
    {
        $user = Auth::user();

        // Staf RnD hanya bisa update tugas mereka sendiri
        if ($user->role_id === 'R03' && $penugasan_produksi->user_id !== $user->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:assigned,in_progress,completed,cancelled',
        ]);

        // Validasi transition
        if (!$penugasan_produksi->isValidStatusTransition($validated['status'])) {
            return response()->json([
                'error' => 'Transisi status tidak valid dari ' . $penugasan_produksi->status
            ], 422);
        }

        $penugasan_produksi->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui',
            'penugasan' => $penugasan_produksi->fresh()->load([
                'user',
                'pengadaanDetail',
                'createdBy',
                'updatedBy'
            ])
        ]);
    }

    /**
     * Get outstanding production assignments for a PengadaanDetail
     */
    public function getOutstandingByPengadaanDetail($pengadaanDetailId)
    {
        $penugasan = PenugasanProduksi::byPengadaanDetail($pengadaanDetailId)
            ->outstanding()
            ->with(['user', 'pengadaanDetail'])
            ->get();

        return response()->json($penugasan);
    }

    /**
     * Get production assignments for authenticated worker
     */
    public function myAssignments(Request $request)
    {
        $user = Auth::user();

        $query = PenugasanProduksi::byUser($user->user_id);

        if ($request->has('status') && $request->status !== '') {
            $query->byStatus($request->status);
        }

        $penugasan = $query->with([
            'pengadaanDetail.pengadaan',
            'createdBy'
        ])->orderBy('deadline', 'asc')->get();

        return response()->json($penugasan);
    }

    /**
     * Dashboard statistics untuk supervisor
     */
    public function statistics()
    {
        $user = Auth::user();

        $stats = [
            'total_created' => PenugasanProduksi::where('created_by', $user->user_id)->count(),
            'assigned' => PenugasanProduksi::where('created_by', $user->user_id)->byStatus('assigned')->count(),
            'in_progress' => PenugasanProduksi::where('created_by', $user->user_id)->byStatus('in_progress')->count(),
            'completed' => PenugasanProduksi::where('created_by', $user->user_id)->byStatus('completed')->count(),
            'cancelled' => PenugasanProduksi::where('created_by', $user->user_id)->byStatus('cancelled')->count(),
            'overdue' => PenugasanProduksi::where('created_by', $user->user_id)
                ->whereDate('deadline', '<', Carbon::today())
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Dashboard statistics untuk worker
     */
    public function myStatistics()
    {
        $user = Auth::user();

        $stats = [
            'total' => PenugasanProduksi::where('user_id', $user->user_id)->count(),
            'assigned' => PenugasanProduksi::where('user_id', $user->user_id)->byStatus('assigned')->count(),
            'in_progress' => PenugasanProduksi::where('user_id', $user->user_id)->byStatus('in_progress')->count(),
            'completed' => PenugasanProduksi::where('user_id', $user->user_id)->byStatus('completed')->count(),
            'cancelled' => PenugasanProduksi::where('user_id', $user->user_id)->byStatus('cancelled')->count(),
            'overdue' => PenugasanProduksi::where('user_id', $user->user_id)
                ->whereDate('deadline', '<', Carbon::today())
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count(),
        ];

        return response()->json($stats);
    }
}
