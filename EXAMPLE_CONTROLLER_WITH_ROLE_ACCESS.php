<?php

/**
 * FILE CONTOH IMPLEMENTASI
 *
 * Ini adalah contoh bagaimana mengintegrasikan middleware dan trait RoleAccess
 * di dalam sebuah controller. Gunakan sebagai referensi saat membuat/modify controller.
 *
 * JANGAN COPY-PASTE langsung, tapi pahami pattern-nya dan adaptasi sesuai kebutuhan.
 */

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

/**
 * CONTOH CONTROLLER DENGAN ROLE-BASED ACCESS CONTROL
 *
 * Contoh di bawah adalah pseudo-code yang menunjukkan pattern best practice
 * untuk mengimplementasikan role-based access di controller.
 */
class ExampleBahanBakuControllerWithRoleAccess extends Controller
{
    /**
     * Display a listing of resources.
     *
     * Role yang bisa akses:
     * - R01 (Admin)
     * - R02 (Staf Gudang)
     * - R07 (Manajer Gudang)
     *
     * Middleware sudah check, tapi kita bisa tambah logic spesifik di sini.
     */
    public function index(Request $request)
    {
        // Optional: Tambah logic spesifik per role
        if ($this->isStafGudang()) {
            // Jika Staf Gudang, hanya tampilkan bahan baku yang mereka tangani
            // Bisa tambahkan filter, limit data, dll
        }

        // Build query dan return
        $bahanBakus = BahanBaku::paginate(10);

        return Inertia::render('bahan-baku/index', [
            'bahanBaku' => $bahanBakus,
            // Kirim info permission ke frontend untuk conditional rendering
            'permissions' => [
                'create' => $this->isGudangRelated(),
                'edit' => $this->isGudangRelated(),
                'delete' => $this->isAdmin() || $this->isManajerGudang(),
            ],
            'userRole' => $this->getCurrentRoleName(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * Middleware akan check jika user punya action 'create' untuk route ini.
     */
    public function create()
    {
        // Middleware sudah proteksi di level route
        // Tapi bisa tambah double-check di sini kalau perlu

        if (!$this->isGudangRelated()) {
            $this->unauthorize(); // Throw 403
        }

        return Inertia::render('bahan-baku/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * Middleware check HTTP POST request.
     * Tambah logic untuk validate dan save.
     */
    public function store(Request $request)
    {
        // Optional: Tambah role check (redundant but safe)
        if (!$this->isGudangRelated()) {
            $this->unauthorize();
        }

        // Validate input
        $validated = $request->validate([
            'nama_bahan' => 'required|string|max:255',
            'lokasi_bahan' => 'required|string|max:255',
            'satuan_bahan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
        ]);

        // Create record
        $bahanBaku = BahanBaku::create($validated);

        // Log activity dengan role info
        Log::info('Bahan Baku created', [
            'created_by' => $this->getCurrentRole(),
            'created_by_name' => $this->getCurrentRoleName(),
            'bahan_baku_id' => $bahanBaku->bahan_baku_id,
        ]);

        return redirect()
            ->route('bahan-baku.index')
            ->with('message', 'Bahan Baku berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * Middleware check GET request dengan ID.
     */
    public function show(BahanBaku $bahanBaku)
    {
        $data = [
            'bahanBaku' => $bahanBaku,
            'userRole' => $this->getCurrentRoleName(),
        ];

        // Conditional rendering based on role
        if ($this->isGudangRelated()) {
            $data['canEdit'] = true;
            $data['canDelete'] = false; // Hanya Manajer bisa delete
        }

        if ($this->isManajerGudang()) {
            $data['canDelete'] = true;
            $data['showDeletionHistory'] = true;
        }

        if ($this->isAdmin()) {
            $data['canEdit'] = true;
            $data['canDelete'] = true;
            $data['showAllHistory'] = true;
        }

        return Inertia::render('bahan-baku/show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * Middleware check GET request dengan /edit suffix.
     */
    public function edit(BahanBaku $bahanBaku)
    {
        // Optional double check
        if (!$this->isGudangRelated()) {
            $this->unauthorize();
        }

        return Inertia::render('bahan-baku/edit', [
            'bahanBaku' => $bahanBaku,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * Middleware check PUT/PATCH request dengan ID.
     */
    public function update(Request $request, BahanBaku $bahanBaku)
    {
        // Double check (middleware sudah check, ini for extra safety)
        if (!$this->isGudangRelated()) {
            $this->unauthorize();
        }

        // Validate
        $validated = $request->validate([
            'nama_bahan' => 'required|string|max:255',
            'lokasi_bahan' => 'required|string|max:255',
            'satuan_bahan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
        ]);

        // Store old data untuk audit
        $oldData = $bahanBaku->only(['nama_bahan', 'harga_satuan']);

        // Update
        $bahanBaku->update($validated);

        // Log activity
        Log::info('Bahan Baku updated', [
            'updated_by' => $this->getCurrentRole(),
            'updated_by_name' => $this->getCurrentRoleName(),
            'bahan_baku_id' => $bahanBaku->bahan_baku_id,
            'old_data' => $oldData,
            'new_data' => $validated,
        ]);

        return redirect()
            ->route('bahan-baku.show', $bahanBaku)
            ->with('message', 'Bahan Baku berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     *
     * Middleware check DELETE request dengan ID.
     * Hanya Admin dan Manajer Gudang yang bisa delete (minimal)
     */
    public function destroy(BahanBaku $bahanBaku)
    {
        // Additional role check untuk delete operation
        // Bisa lebih restrictive dari middleware
        if (!$this->hasRoles(['R01', 'R07'])) {
            return response()->json([
                'message' => 'Hanya Admin dan Manajer Gudang yang bisa menghapus Bahan Baku',
            ], 403);
        }

        // Store info untuk audit sebelum delete
        $deletedData = [
            'bahan_baku_id' => $bahanBaku->bahan_baku_id,
            'nama_bahan' => $bahanBaku->nama_bahan,
            'deleted_by' => $this->getCurrentRole(),
            'deleted_by_name' => $this->getCurrentRoleName(),
        ];

        // Delete
        $bahanBaku->delete();

        // Log deletion
        Log::warning('Bahan Baku deleted', $deletedData);

        return redirect()
            ->route('bahan-baku.index')
            ->with('message', 'Bahan Baku berhasil dihapus');
    }

    /**
     * CONTOH METHOD TAMBAHAN: Export
     *
     * Hanya accessible oleh Admin, Staf Gudang, dan Manajer Gudang
     */
    public function export(Request $request)
    {
        // Check role
        if (!$this->isGudangRelated() && !$this->isAdmin()) {
            return response()->json([
                'message' => 'Hanya Admin dan Gudang yang bisa export',
            ], 403);
        }

        // Export logic
        $bahanBakus = BahanBaku::all();

        Log::info('Bahan Baku exported', [
            'exported_by' => $this->getCurrentRoleName(),
            'count' => $bahanBakus->count(),
        ]);

        // Return export file
        // ...
    }

    /**
     * CONTOH METHOD TAMBAHAN: Restore (Soft Delete)
     *
     * Hanya Admin dan Manajer yang bisa restore
     */
    public function restore($id)
    {
        // More restrictive permission untuk restore
        if (!$this->hasRoles(['R01', 'R07'])) {
            $this->unauthorize();
        }

        $bahanBaku = BahanBaku::withTrashed()->findOrFail($id);
        $bahanBaku->restore();

        Log::info('Bahan Baku restored', [
            'restored_by' => $this->getCurrentRoleName(),
            'bahan_baku_id' => $id,
        ]);

        return redirect()
            ->back()
            ->with('message', 'Bahan Baku berhasil di-restore');
    }
}

/**
 * POIN-POIN PENTING:
 *
 * 1. MIDDLEWARE SUDAH PROTECT ROUTE
 *    - Middleware CheckRoleBasedAccess sudah check di level request
 *    - Tapi tidak ada salahnya tambah double-check di controller untuk action penting
 *
 * 2. USE ROLE ACCESS TRAIT
 *    - Use $this->isGudangRelated() bukan $this->hasRoles(['R02', 'R07'])
 *    - Lebih readable dan maintainable
 *
 * 3. LOG IMPORTANT ACTIONS
 *    - Log siapa (role) yang melakukan action
 *    - Useful untuk audit trail dan troubleshooting
 *
 * 4. SEND PERMISSION TO FRONTEND
 *    - Pass $permissions ke view untuk conditional rendering
 *    - Jangan rely hanya pada frontend, backend harus tetap proteksi
 *
 * 5. VALIDATE ROLE-SPECIFIC LOGIC
 *    - Jika ada logic yang berbeda per role, pisahkan clearly
 *    - Use if statement atau conditional untuk clarity
 *
 * 6. USE SPECIFIC ROLES FOR CRITICAL OPERATIONS
 *    - Untuk delete/export/critical operations, gunakan hasRoles() bukan isGudangRelated()
 *    - Lebih explicit dan less prone to mistakes
 *
 * 7. CONSISTENT ERROR HANDLING
 *    - Use $this->unauthorize() untuk consistent 403 response
 *    - Atau return response()->json() untuk API endpoints
 */

// PATTERN SUMMARY:
//
// Pattern 1: Simple Check
// ---
// if (!$this->isGudangRelated()) {
//     $this->unauthorize();
// }
//
// Pattern 2: Multiple Roles Check
// ---
// if (!$this->hasRoles(['R01', 'R07'])) {
//     return response()->json(['message' => '...'], 403);
// }
//
// Pattern 3: Get Current Role Info
// ---
// $role = $this->getCurrentRole();
// $roleName = $this->getCurrentRoleName();
//
// Pattern 4: Conditional Logic
// ---
// if ($this->isStafGudang()) {
//     // Do something for Staf Gudang
// } elseif ($this->isManajerGudang()) {
//     // Do something for Manajer Gudang
// }
//
// Pattern 5: Send to Frontend
// ---
// return Inertia::render('view', [
//     'canEdit' => $this->isGudangRelated(),
//     'canDelete' => $this->isAdmin(),
// ]);
