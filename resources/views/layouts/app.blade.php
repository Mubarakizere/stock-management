<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Manager</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow mb-6">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-indigo-600">Stock Manager</h1>
            <div class="space-x-4">
                <a href="{{ route('categories.index') }}" class="text-gray-700 hover:text-indigo-600">Categories</a>
                <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-indigo-600">Products</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4">
        @if(session('success'))
            <div class="mb-4 p-4 rounded bg-green-100 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
