<x-auth-split-layout>
    {{-- Mobile brand mark --}}
    <div class="lg:hidden flex items-center gap-2.5 mb-6">
        <span class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-teal-500/10 border border-teal-500/20 text-teal-600 dark:text-teal-400">
            <i data-lucide="boxes" class="w-5 h-5"></i>
        </span>
        <span class="text-base font-bold text-gray-900 dark:text-white">Nezerwa Plus</span>
    </div>

    <div class="space-y-1.5 mb-8">
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            Welcome back
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Please enter your credentials to access your account.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                Email address
            </label>
            <div class="relative rounded-xl shadow-xs">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
                <input id="email" name="email" type="email" autocomplete="email" required autofocus
                       value="{{ old('email') }}"
                       class="block w-full pl-10 pr-3.5 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 dark:focus:border-teal-400 transition"
                       placeholder="you@company.com">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700 dark:text-teal-400 hover:underline">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="relative rounded-xl shadow-xs">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="block w-full pl-10 pr-3.5 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 dark:focus:border-teal-400 transition"
                       placeholder="••••••••">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" name="remember" type="checkbox"
                   class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-800">
            <label for="remember_me" class="ml-2 block text-xs font-medium text-gray-700 dark:text-gray-300 select-none">
                Remember me for 30 days
            </label>
        </div>

        <div>
            <button type="submit"
                    class="flex w-full justify-center items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 active:bg-teal-800 py-2.5 px-4 text-sm font-semibold text-white shadow-sm transition-all">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span>Sign in</span>
            </button>
        </div>
    </form>

    <div class="mt-8 text-center">
        <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>
            Protected by Nezerwa Plus Security
        </span>
    </div>
</x-auth-split-layout>
