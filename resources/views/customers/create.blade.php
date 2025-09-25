@extends('layouts.app')

@section('content')
<h2 class="text-2xl font-bold mb-4">Add Customer</h2>

<form action="{{ route('customers.store') }}" method="POST" class="space-y-4 bg-white p-4 rounded shadow">
    @csrf
    <div>
        <label class="block">Name</label>
        <input type="text" name="name" class="w-full border p-2 rounded" required>
    </div>
    <div>
        <label class="block">Email</label>
        <input type="email" name="email" class="w-full border p-2 rounded">
    </div>
    <div>
        <label class="block">Phone</label>
        <input type="text" name="phone" class="w-full border p-2 rounded">
    </div>
    <div>
        <label class="block">Address</label>
        <textarea name="address" class="w-full border p-2 rounded"></textarea>
    </div>
    <button class="px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
</form>
@endsection
