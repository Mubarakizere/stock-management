<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->paginate(10);
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request) {
            $role = Role::create([
                'name'       => strtolower($request->name),
                'guard_name' => 'web',
            ]);

            if ($request->filled('permissions')) {
                $role->syncPermissions($request->permissions);
            }
        });

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => strtolower($request->name)]);
            $role->syncPermissions($request->permissions ?? []);
        });

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->back()->with('error', 'The Admin role cannot be deleted.');
        }

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
