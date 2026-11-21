<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Loan;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));

        $customersQuery = Customer::query()
            ->withCount('sales')
            ->withSum('sales as total_spent', 'total_amount');

        if ($query !== '') {
            $customersQuery->where(function ($qB) use ($query) {
                $qB->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            });
        }

        $customers = $customersQuery
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $totalCustomers = Customer::count();
        $activeCustomers = Customer::has('sales')->count();
        $totalRevenue = (float) Sale::whereNotNull('customer_id')->sum('total_amount');

        // Total outstanding receivables from customer loans
        $pendingLoans = Loan::where('type', 'given')
            ->where('status', 'pending')
            ->whereNotNull('customer_id')
            ->with('payments')
            ->get();
        $totalReceivables = (float) $pendingLoans->sum('remaining');

        return view('customers.index', [
            'customers'        => $customers,
            'query'            => $query,
            'totalCustomers'   => $totalCustomers,
            'activeCustomers'  => $activeCustomers,
            'totalRevenue'     => $totalRevenue,
            'totalReceivables' => $totalReceivables,
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->loadMissing([
            'sales' => fn($q) => $q->latest()->take(10),
            'loans' => fn($q) => $q->with('payments')->latest(),
        ]);

        $totalOrders = (int) $customer->sales()->count();
        $totalSpent  = (float) $customer->sales()->sum('total_amount');
        $totalPaid   = (float) $customer->sales()->sum('amount_paid');

        // Customer's given pending loans
        $customerLoans = $customer->loans()
            ->where('type', 'given')
            ->where('status', 'pending')
            ->with('payments')
            ->get();
        $outstandingCredit = (float) $customerLoans->sum('remaining');

        $avgOrderValue = $totalOrders > 0 ? ($totalSpent / $totalOrders) : 0;
        $lastSale = $customer->sales()->latest()->first();

        return view('customers.show', compact(
            'customer',
            'totalOrders',
            'totalSpent',
            'totalPaid',
            'outstandingCredit',
            'avgOrderValue',
            'lastSale'
        ));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create($data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer account created successfully.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $customer->update($data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer details updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $name = $customer->name;
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', "Customer '{$name}' has been deleted successfully. Historical ledger entries were retained as unassigned.");
    }
}
