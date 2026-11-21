<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
        <thead>
            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-bold">
                <th scope="col" class="py-3 px-4 text-left">#</th>
                <th scope="col" class="py-3 px-4 text-left">Role Name &amp; Level</th>
                <th scope="col" class="py-3 px-4 text-left">Assigned Users</th>
                <th scope="col" class="py-3 px-4 text-left">Granted Permissions</th>
                <th scope="col" class="py-3 px-4 text-left">Created At</th>
                <th scope="col" class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
            @forelse ($roles as $role)
                @php
                    $isSystemRole = in_array($role->name, ['admin', 'manager']);
                    $roleBadge = match($role->name) {
                        'admin'      => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200/80 dark:border-rose-800',
                        'manager'    => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200/80 dark:border-indigo-800',
                        'cashier'    => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200/80 dark:border-emerald-800',
                        'accountant' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200/80 dark:border-amber-800',
                        default      => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                    };

                    $roleIconBg = match($role->name) {
                        'admin'      => 'from-rose-500 to-red-600',
                        'manager'    => 'from-indigo-500 to-blue-600',
                        'cashier'    => 'from-emerald-500 to-teal-600',
                        'accountant' => 'from-amber-500 to-orange-600',
                        default      => 'from-slate-500 to-slate-700',
                    };
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    {{-- Row Index --}}
                    <td class="py-3.5 px-4 font-mono font-medium text-slate-400 whitespace-nowrap">
                        {{ $loop->iteration + ($roles->currentPage() - 1) * $roles->perPage() }}
                    </td>

                    {{-- Role Name & System Tag --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br {{ $roleIconBg }} flex items-center justify-center text-white text-xs font-bold shadow-xs shrink-0">
                                @if($role->name === 'admin')
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                @elseif($role->name === 'manager')
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                @endif
                            </div>

                            <div>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="
                                                selectedRole = {
                                                    id: {{ $role->id }},
                                                    name: '{{ ucfirst($role->name) }}',
                                                    raw_name: '{{ $role->name }}',
                                                    is_system: {{ $isSystemRole ? 'true' : 'false' }},
                                                    created_at: '{{ $role->created_at?->format('M d, Y') ?? '—' }}',
                                                    users_count: {{ $role->users_count ?? 0 }},
                                                    users: @js($role->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])),
                                                    permissions_count: {{ $role->permissions_count ?? $role->permissions->count() }},
                                                    permissions: @js($role->permissions->pluck('name')),
                                                    edit_url: '{{ route('roles.edit', $role) }}'
                                                };
                                                showDrawer = true;
                                            "
                                            class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 text-left transition flex items-center gap-1.5 cursor-pointer">
                                        <span>{{ ucfirst($role->name) }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    @if($isSystemRole)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700 uppercase tracking-wider" title="Core System Built-in Role">
                                            System
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">
                                    guard: {{ $role->guard_name ?? 'web' }}
                                </span>
                            </div>
                        </div>
                    </td>

                    {{-- Assigned Users --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ $role->users_count ?? 0 }} user{{ ($role->users_count ?? 0) !== 1 ? 's' : '' }}</span>
                            </span>

                            @if(($role->users_count ?? 0) > 0)
                                <div class="flex -space-x-1.5 overflow-hidden">
                                    @foreach($role->users->take(3) as $u)
                                        <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-slate-900 bg-indigo-600 text-white text-[9px] font-bold flex items-center justify-center"
                                             title="{{ $u->name }} ({{ $u->email }})">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                    @endforeach
                                    @if(($role->users_count ?? 0) > 3)
                                        <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-slate-900 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[9px] font-bold flex items-center justify-center">
                                            +{{ ($role->users_count ?? 0) - 3 }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </td>

                    {{-- Granted Permissions --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            @php
                                $permCount = $role->permissions_count ?? $role->permissions->count();
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $permCount > 0 ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                <span>{{ $permCount }} Granted</span>
                            </span>
                        </div>
                    </td>

                    {{-- Created At --}}
                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                        <span>{{ $role->created_at ? $role->created_at->format('M d, Y') : '—' }}</span>
                        <span class="block text-[10px] text-slate-400 dark:text-slate-500">{{ $role->created_at ? $role->created_at->diffForHumans() : '' }}</span>
                    </td>

                    {{-- Actions --}}
                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            {{-- Inspect Access Drawer Button --}}
                            <button type="button"
                                    @click="
                                        selectedRole = {
                                            id: {{ $role->id }},
                                            name: '{{ ucfirst($role->name) }}',
                                            raw_name: '{{ $role->name }}',
                                            is_system: {{ $isSystemRole ? 'true' : 'false' }},
                                            created_at: '{{ $role->created_at?->format('M d, Y') ?? '—' }}',
                                            users_count: {{ $role->users_count ?? 0 }},
                                            users: @js($role->users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])),
                                            permissions_count: {{ $role->permissions_count ?? $role->permissions->count() }},
                                            permissions: @js($role->permissions->pluck('name')),
                                            edit_url: '{{ route('roles.edit', $role) }}'
                                        };
                                        showDrawer = true;
                                    "
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 transition cursor-pointer"
                                    title="Inspect Permissions &amp; Users">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Inspect</span>
                            </button>

                            {{-- Edit Role --}}
                            <a href="{{ route('roles.edit', $role) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition"
                               title="Edit Role &amp; Permissions">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit</span>
                            </a>

                            {{-- Delete Role (Disabled for system roles) --}}
                            @if(!$isSystemRole)
                                <button type="button"
                                        @click="
                                            deleteRoleId = {{ $role->id }};
                                            deleteRoleName = '{{ ucfirst($role->name) }}';
                                        "
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/60 transition cursor-pointer"
                                        title="Delete Role">
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Delete</span>
                                </button>
                            @else
                                <span class="px-2.5 py-1.5 text-xs text-slate-400 dark:text-slate-600 cursor-not-allowed" title="System Role is Protected">
                                    Locked
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">No roles found</span>
                            <span class="text-xs text-slate-400 max-w-sm">No role matches your search query. Try clearing the search or creating a new role.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
