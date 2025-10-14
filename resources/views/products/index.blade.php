@extends('layouts.app')
@section('title', 'Products')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    {{-- 🔹 Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800">Products</h1>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            + Add Product
        </a>
    </div>

    {{-- 🔹 Flash Messages --}}
    @if (session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 p-3">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 p-3">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- 🔹 Product Table --}}
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total In</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Out</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current Stock</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $product)
                    @php
                        $current = $product->currentStock();
                        $isLow = $current <= 5;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-all">
                        {{-- Name --}}
                        <td class="px-4 py-3 text-gray-800 font-medium">
                            <a href="{{ route('products.show', $product) }}"
                               class="hover:text-indigo-600 transition-colors">
                                {{ $product->name }}
                            </a>
                        </td>

                        {{-- Category --}}
                        <td class="px-4 py-3 text-gray-700">
                            {{ $product->category->name ?? '—' }}
                        </td>

                        {{-- Price --}}
                        <td class="px-4 py-3 text-right text-gray-700">
                            RWF {{ number_format($product->price, 0) }}
                        </td>

                        {{-- Total In --}}
                        <td class="px-4 py-3 text-right text-green-700 font-medium">
                            {{ number_format($product->total_in ?? 0, 0) }}
                        </td>

                        {{-- Total Out --}}
                        <td class="px-4 py-3 text-right text-red-700 font-medium">
                            {{ number_format($product->total_out ?? 0, 0) }}
                        </td>

                        {{-- Current Stock --}}
                        <td class="px-4 py-3 text-right font-semibold {{ $isLow ? 'text-red-600' : 'text-gray-900' }}">
                            {{ number_format($current, 0) }}
                            @if($isLow)
                                <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                    Low
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right space-x-1">
                            <a href="{{ route('products.show', $product) }}"
                               class="btn btn-secondary text-xs">
                               View
                            </a>

                            <a href="{{ route('products.edit', $product) }}"
                               class="btn btn-outline text-xs">
                               Edit
                            </a>

                            <a href="{{ route('stock.history', ['product_id' => $product->id]) }}"
                               class="btn btn-outline text-xs">
                               Movements
                            </a>

                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Delete this product? This will not delete stock movements.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger text-xs">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500 text-sm">
                            No products found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
