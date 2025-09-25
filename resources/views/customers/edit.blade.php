@extends('layouts.app')

@section('content')
<h2 class="text-2xl font-bold mb-4">Edit Customer</h2>

@if ($errors->any())
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('customers.update', $customer) }}" method="POST" class="space-y-4 bg-white p-4 rounded shadow">
    @csrf
    @method('PUT')

    <div>
        <label class="block">Name</label>
        <input type="text" name="name" class="w-full border p-2 rounded"
               value="{{ old('name', $customer->name) }}" required>
    </div>

    <div>
        <label class="block">Email</label>
        <input type="email" name="email" class="w-full border p-2 rounded"
               value="{{ old('email', $customer->email) }}">
    </div>

    <div>
        <label class="block">Phone</label>
        <input type="text" name="phone" class="w-full border p-2 rounded"
               value="{{ old('phone', $customer->phone) }}">
    </div>

    <div>
        <label class="block">Address</label>
        <textarea name="address" class="w-full border p-2 rounded" rows="3">{{ old('address', $customer->address) }}</textarea>
    </div>

    <div class="flex space-x-2">
        <button class="px-4 py-2 bg-indigo-600 text-white rounded">Update</button>
        <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-gray-300 rounded">Cancel</a>
    </div>
</form>
@endsection
