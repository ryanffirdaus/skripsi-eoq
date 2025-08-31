<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PelangganController extends Controller
{
    /**
     * Display a listing of the pelanggan.
     */
    public function index(Request $request)
    {
        // Get query parameters with default values
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'pelanggan_id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $perPage = $request->input('per_page', 10);

        // Build the query
        $query = Pelanggan::query();

        // Apply search filter if a search term is present
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', '%' . $search . '%')
                    ->orWhere('email_pelanggan', 'like', '%' . $search . '%')
                    ->orWhere('nomor_telepon', 'like', '%' . $search . '%');
            });
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Paginate the results
        $pelanggans = $query->paginate($perPage)->withQueryString();

        return Inertia::render('pelanggan/index', [
            'pelanggan' => $pelanggans,
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
     * Show the form for creating a new pelanggan.
     */
    public function create()
    {
        return Inertia::render('pelanggan/create');
    }

    /**
     * Store a newly created pelanggan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:255'],
            'email_pelanggan' => ['required', 'email', 'max:255', 'unique:pelanggans,email_pelanggan'],
            'nomor_telepon' => ['required', 'string', 'max:20'],
            'alamat_pembayaran' => ['required', 'string'],
            'alamat_pengiriman' => ['nullable', 'string'],
        ]);

        // If alamat_pengiriman is empty, use alamat_pembayaran
        if (empty($validated['alamat_pengiriman'])) {
            $validated['alamat_pengiriman'] = $validated['alamat_pembayaran'];
        }

        $pelanggan = Pelanggan::create($validated);

        return redirect()->route('pelanggan.index')
            ->with('message', "Pelanggan '{$validated['nama_pelanggan']}' has been successfully created with ID: {$pelanggan->pelanggan_id}.")
            ->with('type', 'success');
    }

    /**
     * Show the form for editing the specified pelanggan.
     */
    public function edit(Pelanggan $pelanggan)
    {
        return Inertia::render('pelanggan/edit', [
            'pelanggan' => $pelanggan
        ]);
    }

    /**
     * Update the specified pelanggan in storage.
     */
    public function update(Request $request, Pelanggan $pelanggan)
    {
        $validated = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:255'],
            'email_pelanggan' => ['required', 'email', 'max:255', 'unique:pelanggans,email_pelanggan,' . $pelanggan->pelanggan_id . ',pelanggan_id'],
            'nomor_telepon' => ['required', 'string', 'max:20'],
            'alamat_pembayaran' => ['required', 'string'],
            'alamat_pengiriman' => ['nullable', 'string'],
        ]);

        // If alamat_pengiriman is empty, use alamat_pembayaran
        if (empty($validated['alamat_pengiriman'])) {
            $validated['alamat_pengiriman'] = $validated['alamat_pembayaran'];
        }

        $pelanggan->update($validated);

        return redirect()->route('pelanggan.index')
            ->with('message', "Pelanggan '{$validated['nama_pelanggan']}' has been successfully updated.")
            ->with('type', 'success');
    }

    /**
     * Remove the specified pelanggan from storage.
     */
    public function destroy(Pelanggan $pelanggan)
    {
        try {
            $namaPelanggan = $pelanggan->nama_pelanggan;
            $pelangganId = $pelanggan->pelanggan_id;

            $pelanggan->delete();

            return redirect()->route('pelanggan.index')
                ->with('message', "Pelanggan '{$namaPelanggan}' (ID: {$pelangganId}) has been successfully deleted.")
                ->with('type', 'success');
        } catch (\Exception $e) {
            return redirect()->route('pelanggan.index')
                ->with('message', 'Failed to delete pelanggan. Please try again.')
                ->with('type', 'error');
        }
    }
}
