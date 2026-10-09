<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white dark:bg-gray-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Nezerwa Plus') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full">
    <x-loader />
    <div class="min-h-full flex">

        <!-- Left Side: Solid Dark Branding Panel -->
        <div class="hidden lg:flex flex-1 relative bg-gray-900 border-r border-gray-800 text-white overflow-hidden">
            <!-- Subtle architectural dot pattern -->
            <div class="absolute inset-0 bg-[radial-gradient(#1f2937_1px,transparent_1px)] [background-size:24px_24px] opacity-40 pointer-events-none"></div>

            <div class="relative z-10 flex flex-col justify-center items-center h-full p-12 w-full text-center">
                <!-- App Icon Mark -->
                <div class="w-14 h-14 rounded-2xl bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center mb-6 shadow-sm">
                    <i data-lucide="boxes" class="w-7 h-7"></i>
                </div>

                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-white mb-2.5">
                    Nezerwa Plus
                </h1>
                <p class="text-gray-400 text-sm max-w-sm mx-auto leading-relaxed mb-8">
                    Modern inventory management, automated valuation, and multi-channel commerce.
                </p>

                <!-- Value Highlights -->
                <div class="w-full max-w-sm space-y-2.5 text-left">
                    <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-gray-800/40 border border-gray-800/80 text-xs text-gray-300">
                        <span class="w-7 h-7 rounded-lg bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center shrink-0">
                            <i data-lucide="package" class="w-3.5 h-3.5"></i>
                        </span>
                        <span>Real-time stock valuation & low-stock alerts</span>
                    </div>

                    <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-gray-800/40 border border-gray-800/80 text-xs text-gray-300">
                        <span class="w-7 h-7 rounded-lg bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center shrink-0">
                            <i data-lucide="flask-conical" class="w-3.5 h-3.5"></i>
                        </span>
                        <span>Raw materials, recipes & production tracking</span>
                    </div>

                    <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-gray-800/40 border border-gray-800/80 text-xs text-gray-300">
                        <span class="w-7 h-7 rounded-lg bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center shrink-0">
                            <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                        </span>
                        <span>Multi-channel transactions & supplier ledger</span>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-6 left-0 right-0 text-center text-gray-400 dark:text-gray-500 text-xs">
                &copy; {{ date('Y') }} Nezerwa Plus. All rights reserved.
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-gray-900">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                {{ $slot }}
            </div>
        </div>

    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
