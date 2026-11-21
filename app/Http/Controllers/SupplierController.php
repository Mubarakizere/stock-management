<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Loan;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers with executive KPIs and live search.
     */
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));

        $suppliersQuery = Supplier::query()
            ->withCount('purchases')
            ->withSum('purchases as total_spend', 'total_amount')
            ->withSum('purchases as total_balance_due', 'balance_due');

        if ($query !== '') {
            $suppliersQuery->where(function ($qB) use ($query) {
                $qB->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            });
        }

        $suppliers = $suppliersQuery
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::whereHas('purchases')->orWhereHas('loans')->count();
        $totalSpend = (float) Purchase::whereNotNull('supplier_id')->sum('total_amount');

        // Outstanding payables: balance due from purchases + remaining on pending loans taken from suppliers
        $purchasePayables = (float) Purchase::whereNotNull('supplier_id')
            ->whereIn('status', ['pending', 'partial'])
            ->sum('balance_due');

        $pendingTakenLoans = Loan::where('type', 'taken')
            ->where('status', 'pending')
            ->whereNotNull('supplier_id')
            ->with('payments')
            ->get();
        $loanPayables = (float) $pendingTakenLoans->sum('remaining');
        $totalPayables = $purchasePayables + $loanPayables;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'html'       => view('suppliers._table', compact('suppliers'))->render(),
                'pagination' => (string) $suppliers->links(),
                'total'      => $suppliers->total(),
            ]);
        }

        return view('suppliers.index', [
            'suppliers'       => $suppliers,
            'query'           => $query,
            'totalSuppliers'  => $totalSuppliers,
            'activeSuppliers' => $activeSuppliers,
            'totalSpend'      => $totalSpend,
            'totalPayables'   => $totalPayables,
        ]);
    }

    /**
     * Show the detailed supplier profile and ledger.
     */
    public function show(Supplier $supplier)
    {
        $supplier->loadMissing([
            'purchases' => fn($q) => $q->latest()->take(15),
            'loans' => fn($q) => $q->with('payments')->latest(),
            'purchaseReturns' => fn($q) => $q->latest()->take(5),
        ]);

        $totalOrders   = (int) $supplier->purchases()->count();
        $totalSpend    = (float) $supplier->purchases()->sum('total_amount');
        $totalPaid     = (float) $supplier->purchases()->sum('amount_paid');
        $poBalanceDue  = (float) $supplier->purchases()->sum('balance_due');

        $pendingLoans  = $supplier->loans()
            ->where('type', 'taken')
            ->where('status', 'pending')
            ->with('payments')
            ->get();
        $loanRemaining = (float) $pendingLoans->sum('remaining');
        $outstandingPayables = $poBalanceDue + $loanRemaining;

        $avgOrderValue = $totalOrders > 0 ? ($totalSpend / $totalOrders) : 0;
        $lastPurchase  = $supplier->purchases()->latest()->first();

        return view('suppliers.show', compact(
            'supplier',
            'totalOrders',
            'totalSpend',
            'totalPaid',
            'outstandingPayables',
            'avgOrderValue',
            'lastPurchase'
        ));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $supplier = Supplier::create($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => "Supplier '{$supplier->name}' registered successfully.",
                'supplier' => $supplier,
            ]);
        }

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', "Supplier '{$supplier->name}' registered successfully.");
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Request $request, Supplier $supplier)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'supplier' => $supplier,
            ]);
        }

        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $supplier->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => "Supplier '{$supplier->name}' updated successfully.",
                'supplier' => $supplier,
            ]);
        }

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', "Supplier '{$supplier->name}' updated successfully.");
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Request $request, Supplier $supplier)
    {
        $name = $supplier->name;
        $supplier->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Supplier '{$name}' deleted successfully.",
            ]);
        }

        return redirect()
            ->route('suppliers.index')
            ->with('success', "Supplier '{$name}' deleted successfully.");
    }
}
