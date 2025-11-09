<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Http\Traits\RoleAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use RoleAccess;

    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        // Authorization: hanya Admin (R01) yang bisa akses
        if (!$this->isAdmin()) {
            return redirect()->route('dashboard')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk mengakses daftar pengguna.',
                    'type' => 'error'
                ]);
        }

        // Get query parameters with default values
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'user_id');
        $sortDirection = $request->input('sort_direction', 'asc');
        $perPage = $request->input('per_page', 10);
        $roleFilter = $request->input('role_id');

        // Build the query
        $query = User::with('role');

        // Apply search filter if a search term is present
        if ($search) {
            $query->where('nama_lengkap', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhereHas('role', function ($q) use ($search) {
                    $q->where('nama', 'like', '%' . $search . '%');
                });
        }

        if ($roleFilter && $roleFilter !== 'all') {
            $query->where('role_id', $roleFilter);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        // Paginate the results
        $users = $query->paginate($perPage)->withQueryString();

        return Inertia::render('users/index', [
            'users' => $users,
            'roles' => Role::all(),
            'filters' => [
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => (int) $perPage,
                'role_id' => $roleFilter,
            ],
            'flash' => [
                'message' => session('message'),
                'type' => session('type', 'success'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // Authorization: hanya Admin (R01) yang bisa create
        if (!$this->isAdmin()) {
            return redirect()->route('dashboard')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk membuat pengguna baru.',
                    'type' => 'error'
                ]);
        }

        return Inertia::render('users/create', [
            'roles' => Role::all()
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Authorization: hanya Admin (R01) yang bisa lihat detail
        if (!$this->isAdmin()) {
            return redirect()->route('dashboard')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk melihat detail pengguna.',
                    'type' => 'error'
                ]);
        }

        $user->load([
            'role',
            'createdBy:user_id,nama_lengkap',
            'updatedBy:user_id,nama_lengkap'
        ]);

        return Inertia::render('users/show', [
            'user' => $user
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        // Authorization: hanya Admin (R01) yang bisa store
        if (!$this->isAdmin()) {
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk membuat pengguna baru.',
                    'type' => 'error'
                ]);
        }

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'string', 'min:8', 'same:password'],
            'role_id' => ['required', 'exists:roles,role_id'],
        ]);

        $user = User::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        return redirect()->route('users.index')
            ->with('message', "Pengguna '{$validated['nama_lengkap']}' telah berhasil dibuat dengan ID: {$user->user_id}.")
            ->with('type', 'success');
    }

    public function edit(User $user)
    {
        // Authorization: hanya Admin (R01) yang bisa edit
        if (!$this->isAdmin()) {
            return redirect()->route('dashboard')
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk mengedit pengguna.',
                    'type' => 'error'
                ]);
        }

        return Inertia::render('users/edit', [
            'user' => $user,
            'roles' => Role::all()
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Authorization: hanya Admin (R01) yang bisa update
        if (!$this->isAdmin()) {
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk mengubah data pengguna.',
                    'type' => 'error'
                ]);
        }

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'role_id' => ['required', 'exists:roles,role_id'],
        ]);

        $userData = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        $user->update($userData);

        return redirect()->route('users.index')
            ->with('message', "Pengguna '{$validated['nama_lengkap']}' telah berhasil diperbarui.")
            ->with('type', 'success');
    }

    public function destroy(User $user)
    {
        // Authorization: hanya Admin (R01) yang bisa delete
        if (!$this->isAdmin()) {
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Anda tidak memiliki izin untuk menghapus pengguna.',
                    'type' => 'error'
                ]);
        }

        try {
            $userName = $user->nama_lengkap;
            $userId = $user->user_id;

            $user->delete();

            return redirect()->route('users.index')
                ->with('message', "Pengguna '{$userName}' dengan ID: {$userId} telah dihapus.")
                ->with('type', 'success');
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->with('message', 'Gagal menghapus pengguna. Silakan coba lagi.')
                ->with('type', 'error');
        }
    }
}
