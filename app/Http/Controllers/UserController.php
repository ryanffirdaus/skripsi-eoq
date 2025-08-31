<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
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
                ->orWhere('email', 'like', '%' . $search . '%');
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
        return Inertia::render('users/create', [
            'roles' => Role::all()
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
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
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,role_id'],
        ]);

        // Generate a unique user_id with pattern US001, US002, etc.
        $latestUser = User::latest('user_id')->first();
        $nextId = $latestUser ? intval(substr($latestUser->user_id, 2)) + 1 : 1;
        $user_id = 'US' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        User::create([
            'user_id' => $user_id,
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        return redirect()->route('users.index')
            ->with('message', "User '{$validated['nama_lengkap']}' has been successfully created with ID: {$user_id}.")
            ->with('type', 'success');
    }

    public function edit(User $user)
    {
        return Inertia::render('users/edit', [
            'user' => $user,
            'roles' => Role::all()
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->user_id, 'user_id')],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,role_id'],
        ]);

        $userData = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        // Only update password if it's provided
        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);

        return redirect()->route('users.index')
            ->with('message', "User '{$validated['nama_lengkap']}' has been successfully updated.")
            ->with('type', 'success');
    }

    public function destroy(User $user)
    {
        try {
            $userName = $user->nama_lengkap;
            $userId = $user->user_id;

            $user->delete();

            return redirect()->route('users.index')
                ->with('message', "User '{$userName}' (ID: {$userId}) has been successfully deleted.")
                ->with('type', 'success');
        } catch (\Exception $e) {
            return redirect()->route('users.index')
                ->with('message', 'Failed to delete user. Please try again.')
                ->with('type', 'error');
        }
    }
}
