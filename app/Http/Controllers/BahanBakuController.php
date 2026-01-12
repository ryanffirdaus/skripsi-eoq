<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class BahanBakuController extends Controller
{
    /**
     * Display a listing of the bahan baku.
     */
    public function index(Request $request)
    {
        // Get query parameters with default values
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'bahan_baku_id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $perPage = $request->input('per_page', 10);

        // Build the query
        $query = BahanBaku::query();

        // Apply search filter if a search term is present
        if ($search) {
            $query->where('nama_bahan', 'like', '%' . $search . '%')
                ->orWhere('lokasi_bahan', 'like', '%' . $search . '%')
                ->orWhere('bahan_baku_id', 'like', '%' . $search . '%');
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Paginate the results
        $bahanBakus = $query->paginate($perPage)->withQueryString();

        // Check permissions
        $canCreate = $this->hasRoles(['R01', 'R07', 'R02']); // Admin, Manajer Gudang, Staf Gudang
        $canEdit = $this->hasRoles(['R01', 'R07', 'R02']);
        $canDelete = $this->hasRoles(['R01', 'R07', 'R02']);

        return Inertia::render('bahan-baku/index', [
            'bahanBaku' => $bahanBakus,
            'filters' => [
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => (int) $perPage,
            ],
            'permissions' => [
                'canCreate' => $canCreate,
                'canEdit' => $canEdit,
                'canDelete' => $canDelete,
            ],
            'flash' => [
                'message' => session('message'),
                'type' => session('type', 'success'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new bahan baku.
     */
    public function create()
    {
        // Authorization: Admin (R01), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk membuat bahan baku baru.');
        }

        return Inertia::render('bahan-baku/create');
    }

    /**
     * Display the specified bahan baku.
     */
    public function show(BahanBaku $bahanBaku)
    {
        $bahanBaku->load([
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        // Check permissions
        $canEdit = $this->hasRoles(['R01', 'R07', 'R02']); // Admin, Manajer Gudang
        $canDelete = $this->hasRoles(['R01', 'R07', 'R02']);

        return Inertia::render('bahan-baku/show', [
            'bahanBaku' => $bahanBaku,
            'permissions' => [
                'canEdit' => $canEdit,
                'canDelete' => $canDelete,
            ],
        ]);
    }

    /**
     * Store a newly created bahan baku in storage.
     */
    public function store(Request $request)
    {
        // Authorization: Admin (R01), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk menyimpan bahan baku.');
        }

        $validated = $request->validate([
            'nama_bahan' => ['required', 'string', 'max:255'],
            'lokasi_bahan' => ['required', 'string', 'max:255'],
            'stok_bahan' => ['nullable', 'numeric', 'min:0'],
            'satuan_bahan' => ['required', 'string', 'max:50'],
            'harga_bahan' => ['required', 'numeric', 'min:0'],
        ]);

        $bahanBaku = BahanBaku::create([
            'nama_bahan' => $validated['nama_bahan'],
            'lokasi_bahan' => $validated['lokasi_bahan'],
            'stok_bahan' => $validated['stok_bahan'] ?? 0,
            'satuan_bahan' => $validated['satuan_bahan'],
            'harga_bahan' => $validated['harga_bahan'],
        ]);

        return redirect()->route('bahan-baku.index')
            ->with('message', "Bahan Baku '{$validated['nama_bahan']}' telah berhasil dibuat dengan ID: {$bahanBaku->bahan_baku_id}.")
            ->with('type', 'success');
    }

    /**
     * Show the form for editing the specified bahan baku.
     */
    public function edit(BahanBaku $bahanBaku)
    {
        // Authorization: Admin (R01), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit bahan baku.');
        }

        return Inertia::render('bahan-baku/edit', [
            'bahanBaku' => $bahanBaku
        ]);
    }

    /**
     * Update the specified bahan baku in storage.
     */
    public function update(Request $request, BahanBaku $bahanBaku)
    {
        // Authorization: Admin (R01), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah bahan baku.');
        }

        $validated = $request->validate([
            'nama_bahan' => ['required', 'string', 'max:255'],
            'lokasi_bahan' => ['required', 'string', 'max:255'],
            'stok_bahan' => ['required', 'numeric', 'min:0'],
            'satuan_bahan' => ['required', 'string', 'max:50'],
            'harga_bahan' => ['required', 'numeric', 'min:0'],
        ]);

        // Update with validated data
        $bahanBaku->update($validated);

        return redirect()->route('bahan-baku.index')
            ->with('message', "Bahan Baku '{$validated['nama_bahan']}' telah berhasil diperbarui.")
            ->with('type', 'success');
    }

    /**
     * Remove the specified bahan baku from storage.
     */
    public function destroy(BahanBaku $bahanBaku)
    {
        // Authorization: Admin (R01), Manajer Gudang (R07)
        if (!$this->isAdmin() && !$this->isGudangRelated()) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus bahan baku.');
        }

        try {
            $namaBahan = $bahanBaku->nama_bahan;
            $bahanBakuId = $bahanBaku->bahan_baku_id;

            $bahanBaku->delete();

            return redirect()->route('bahan-baku.index')
                ->with('message', "Bahan Baku '{$namaBahan}' dengan ID: {$bahanBakuId} telah dihapus.")
                ->with('type', 'success');
        } catch (\Exception $e) {
            return redirect()->route('bahan-baku.index')
                ->with('message', 'Gagal menghapus bahan baku. Silakan coba lagi.')
                ->with('type', 'error');
        }
    }
}
