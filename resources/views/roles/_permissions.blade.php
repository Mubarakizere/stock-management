@php
    use Illuminate\Support\Str;

    $totalPermCount = 0;
    foreach ($permissions as $gp) {
        $totalPermCount += $gp->count();
    }
@endphp

<div x-data="{
        expanded: true,
        filterText: '',
        selectedCount: {{ count($rolePermissions ?? []) }},
        updateCount() {
            this.selectedCount = $el.querySelectorAll('input[type=checkbox][name=\'permissions[]\']:checked').length;
        },
        toggleAll(state) {
            $el.querySelectorAll('input[type=checkbox][name=\'permissions[]\']').forEach(cb => {
                cb.checked = state;
            });
            this.updateCount();
        },
        toggleGroup(groupSlug, state) {
            $el.querySelectorAll(`input[data-group='${groupSlug}']`).forEach(cb => {
                cb.checked = state;
            });
            this.updateCount();
        }
     }"
     class="space-y-6">

    {{-- Permissions Matrix Header & Global Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2">
                <div class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Security Permissions Matrix</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 font-mono">
                    <span x-text="selectedCount"></span> / {{ $totalPermCount }} Selected
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Configure module-level access grants and capabilities assigned to this role.
            </p>
        </div>

        {{-- Fast Global Buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="toggleAll(true)"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 transition cursor-pointer">
                Select All ({{ $totalPermCount }})
            </button>
            <button type="button" @click="toggleAll(false)"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition cursor-pointer">
                Clear All
            </button>
            <button type="button" @click="expanded = !expanded"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <span x-text="expanded ? 'Collapse All' : 'Expand All'"></span>
            </button>
        </div>
    </div>

    {{-- Filter Matrix Search --}}
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        <input type="text" x-model="filterText" placeholder="Filter permissions by keyword (e.g. create, delete, export, sales, loans)..."
               class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
    </div>

    {{-- Grouped Permissions Grid --}}
    <div class="space-y-4" x-show="expanded">
        @forelse ($permissions as $group => $groupPermissions)
            @php
                $groupSlug = Str::slug($group);
                $groupCount = $groupPermissions->count();

                $groupTheme = match(strtolower($group)) {
                    'users', 'roles'            => 'border-rose-200 dark:border-rose-900/50 bg-rose-50/20 dark:bg-rose-950/10 text-rose-700 dark:text-rose-400',
                    'sales', 'purchases'        => 'border-blue-200 dark:border-blue-900/50 bg-blue-50/20 dark:bg-blue-950/10 text-blue-700 dark:text-blue-400',
                    'expenses', 'transactions'  => 'border-amber-200 dark:border-amber-900/50 bg-amber-50/20 dark:bg-amber-950/10 text-amber-700 dark:text-amber-400',
                    'manufacturing', 'recipes'  => 'border-purple-200 dark:border-purple-900/50 bg-purple-50/20 dark:bg-purple-950/10 text-purple-700 dark:text-purple-400',
                    'reports', 'dashboard'      => 'border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/20 dark:bg-emerald-950/10 text-emerald-700 dark:text-emerald-400',
                    default                     => 'border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/30 text-slate-700 dark:text-slate-300',
                };
            @endphp

            <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-850 shadow-xs space-y-3.5"
                 x-show="!filterText || '{{ strtolower($group) }}'.includes(filterText.toLowerCase()) || $el.querySelectorAll('.perm-item:not([style*=\'display: none\'])').length > 0">

                {{-- Module Group Header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold uppercase tracking-wider {{ $groupTheme }}">
                            {{ $group }}
                        </span>
                        <span class="text-xs text-slate-400 font-mono">
                            {{ $groupCount }} permission{{ $groupCount !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-xs">
                        <button type="button" @click="toggleGroup('{{ $groupSlug }}', true)"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold cursor-pointer">
                            Select Group
                        </button>
                        <span class="text-slate-300 dark:text-slate-600">•</span>
                        <button type="button" @click="toggleGroup('{{ $groupSlug }}', false)"
                                class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:underline cursor-pointer">
                            Clear
                        </button>
                    </div>
                </div>

                {{-- Checkbox Tiles --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                    @foreach ($groupPermissions as $perm)
                        @php
                            $actionName = Str::after($perm->name, '.');
                            if ($actionName === '') {
                                $actionName = $perm->name;
                            }
                            $friendlyAction = ucfirst(str_replace(['.', '_'], ' ', $actionName));
                            $isChecked = isset($rolePermissions) && in_array($perm->name, $rolePermissions);
                        @endphp

                        <label class="perm-item flex items-start gap-2.5 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 hover:bg-slate-100/70 dark:hover:bg-slate-800/60 transition cursor-pointer select-none"
                               x-show="!filterText || '{{ strtolower($perm->name) }}'.includes(filterText.toLowerCase()) || '{{ strtolower($friendlyAction) }}'.includes(filterText.toLowerCase())">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $perm->name }}"
                                   data-group="{{ $groupSlug }}"
                                   @checked($isChecked)
                                   @change="updateCount()"
                                   class="mt-0.5 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800 dark:checked:bg-indigo-600 transition">
                            <div class="min-w-0 grow">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-tight">
                                    {{ $friendlyAction }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono truncate" title="{{ $perm->name }}">
                                    {{ $perm->name }}
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-400 text-xs italic">
                No permissions registered in database.
            </div>
        @endforelse
    </div>
</div>
