<?php

namespace App\Http\Controllers;

use App\Models\Pemasok;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PemasokController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get query parameters with default values
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'pemasok_id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $perPage = $request->input('per_page', 10);

        // Build the query - include trashed
        $query = Pemasok::withTrashed();

        // Apply search filter if a search term is present
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pemasok', 'like', '%' . $search . '%')
                    ->orWhere('narahubung', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('nomor_telepon', 'like', '%' . $search . '%')
                    ->orWhere('alamat', 'like', '%' . $search . '%');
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $pemasok = $query->paginate($perPage)->withQueryString();

        // Transform data to add status based on deleted_at
        $pemasok->getCollection()->transform(function ($item) {
            $item->status = $item->deleted_at ? 'inactive' : 'active';
            return $item;
        });

        return Inertia::render('pemasok/index', [
            'pemasok' => $pemasok,
            'filters' => [
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => (int) $perPage,
            ],
            'flash' => [
                'message' => session('message'),
                'type' => session('type', 'success'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('pemasok/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pemasok' => ['required', 'string', 'max:255'],
            'narahubung' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:pemasok,email'],
            'nomor_telepon' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'catatan' => ['nullable', 'string'],
        ]);

        $pemasok = Pemasok::create($validated);

        return redirect()->route('pemasok.index')
            ->with('message', "Pemasok '{$validated['nama_pemasok']}' has been successfully created with ID: {$pemasok->pemasok_id}.")
            ->with('type', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pemasok $pemasok)
    {
        // Eager load relationships for created_by and updated_by, selecting only necessary fields
        $pemasok->load([
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('pemasok/show', [
            'pemasok' => $pemasok
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pemasok $pemasok)
    {
        return Inertia::render('pemasok/edit', [
            'pemasok' => $pemasok
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pemasok $pemasok)
    {
        $validated = $request->validate([
            'nama_pemasok' => ['required', 'string', 'max:255'],
            'narahubung' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:pemasok,email,' . $pemasok->pemasok_id . ',pemasok_id'],
            'nomor_telepon' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'catatan' => ['nullable', 'string'],
        ]);

        $pemasok->update($validated);

        return redirect()->route('pemasok.index')
            ->with('message', "Pemasok '{$validated['nama_pemasok']}' has been successfully updated.")
            ->with('type', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pemasok $pemasok)
    {
        try {
            $namaPemasok = $pemasok->nama_pemasok;
            $pemasokId = $pemasok->pemasok_id;

            // This will trigger the 'deleting' event in the model for soft delete
            $pemasok->delete();

            return redirect()->route('pemasok.index')
                ->with('message', "Pemasok '{$namaPemasok}' (ID: {$pemasokId}) has been successfully deleted.")
                ->with('type', 'success');
        } catch (\Exception $e) {
            // Generic error message for security
            return redirect()->route('pemasok.index')
                ->with('message', 'Failed to delete the pemasok. It might be associated with other data.')
                ->with('type', 'error');
        }
    }

    /**
     * Restore a soft deleted pemasok
     */
    public function restore($pemasok_id)
    {
        $pemasok = Pemasok::withTrashed()->where('pemasok_id', $pemasok_id)->firstOrFail();

        if ($pemasok->trashed()) {
            $pemasok->restore();

            return redirect()->route('pemasok.index')
                ->with('message', "Pemasok '{$pemasok->nama_pemasok}' has been successfully restored.")
                ->with('type', 'success');
        }

        return redirect()->route('pemasok.index')
            ->with('message', 'Pemasok is not deleted.')
            ->with('type', 'warning');
    }
}
