<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Stock Manager') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 text-gray-900 font-sans antialiased min-h-screen">
    @auth
        <!-- Navbar -->
        <nav class="bg-white shadow mb-6">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold text-indigo-600">
                    <a href="{{ route('dashboard') }}">Stock Manager</a>
                </h1>

                <div class="flex items-center space-x-4">
                    <a href="{{ route('categories.index') }}" class="text-gray-700 hover:text-indigo-600">Categories</a>
                    <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-indigo-600">Products</a>
                    <a href="{{ route('suppliers.index') }}" class="text-gray-700 hover:text-indigo-600">Suppliers</a>
                    <a href="{{ route('customers.index') }}" class="text-gray-700 hover:text-indigo-600">Customers</a>
                    <a href="{{ route('transactions.index') }}" class="text-gray-700 hover:text-indigo-600">Transactions</a>

                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
</body>
</html>
