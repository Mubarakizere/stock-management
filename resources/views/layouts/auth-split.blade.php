<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'NezerwaPlus') }}</title>

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

        <!-- Left Side: Branding & Visuals -->
        <!-- Left Side: Branding & Visuals -->
        <div class="hidden lg:flex flex-1 relative bg-emerald-900 text-white overflow-hidden" style="background-color: #064e3b;">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-600 to-emerald-900 opacity-90" style="background: linear-gradient(to bottom right, #059669, #064e3b);"></div>
            
            <div class="relative z-10 flex flex-col justify-center items-center h-full p-12 w-full text-center">
                <div class="mb-8">
                    <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-sm inline-block">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold tracking-tight text-white mb-4">Nezerwa Plus</h1>
                <p class="text-emerald-100 text-lg max-w-md mx-auto">
                    The premium solution for modern commerce.
                </p>
            </div>
            
            <div class="absolute bottom-6 left-0 right-0 text-center text-emerald-200/60 text-xs">
                &copy; {{ date('Y') }} NezerwaPlus. All rights reserved.
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-gray-900">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                {{ $slot }}
            </div>
        </div>

    </div>
</body>
</html>
