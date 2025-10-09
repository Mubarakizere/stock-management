@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-semibold text-gray-800">Customers</h1>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">+ Add Customer</a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded bg-green-100 text-green-700 text-sm">
        {{ session('success') }}
    </div>
@endif

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->email ?? '—' }}</td>
                        <td>{{ $customer->phone ?? '—' }}</td>
                        <td>{{ $customer->address ?? '—' }}</td>
                        <td class="text-right">
                            <div class="table-actions">
                                <a href="{{ route('customers.edit', $customer) }}" class="btn-table btn-table-edit">Edit</a>
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure?')" class="btn-table btn-table-delete">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="table-empty">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
