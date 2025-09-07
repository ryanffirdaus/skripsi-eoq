<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get query parameters with default values
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'supplier_id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $perPage = $request->input('per_page', 10);

        // Build the query
        $query = Supplier::query();

        // Apply search filter if a search term is present
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_supplier', 'like', '%' . $search . '%')
                    ->orWhere('kontak_person', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('telepon', 'like', '%' . $search . '%')
                    ->orWhere('kota', 'like', '%' . $search . '%');
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Paginate the results
        $suppliers = $query->paginate($perPage)->withQueryString();

        return Inertia::render('supplier/index', [
            'supplier' => $suppliers,
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
        return Inertia::render('supplier/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_supplier' => ['required', 'string', 'max:255'],
            'kontak_person' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:supplier,email'],
            'telepon' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kode_pos' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'catatan' => ['nullable', 'string'],
        ]);

        $supplier = Supplier::create($validated);

        return redirect()->route('supplier.index')
            ->with('message', "Supplier '{$validated['nama_supplier']}' has been successfully created with ID: {$supplier->supplier_id}.")
            ->with('type', 'success');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        // Eager load relationships for created_by and updated_by, selecting only necessary fields
        $supplier->load([
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('supplier/show', [
            'supplier' => $supplier
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return Inertia::render('supplier/edit', [
            'supplier' => $supplier
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'nama_supplier' => ['required', 'string', 'max:255'],
            'kontak_person' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:supplier,email,' . $supplier->supplier_id . ',supplier_id'],
            'telepon' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kode_pos' => ['required', 'string', 'max:10'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'catatan' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return redirect()->route('supplier.index')
            ->with('message', "Supplier '{$validated['nama_supplier']}' has been successfully updated.")
            ->with('type', 'success');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        try {
            $namaSupplier = $supplier->nama_supplier;
            $supplierId = $supplier->supplier_id;

            // This will trigger the 'deleting' event in the model for soft delete
            $supplier->delete();

            return redirect()->route('supplier.index')
                ->with('message', "Supplier '{$namaSupplier}' (ID: {$supplierId}) has been successfully deleted.")
                ->with('type', 'success');
        } catch (\Exception $e) {
            // Generic error message for security
            return redirect()->route('supplier.index')
                ->with('message', 'Failed to delete the supplier. It might be associated with other data.')
                ->with('type', 'error');
        }
    }
}
