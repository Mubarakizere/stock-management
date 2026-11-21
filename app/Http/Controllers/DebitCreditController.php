<?php

namespace App\Http\Controllers;

use App\Models\DebitCredit;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class DebitCreditController extends Controller
{
    /**
     * List entries with live interactive filters, filter-aware totals, and AJAX support.
     */
    public function index(Request $request)
    {
        $rid  = uniqid('dc_idx_');
        $isPg = DB::getDriverName() === 'pgsql';
        $like = $isPg ? 'ilike' : 'like';

        try {
            $base = DebitCredit::query()
                ->with(['customer', 'supplier', 'user', 'transaction'])
                ->when($request->filled('type'),        fn($q) => $q->where('type', $request->type))
                ->when($request->filled('from'),        fn($q) => $q->whereDate('date', '>=', $request->from))
                ->when($request->filled('to'),          fn($q) => $q->whereDate('date', '<=', $request->to))
                ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->customer_id))
                ->when($request->filled('supplier_id'), fn($q) => $q->where('supplier_id', $request->supplier_id))
                ->when($request->filled('party_type'), function ($q) use ($request) {
                    if ($request->party_type === 'customer') {
                        $q->whereNotNull('customer_id');
                    } elseif ($request->party_type === 'supplier') {
                        $q->whereNotNull('supplier_id');
                    } elseif ($request->party_type === 'general') {
                        $q->whereNull('customer_id')->whereNull('supplier_id');
                    }
                })
                ->when($request->filled('q'), function ($q) use ($request, $like) {
                    $term = trim($request->q);
                    $q->where(function ($xx) use ($term, $like) {
                        $xx->where('description', $like, "%{$term}%")
                           ->orWhereHas('customer', fn($cq) => $cq->where('name', $like, "%{$term}%"))
                           ->orWhereHas('supplier', fn($sq) => $sq->where('name', $like, "%{$term}%"))
                           ->orWhereHas('user',     fn($uq) => $uq->where('name', $like, "%{$term}%"));
                        if (is_numeric($term)) {
                            $xx->orWhere('id', (int) $term)
                               ->orWhere('transaction_id', (int) $term);
                        }
                    });
                });

            // Filter-aware totals
            $debitsTotal  = (float) (clone $base)->where('type', 'debit')->sum('amount');
            $creditsTotal = (float) (clone $base)->where('type', 'credit')->sum('amount');
            $net          = $creditsTotal - $debitsTotal;
            $totalCount   = (int) (clone $base)->count();
            $debitsCount  = (int) (clone $base)->where('type', 'debit')->count();
            $creditsCount = (int) (clone $base)->where('type', 'credit')->count();

            // Page data
            $records = (clone $base)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();

            $pageCredits = (float) $records->getCollection()->where('type', 'credit')->sum('amount');
            $pageDebits  = (float) $records->getCollection()->where('type', 'debit')->sum('amount');

            $customers = Customer::select('id', 'name')->orderBy('name')->get();
            $suppliers = Supplier::select('id', 'name')->orderBy('name')->get();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'html'         => view('debits_credits._table', compact('records', 'pageCredits', 'pageDebits'))->render(),
                    'pagination'   => (string) $records->links(),
                    'total'        => $totalCount,
                    'credits'      => $creditsTotal,
                    'debits'       => $debitsTotal,
                    'net'          => $net,
                    'creditsCount' => $creditsCount,
                    'debitsCount'  => $debitsCount,
                ]);
            }

            return view('debits_credits.index', compact(
                'records',
                'debitsTotal',
                'creditsTotal',
                'net',
                'totalCount',
                'debitsCount',
                'creditsCount',
                'pageCredits',
                'pageDebits',
                'customers',
                'suppliers'
            ));
        } catch (Throwable $e) {
            Log::error('DebitsCredits@index failed', ['rid' => $rid, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load debits and credits ledger. Ref: ' . $rid);
        }
    }

    /**
     * Show create form.
     */
    public function create()
    {
        try {
            $customers = Customer::select('id', 'name')->orderBy('name')->get();
            $suppliers = Supplier::select('id', 'name')->orderBy('name')->get();
            return view('debits_credits.create', compact('customers', 'suppliers'));
        } catch (Throwable $e) {
            Log::error('DebitsCredits@create failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to open create form.');
        }
    }

    /**
     * Persist a new entry.
     */
    public function store(Request $request)
    {
        $rid = uniqid('dc_store_');
        try {
            $validated = $request->validate([
                'type'           => ['required', Rule::in(['debit', 'credit'])],
                'amount'         => ['required', 'numeric', 'min:0.01'],
                'description'    => ['nullable', 'string', 'max:500'],
                'date'           => ['nullable', 'date'],
                'customer_id'    => ['nullable', 'exists:customers,id', 'prohibited_with:supplier_id'],
                'supplier_id'    => ['nullable', 'exists:suppliers,id', 'prohibited_with:customer_id'],
                'transaction_id' => ['nullable', 'exists:transactions,id', 'unique:debits_credits,transaction_id'],
            ]);

            $validated['user_id'] = Auth::id();
            $validated['date']    = $validated['date'] ?? now()->toDateString();

            $entry = DebitCredit::create($validated);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Debit/Credit entry recorded successfully.',
                    'entry'   => $entry->load(['customer', 'supplier', 'user', 'transaction']),
                ]);
            }

            return redirect()
                ->route('debits-credits.index')
                ->with('success', 'Debit/Credit entry recorded successfully.');
        } catch (ValidationException $ve) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'errors' => $ve->errors()], 422);
            }
            throw $ve;
        } catch (Throwable $e) {
            Log::error('DebitsCredits@store failed', ['rid' => $rid, 'error' => $e->getMessage()]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to record entry. Ref: ' . $rid], 500);
            }
            return back()->withInput()->with('error', 'Failed to record entry. Ref: ' . $rid);
        }
    }

    /**
     * Show detail voucher view.
     */
    public function show(Request $request, DebitCredit $debits_credit)
    {
        try {
            $debits_credit->load(['customer', 'supplier', 'user', 'transaction']);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'entry'   => $debits_credit,
                ]);
            }

            return view('debits_credits.show', [
                'entry' => $debits_credit,
            ]);
        } catch (Throwable $e) {
            Log::error('DebitsCredits@show failed', ['id' => $debits_credit->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load entry details.');
        }
    }

    /**
     * Show edit form.
     */
    public function edit(Request $request, DebitCredit $debits_credit)
    {
        try {
            $debits_credit->load(['customer', 'supplier', 'user', 'transaction']);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'entry'   => $debits_credit,
                ]);
            }

            $customers = Customer::select('id', 'name')->orderBy('name')->get();
            $suppliers = Supplier::select('id', 'name')->orderBy('name')->get();

            return view('debits_credits.edit', [
                'entry'     => $debits_credit,
                'customers' => $customers,
                'suppliers' => $suppliers,
            ]);
        } catch (Throwable $e) {
            Log::error('DebitsCredits@edit failed', ['id' => $debits_credit->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to open edit form.');
        }
    }

    /**
     * Update an existing entry.
     */
    public function update(Request $request, DebitCredit $debits_credit)
    {
        $rid = uniqid('dc_update_');
        try {
            $validated = $request->validate([
                'type'           => ['required', Rule::in(['debit', 'credit'])],
                'amount'         => ['required', 'numeric', 'min:0.01'],
                'description'    => ['nullable', 'string', 'max:500'],
                'date'           => ['required', 'date'],
                'customer_id'    => ['nullable', 'exists:customers,id', 'prohibited_with:supplier_id'],
                'supplier_id'    => ['nullable', 'exists:suppliers,id', 'prohibited_with:customer_id'],
                'transaction_id' => [
                    'nullable',
                    'exists:transactions,id',
                    Rule::unique('debits_credits', 'transaction_id')->ignore($debits_credit->id),
                ],
            ]);

            $debits_credit->update($validated);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entry updated successfully.',
                    'entry'   => $debits_credit->load(['customer', 'supplier', 'user', 'transaction']),
                ]);
            }

            return redirect()
                ->route('debits-credits.index')
                ->with('success', 'Entry updated successfully.');
        } catch (ValidationException $ve) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'errors' => $ve->errors()], 422);
            }
            throw $ve;
        } catch (Throwable $e) {
            Log::error('DebitsCredits@update failed', ['rid' => $rid, 'id' => $debits_credit->id, 'error' => $e->getMessage()]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to update entry. Ref: ' . $rid], 500);
            }
            return back()->withInput()->with('error', 'Failed to update entry. Ref: ' . $rid);
        }
    }

    /**
     * Delete an entry.
     */
    public function destroy(Request $request, DebitCredit $debits_credit)
    {
        $rid = uniqid('dc_destroy_');
        try {
            $debits_credit->delete();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entry deleted successfully.',
                ]);
            }

            return redirect()
                ->route('debits-credits.index')
                ->with('success', 'Entry deleted successfully.');
        } catch (Throwable $e) {
            Log::error('DebitsCredits@destroy failed', ['rid' => $rid, 'id' => $debits_credit->id, 'error' => $e->getMessage()]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete entry. Ref: ' . $rid], 500);
            }
            return back()->with('error', 'Failed to delete entry. Ref: ' . $rid);
        }
    }
}
