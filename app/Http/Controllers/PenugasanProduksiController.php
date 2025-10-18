<?php

namespace App\Http\Controllers;

use App\Models\Pengadaan;
use App\Models\PenugasanProduksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PenugasanProduksiController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = PenugasanProduksi::with(['pengadaan.detail.produk', 'assignedToUser', 'assignedByUser']);

        // Filter by role
        if ($user->role->nama_role === 'Staf RnD') {
            $query->where('assigned_to', $user->user_id);
        }

        // Search filter
        if ($request->search) {
            $query->whereHas('pengadaan.detail.produk', function($q) use ($request) {
                $q->where('nama_produk', 'like', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $penugasan = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('penugasan-produksi/index', [
            'penugasan' => $penugasan,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        // Get approved produk pengadaan that haven't been assigned yet
        $pengadaanList = Pengadaan::where('jenis_pengadaan', 'Produksi')
            ->where('status', 'disetujui_finance')
            ->with(['detail.produk'])
            ->get()
            ->map(function($pengadaan) {
                return [
                    'pengadaan_id' => $pengadaan->pengadaan_id,
                    'kode' => $pengadaan->kode_pengadaan,
                    'produk' => $pengadaan->detail->first()->produk->nama_produk ?? 'N/A',
                    'qty' => $pengadaan->detail->first()->qty_diminta ?? 0,
                ];
            });

        // Get Staf RnD users
        $stafRnD = User::whereHas('role', function($q) {
            $q->where('nama_role', 'Staf RnD');
        })->get(['user_id', 'name']);

        return Inertia::render('penugasan-produksi/create', [
            'pengadaanList' => $pengadaanList,
            'stafRnD' => $stafRnD,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pengadaan_id' => 'required|exists:pengadaan,pengadaan_id',
            'assigned_to' => 'required|exists:users,user_id',
            'qty_assigned' => 'required|integer|min:1',
            'deadline' => 'required|date|after:today',
            'catatan' => 'nullable|string',
        ]);

        PenugasanProduksi::create([
            'pengadaan_id' => $request->pengadaan_id,
            'assigned_to' => $request->assigned_to,
            'assigned_by' => auth()->id(),
            'qty_assigned' => $request->qty_assigned,
            'deadline' => $request->deadline,
            'catatan' => $request->catatan,
            'status' => 'assigned',
        ]);

        return redirect()->route('penugasan-produksi.index')->with('flash', [
            'message' => 'Penugasan produksi berhasil dibuat.',
            'type' => 'success'
        ]);
    }

    public function show(PenugasanProduksi $penugasan_produksi)
    {
        $penugasan_produksi->load(['pengadaan.detail.produk', 'assignedToUser', 'assignedByUser']);

        return Inertia::render('penugasan-produksi/show', [
            'penugasan' => $penugasan_produksi,
        ]);
    }

    public function edit(PenugasanProduksi $penugasan_produksi)
    {
        $user = auth()->user();

        // Staf can only update if it's their assignment
        if ($user->role->nama_role === 'Staf RnD' && $penugasan_produksi->assigned_to !== $user->user_id) {
            abort(403, 'Unauthorized');
        }

        $penugasan_produksi->load(['pengadaan.detail.produk', 'assignedToUser']);

        $stafRnD = null;
        if ($user->role->nama_role === 'Manajer RnD') {
            $stafRnD = User::whereHas('role', function($q) {
                $q->where('nama_role', 'Staf RnD');
            })->get(['user_id', 'name']);
        }

        return Inertia::render('penugasan-produksi/edit', [
            'penugasan' => $penugasan_produksi,
            'stafRnD' => $stafRnD,
            'isStaf' => $user->role->nama_role === 'Staf RnD',
        ]);
    }

    public function update(Request $request, PenugasanProduksi $penugasan_produksi)
    {
        $user = auth()->user();

        if ($user->role->nama_role === 'Staf RnD') {
            // Staf can only update qty_completed and status
            $request->validate([
                'qty_completed' => 'required|integer|min:0|max:' . $penugasan_produksi->qty_assigned,
                'status' => 'required|in:in_progress,completed',
            ]);

            $penugasan_produksi->update([
                'qty_completed' => $request->qty_completed,
                'status' => $request->status,
            ]);
        } else {
            // Manajer can update everything except status (auto-calculated)
            $request->validate([
                'assigned_to' => 'required|exists:users,user_id',
                'qty_assigned' => 'required|integer|min:1',
                'deadline' => 'required|date',
                'catatan' => 'nullable|string',
            ]);

            $penugasan_produksi->update([
                'assigned_to' => $request->assigned_to,
                'qty_assigned' => $request->qty_assigned,
                'deadline' => $request->deadline,
                'catatan' => $request->catatan,
            ]);
        }

        return redirect()->route('penugasan-produksi.index')->with('flash', [
            'message' => 'Penugasan produksi berhasil diperbarui.',
            'type' => 'success'
        ]);
    }

    public function destroy(PenugasanProduksi $penugasan_produksi)
    {
        $penugasan_produksi->update(['status' => 'cancelled']);
        $penugasan_produksi->delete();

        return redirect()->route('penugasan-produksi.index')->with('flash', [
            'message' => 'Penugasan produksi berhasil dibatalkan.',
            'type' => 'success'
        ]);
    }
}
