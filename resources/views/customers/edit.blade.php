@extends('layouts.app')

@section('content')
<div class="card max-w-lg mx-auto">
    <h2 class="text-lg font-semibold mb-4">Edit Customer</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label class="form-label" for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $customer->name) }}" required class="form-input">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="address">Address</label>
            <textarea id="address" name="address" rows="3" class="form-textarea">{{ old('address', $customer->address) }}</textarea>
        </div>

        <div class="flex justify-end space-x-2 mt-6">
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
@endsection
