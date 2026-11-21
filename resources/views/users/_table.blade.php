<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
        <thead>
            <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-bold">
                <th scope="col" class="py-3 px-4 text-left">#ID</th>
                <th scope="col" class="py-3 px-4 text-left">User / Profile</th>
                <th scope="col" class="py-3 px-4 text-left">Email Address</th>
                <th scope="col" class="py-3 px-4 text-left">System Role</th>
                <th scope="col" class="py-3 px-4 text-left">Joined Date</th>
                <th scope="col" class="py-3 px-4 text-left">Status</th>
                <th scope="col" class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
            @forelse($users as $user)
                @php
                    $isSuperAdmin = ($user->email === 'admin@stockmanagement.com');
                    $roles = $user->getRoleNames();
                    $primaryRole = $roles->first() ?? 'user';

                    $roleBadge = match($primaryRole) {
                        'admin'      => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200/80 dark:border-rose-800',
                        'manager'    => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200/80 dark:border-indigo-800',
                        'cashier'    => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200/80 dark:border-emerald-800',
                        'accountant' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200/80 dark:border-amber-800',
                        default      => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                    };

                    $avatarBg = match($primaryRole) {
                        'admin'      => 'from-rose-500 to-red-600',
                        'manager'    => 'from-indigo-500 to-blue-600',
                        'cashier'    => 'from-emerald-500 to-teal-600',
                        'accountant' => 'from-amber-500 to-orange-600',
                        default      => 'from-slate-500 to-slate-700',
                    };

                    $photoUrl = null;
                    if ($user->photo && file_exists(public_path('storage/' . $user->photo))) {
                        $photoUrl = asset('storage/' . $user->photo);
                    } elseif ($user->profile_image && file_exists(public_path('storage/' . $user->profile_image))) {
                        $photoUrl = asset('storage/' . $user->profile_image);
                    }
                @endphp
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    {{-- ID --}}
                    <td class="py-3.5 px-4 font-mono font-medium text-slate-400 whitespace-nowrap">
                        #{{ $user->id }}
                    </td>

                    {{-- User Profile & Name --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}"
                                     alt="{{ $user->name }}"
                                     class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-xs">
                            @else
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $avatarBg }} flex items-center justify-center text-white text-xs font-bold shadow-xs">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                            @endif
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <span>{{ $user->name }}</span>
                                    @if($isSuperAdmin)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-800" title="Protected System Account">
                                            Super Admin
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">
                                    UID: {{ $user->id }}
                                </span>
                            </div>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-medium">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $user->email }}</span>
                        </div>
                    </td>

                    {{-- Role --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <div class="flex flex-wrap items-center gap-1">
                            @forelse($roles as $role)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $roleBadge }}">
                                    @if($role === 'admin')
                                        <svg class="w-3 h-3 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    @elseif($role === 'manager')
                                        <svg class="w-3 h-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    @elseif($role === 'cashier')
                                        <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    @elseif($role === 'accountant')
                                        <svg class="w-3 h-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    @endif
                                    <span>{{ ucfirst($role) }}</span>
                                </span>
                            @empty
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    No Role
                                </span>
                            @endforelse
                        </div>
                    </td>

                    {{-- Joined Date --}}
                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                        <span>{{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}</span>
                        <span class="block text-[10px] text-slate-400 dark:text-slate-500">{{ $user->created_at ? $user->created_at->diffForHumans() : '' }}</span>
                    </td>

                    {{-- Status --}}
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Active</span>
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="{{ route('users.edit', $user) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition"
                               title="Edit User">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit</span>
                            </a>

                            @if(!$isSuperAdmin)
                                <button type="button"
                                        @click="openDeleteModal({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/60 transition"
                                        title="Delete User">
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Delete</span>
                                </button>
                            @else
                                <span class="px-2.5 py-1.5 text-xs text-slate-400 dark:text-slate-600 cursor-not-allowed" title="Protected System Account">
                                    Locked
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="p-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">No users found</span>
                            <span class="text-xs text-slate-400 max-w-sm">No user matches your search or role filters. Try resetting the filters or creating a new user account.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
