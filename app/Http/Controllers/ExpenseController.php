<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\PaymentChannel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    /**
     * Display a listing of operating and capital expenses with executive KPIs.
     */
    public function index(Request $request)
    {
        // Normalize simple inputs
        if ($request->filled('method')) {
            $request->merge([
                'method' => strtolower((string) $request->input('method', '')),
            ]);
        }

        $perPage = (int) $request->input('per_page', 15);

        $q = Expense::query()
            ->with(['category', 'supplier', 'creator'])
            ->between($request->date('from'), $request->date('to'))
            ->category($request->integer('category_id'))
            ->forSupplier($request->integer('supplier_id'))
            ->method($request->input('method'))
            ->search($request->input('q'))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $expenses = $q->paginate($perPage)->withQueryString();
        $pageTotal = (float) $expenses->getCollection()->sum('amount');

        // Executive KPI aggregates
        $totalExpenses     = (float) Expense::sum('amount');
        $monthlyExpenses   = (float) Expense::whereDate('date', '>=', now()->startOfMonth())->sum('amount');
        $totalTransactions = (int) Expense::count();
        $topCategory       = Category::whereHas('expenses')
            ->withSum('expenses as total_spend', 'amount')
            ->orderByDesc('total_spend')
            ->first();

        // Payment channels active
        $channels = PaymentChannel::where('is_active', true)->orderBy('name')->get();

        // Method breakdowns for current filtered collection
        $byMethod = $channels->mapWithKeys(function ($channel) use ($expenses) {
            $slug = strtolower($channel->slug);
            $filtered = $expenses->getCollection()->filter(function ($e) use ($slug) {
                return strtolower($e->method ?? '') === $slug;
            });
            return [$slug => [
                'name'   => $channel->name,
                'count'  => $filtered->count(),
                'amount' => (float) $filtered->sum('amount'),
            ]];
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'html'       => view('expenses._table', compact('expenses'))->render(),
                'pagination' => (string) $expenses->links(),
                'total'      => $expenses->total(),
                'page_total' => $pageTotal,
            ]);
        }

        return view('expenses.index', [
            'expenses'          => $expenses,
            'categories'        => Category::query()
                ->whereIn('kind', ['expense', 'both'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'suppliers'         => Supplier::orderBy('name')->get(['id', 'name']),
            'channels'          => $channels,
            'pageTotal'         => $pageTotal,
            'totalExpenses'     => $totalExpenses,
            'monthlyExpenses'   => $monthlyExpenses,
            'totalTransactions' => $totalTransactions,
            'topCategory'       => $topCategory,
            'byMethod'          => $byMethod,
        ]);
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create()
    {
        return view('expenses.create', [
            'categories' => Category::query()
                ->whereIn('kind', ['expense', 'both'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'suppliers'  => Supplier::orderBy('name')->get(['id', 'name']),
            'channels'   => PaymentChannel::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        if ($request->filled('method')) {
            $request->merge([
                'method' => strtolower((string) $request->input('method', '')),
            ]);
        }

        $data = $request->validate([
            'date'        => ['required', 'date'],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($q) => $q->whereIn('kind', ['expense', 'both'])->where('is_active', true)
                ),
            ],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'method'      => ['required', 'string'],
            'reference'   => ['nullable', 'string', 'max:100'],
            'note'        => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();

        $expense = Expense::create($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Expense recorded successfully.',
                'expense' => $expense->load(['category', 'supplier']),
            ], 201);
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    /**
     * Display the specified expense detail.
     */
    public function show(Expense $expense)
    {
        $expense->load(['category', 'supplier', 'creator']);
        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Request $request, Expense $expense)
    {
        $expense->load(['category', 'supplier']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'expense' => $expense,
            ]);
        }

        return view('expenses.edit', [
            'expense'    => $expense,
            'categories' => Category::query()
                ->whereIn('kind', ['expense', 'both'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'suppliers'  => Supplier::orderBy('name')->get(['id', 'name']),
            'channels'   => PaymentChannel::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        if ($request->filled('method')) {
            $request->merge([
                'method' => strtolower((string) $request->input('method', '')),
            ]);
        }

        $data = $request->validate([
            'date'        => ['required', 'date'],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($q) => $q->whereIn('kind', ['expense', 'both'])->where('is_active', true)
                ),
            ],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'method'      => ['required', 'string'],
            'reference'   => ['nullable', 'string', 'max:100'],
            'note'        => ['nullable', 'string'],
        ]);

        $expense->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'expense' => $expense->load(['category', 'supplier']),
            ], 200);
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Request $request, Expense $expense)
    {
        $expense->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Expense record deleted successfully.'
            ], 200);
        }

        return redirect()->route('expenses.index')
            ->with('success', 'Expense record deleted successfully.');
    }
}
