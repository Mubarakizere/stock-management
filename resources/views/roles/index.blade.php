@extends('layouts.app')
@section('title', 'Roles Management')

@section('content')
<div x-data="{ deleteRoleId: null }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <i data-lucide="shield" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
            <span>Roles Management</span>
        </h1>

        <a href="{{ route('roles.create') }}" class="btn btn-primary flex items-center gap-2 text-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New Role
        </a>
    </div>

    {{-- 🔹 Flash Messages --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- 🔹 Roles Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300 text-xs font-medium uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Role Name</th>
                        <th class="px-4 py-3 text-left">Users</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition">
                            <td class="px-4 py-3 text-gray-500">{{ $loop->iteration }}</td>

                            {{-- Role Name --}}
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ match($role->name) {
                                        'admin' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                        'manager' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                        'cashier' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                        'accountant' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300'
                                    } }}">
                                    {{ ucfirst($role->name) }}
                                </span>
                            </td>

                            {{-- Users Count --}}
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $role->users_count ?? 0 }}
                            </td>

                            {{-- Created At --}}
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $role->created_at?->format('Y-m-d') ?? '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right flex justify-end gap-2 flex-wrap">
                                <a href="{{ route('roles.edit', $role) }}"
                                   class="btn btn-outline text-xs flex items-center gap-1">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit
                                </a>

                                @if ($role->name !== 'admin')
                                <button @click="deleteRoleId = {{ $role->id }}" type="button"
                                        class="btn btn-danger text-xs flex items-center gap-1">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No roles found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🔹 Pagination --}}
    <div class="mt-4">
        {{ $roles->links() }}
    </div>

    {{-- 🔸 Delete Confirmation Modal --}}
    <div x-show="deleteRoleId" x-cloak
         class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 max-w-sm w-full">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Confirm Deletion
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Are you sure you want to delete this role? Users with this role will lose their access permissions.
            </p>
            <div class="flex justify-end gap-3">
                <button @click="deleteRoleId = null" class="btn btn-outline text-sm">Cancel</button>
                <form :action="`/roles/${deleteRoleId}`" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger text-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
@endsection
