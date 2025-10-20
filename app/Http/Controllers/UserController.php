<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles')->orderBy('id', 'desc');

        // Search by name or email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // Filter by role
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        $users = $query->paginate(10);
        $roles = Role::pluck('name')->toArray();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::pluck('name')->toArray();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|string|exists:roles,name',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name', 'email');
        $data['password'] = Hash::make($request->password);

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $user = User::create($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', '✅ User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = Role::pluck('name')->toArray();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'role'     => 'required|string|exists:roles,name',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name', 'email');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Handle new profile image
        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', '✅ User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', '🗑️ User deleted successfully.');
    }
}
