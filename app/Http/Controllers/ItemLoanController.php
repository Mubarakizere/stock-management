<?php

namespace App\Http\Controllers;

use App\Models\ItemLoan;
use App\Models\ItemLoanReturn;
use App\Models\PartnerCompany;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItemLoanController extends Controller
{
    /**
     * GET /inter-loans
     * Filters: search, direction, status, partner_id, from, to, overdue
     */
    public function index(Request $request)
    {
        $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = ItemLoan::query()
            ->with(['partner', 'product', 'returns.user', 'user'])
            ->when($request->filled('search'), function ($q) use ($request, $likeOp) {
                $rawTerm = trim($request->string('search'));
                $term = '%' . $rawTerm . '%';
                $numericId = preg_replace('/[^0-9]/', '', $rawTerm);

                $q->where(function ($qq) use ($term, $numericId, $likeOp) {
                    $qq->where('item_name', $likeOp, $term)
                       ->orWhere('unit', $likeOp, $term)
                       ->orWhereHas('partner', function ($qp) use ($term, $likeOp) {
                           $qp->where('name', $likeOp, $term);
                       })
                       ->orWhereHas('product', function ($qpr) use ($term, $likeOp) {
                           $qpr->where('name', $likeOp, $term);
                       });

                    if (!empty($numericId)) {
                        $qq->orWhere('id', (int) $numericId);
                    }
                });
            })
            ->when(in_array($request->string('direction'), ['given', 'taken'], true), function ($q) use ($request) {
                $q->where('direction', $request->string('direction'));
            })
            ->when(in_array($request->string('status'), ['pending', 'partial', 'returned', 'overdue'], true), function ($q) use ($request) {
                $q->where('status', $request->string('status'));
            })
            ->when($request->filled('partner_id'), function ($q) use ($request) {
                $q->where('partner_id', $request->input('partner_id'));
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->whereDate('loan_date', '>=', $request->date('from')->format('Y-m-d'));
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->whereDate('loan_date', '<=', $request->date('to')->format('Y-m-d'));
            })
            ->when($request->boolean('overdue'), function ($q) {
                $q->whereNotNull('due_date')
                  ->whereDate('due_date', '<', now()->toDateString())
                  ->whereColumn('quantity', '>', 'quantity_returned');
            });

        // 4-KPI Deck Calculations
        $stats = [
            'total_active_qty' => (float) (ItemLoan::where('status', '!=', 'returned')
                ->whereRaw('quantity > quantity_returned')
                ->sum(DB::raw('quantity - quantity_returned')) ?? 0),
            'active_count'     => ItemLoan::where('status', '!=', 'returned')
                ->whereRaw('quantity > quantity_returned')
                ->count(),
            'given_qty'        => (float) (ItemLoan::where('direction', 'given')->sum('quantity') ?? 0),
            'given_count'      => ItemLoan::where('direction', 'given')->count(),
            'taken_qty'        => (float) (ItemLoan::where('direction', 'taken')->sum('quantity') ?? 0),
            'taken_count'      => ItemLoan::where('direction', 'taken')->count(),
            'overdue_count'    => ItemLoan::whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereRaw('quantity > quantity_returned')
                ->count(),
            'overdue_qty'      => (float) (ItemLoan::whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereRaw('quantity > quantity_returned')
                ->sum(DB::raw('quantity - quantity_returned')) ?? 0),
            'total_returned_qty' => (float) (ItemLoan::sum('quantity_returned') ?? 0),
        ];

        $loans = $query->orderByDesc('loan_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $partners = PartnerCompany::orderBy('name')->get(['id', 'name', 'phone', 'contact_person']);
        $products = Product::orderBy('name')->get(['id', 'name', 'stock', 'unit']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'html'       => view('item_loans._table', compact('loans'))->render(),
                'stats'      => $stats,
                'total'      => $loans->total(),
                'pagination' => $loans->links()->toHtml(),
            ]);
        }

        return view('item_loans.index', [
            'loans'    => $loans,
            'stats'    => $stats,
            'partners' => $partners,
            'products' => $products,
            'filters'  => $request->only(['search', 'direction', 'status', 'partner_id', 'from', 'to', 'overdue']),
        ]);
    }

    public function create()
    {
        $partners = PartnerCompany::orderBy('name')->get(['id', 'name']);
        $products = Product::orderBy('name')->get(['id', 'name', 'stock', 'unit']);
        return view('item_loans.create', compact('partners', 'products'));
    }

    /**
     * On create:
     * - Set status = 'pending' initially.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_id'  => ['required', Rule::exists('partner_companies', 'id')],
            'product_id'  => ['nullable', Rule::exists('products', 'id')],
            'direction'   => ['required', Rule::in(['given', 'taken'])],
            'item_name'   => ['required', 'string', 'max:255'],
            'unit'        => ['nullable', 'string', 'max:20'],
            'quantity'    => ['required', 'numeric', 'min:0.01'],
            'loan_date'   => ['required', 'date'],
            'due_date'    => ['nullable', 'date', 'after_or_equal:loan_date'],
            'notes'       => ['nullable', 'string'],
        ]);

        $loan = DB::transaction(function () use ($data) {
            $loan = new ItemLoan($data);
            $loan->quantity_returned = 0;
            $loan->status = 'pending';
            $loan->user_id = Auth::id();
            $loan->save();

            // Stock Movement
            if ($loan->product_id) {
                $product = Product::find($loan->product_id);
                $cost    = (float) ($product->cost_price ?? 0);
                $qty     = (float) $loan->quantity;

                // If GIVEN: we lose stock (OUT)
                // If TAKEN: we gain stock (IN)
                $type = $loan->direction === 'given' ? 'out' : 'in';

                StockMovement::create([
                    'product_id'  => $loan->product_id,
                    'type'        => $type,
                    'quantity'    => $qty,
                    'unit_cost'   => $cost,
                    'total_cost'  => round($qty * $cost, 2),
                    'source_type' => ItemLoan::class,
                    'source_id'   => $loan->id,
                    'user_id'     => Auth::id(),
                ]);
            }

            return $loan;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Item loan recorded successfully.',
                'loan'    => $loan->load(['partner', 'product']),
            ]);
        }

        return redirect()->route('item-loans.index')->with('success', 'Loan recorded successfully.');
    }

    public function show(Request $request, ItemLoan $itemLoan)
    {
        $itemLoan->load(['partner', 'product', 'returns' => function ($q) {
            $q->orderBy('return_date')->orderBy('id')->with('user');
        }, 'user']);

        if ($request->wantsJson() || $request->ajax()) {
            $remaining = (float) $itemLoan->remaining;
            $isOverdue = (bool) $itemLoan->is_overdue;
            $qty = (float) $itemLoan->quantity;
            $ret = (float) $itemLoan->quantity_returned;

            return response()->json([
                'id'                => $itemLoan->id,
                'code'              => '#IL-' . str_pad($itemLoan->id, 5, '0', STR_PAD_LEFT),
                'direction'         => $itemLoan->direction,
                'item_name'         => $itemLoan->item_name,
                'unit'              => $itemLoan->unit ?? 'pcs',
                'quantity'          => $qty,
                'quantity_returned' => $ret,
                'remaining'         => $remaining,
                'progress'          => $qty > 0 ? min(100, (int) round(($ret / $qty) * 100)) : 0,
                'loan_date'         => optional($itemLoan->loan_date)->format('Y-m-d'),
                'due_date'          => optional($itemLoan->due_date)->format('Y-m-d'),
                'is_overdue'        => $isOverdue,
                'status'            => $itemLoan->status,
                'notes'             => $itemLoan->notes,
                'partner'           => $itemLoan->partner ? [
                    'id'             => $itemLoan->partner->id,
                    'name'           => $itemLoan->partner->name,
                    'contact_person' => $itemLoan->partner->contact_person,
                    'phone'          => $itemLoan->partner->phone,
                    'email'          => $itemLoan->partner->email,
                ] : null,
                'product'           => $itemLoan->product ? [
                    'id'    => $itemLoan->product->id,
                    'name'  => $itemLoan->product->name,
                    'stock' => $itemLoan->product->stock,
                ] : null,
                'user_name'         => optional($itemLoan->user)->name ?? 'System',
                'returns'           => $itemLoan->returns->map(function ($r) use ($itemLoan) {
                    return [
                        'id'           => $r->id,
                        'returned_qty' => (float) $r->returned_qty,
                        'return_date'  => optional($r->return_date)->format('Y-m-d'),
                        'note'         => $r->note,
                        'user_name'    => optional($r->user)->name ?? 'System',
                    ];
                }),
                'has_returns'       => $itemLoan->returns->isNotEmpty(),
                'created_at'        => optional($itemLoan->created_at)->format('Y-m-d H:i'),
            ]);
        }

        return view('item_loans.show', [
            'loan' => $itemLoan,
        ]);
    }

    public function edit(ItemLoan $itemLoan)
    {
        $partners = PartnerCompany::orderBy('name')->get(['id', 'name']);
        $hasReturns = $itemLoan->returns()->exists();
        $products = Product::orderBy('name')->get(['id', 'name', 'stock', 'unit']);

        return view('item_loans.edit', [
            'loan'       => $itemLoan,
            'partners'   => $partners,
            'products'   => $products,
            'hasReturns' => $hasReturns,
        ]);
    }

    public function update(Request $request, ItemLoan $itemLoan)
    {
        $hasReturns = $itemLoan->returns()->exists();

        $rules = [
            'partner_id'  => ['required', Rule::exists('partner_companies', 'id')],
            'product_id'  => ['nullable', Rule::exists('products', 'id')],
            'direction'   => ['required', Rule::in(['given', 'taken'])],
            'item_name'   => ['required', 'string', 'max:255'],
            'unit'        => ['nullable', 'string', 'max:20'],
            'loan_date'   => ['required', 'date'],
            'due_date'    => ['nullable', 'date', 'after_or_equal:loan_date'],
            'notes'       => ['nullable', 'string'],
        ];

        if ($hasReturns) {
            $request->merge(['quantity' => $itemLoan->quantity]);
        } else {
            $rules['quantity'] = ['required', 'numeric', 'min:0.01'];
        }

        $data = $request->validate($rules);

        if (!$hasReturns) {
            if ((float) $data['quantity'] < (float) $itemLoan->quantity_returned) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors'  => ['quantity' => ['Quantity cannot be less than already returned.']],
                    ], 422);
                }
                return back()->withErrors(['quantity' => 'Quantity cannot be less than already returned.'])
                             ->withInput();
            }
        }

        DB::transaction(function () use ($data, $itemLoan) {
            $oldProductId = $itemLoan->product_id;
            $oldDirection = $itemLoan->direction;
            $oldQuantity = (float) $itemLoan->quantity;

            $itemLoan->fill($data);
            $itemLoan->refreshStatus();
            $itemLoan->save();

            // Update stock movement if product_id or direction changed
            if ($oldProductId && ($oldProductId != $itemLoan->product_id || $oldDirection != $itemLoan->direction || $oldQuantity != (float) $itemLoan->quantity)) {
                $stockMovement = StockMovement::where('source_type', ItemLoan::class)
                    ->where('source_id', $itemLoan->id)
                    ->first();

                if ($stockMovement) {
                    if ($oldProductId != $itemLoan->product_id) {
                        $stockMovement->delete();

                        if ($itemLoan->product_id) {
                            $product = Product::find($itemLoan->product_id);
                            $cost = (float) ($product->cost_price ?? 0);
                            $qty = (float) $itemLoan->quantity;
                            $type = $itemLoan->direction === 'given' ? 'out' : 'in';

                            StockMovement::create([
                                'product_id'  => $itemLoan->product_id,
                                'type'        => $type,
                                'quantity'    => $qty,
                                'unit_cost'   => $cost,
                                'total_cost'  => round($qty * $cost, 2),
                                'source_type' => ItemLoan::class,
                                'source_id'   => $itemLoan->id,
                                'user_id'     => Auth::id(),
                            ]);
                        }
                    } else {
                        $type = $itemLoan->direction === 'given' ? 'out' : 'in';
                        $product = Product::find($itemLoan->product_id);
                        $cost = (float) ($product->cost_price ?? 0);
                        $qty = (float) $itemLoan->quantity;

                        $stockMovement->update([
                            'type'        => $type,
                            'quantity'    => $qty,
                            'unit_cost'   => $cost,
                            'total_cost'  => round($qty * $cost, 2),
                        ]);
                    }
                }
            } elseif (!$oldProductId && $itemLoan->product_id) {
                $product = Product::find($itemLoan->product_id);
                $cost = (float) ($product->cost_price ?? 0);
                $qty = (float) $itemLoan->quantity;
                $type = $itemLoan->direction === 'given' ? 'out' : 'in';

                StockMovement::create([
                    'product_id'  => $itemLoan->product_id,
                    'type'        => $type,
                    'quantity'    => $qty,
                    'unit_cost'   => $cost,
                    'total_cost'  => round($qty * $cost, 2),
                    'source_type' => ItemLoan::class,
                    'source_id'   => $itemLoan->id,
                    'user_id'     => Auth::id(),
                ]);
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Item loan updated successfully.',
                'loan'    => $itemLoan->fresh(['partner', 'product']),
            ]);
        }

        return redirect()->route('item-loans.index')->with('success', 'Loan updated successfully.');
    }

    /**
     * Safest delete: allow only when NO returns exist.
     */
    public function destroy(Request $request, ItemLoan $itemLoan)
    {
        if ($itemLoan->returns()->exists()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete an item loan that has recorded returns. Reversal of returns is required first.',
                ], 422);
            }
            return back()->withErrors(['delete' => 'Cannot delete a loan that has returns.']);
        }

        DB::transaction(function () use ($itemLoan) {
            StockMovement::where('source_type', ItemLoan::class)
                ->where('source_id', $itemLoan->id)
                ->delete();

            $itemLoan->delete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Item loan deleted successfully.',
            ]);
        }

        return redirect()->route('item-loans.index')->with('success', 'Loan deleted successfully.');
    }

    /**
     * POST /inter-loans/{itemLoan}/return
     * Validate returned_qty <= remaining; update header + status.
     */
    public function recordReturn(Request $request, ItemLoan $itemLoan)
    {
        $request->validate([
            'returned_qty' => ['required', 'numeric', 'min:0.01'],
            'return_date'  => ['required', 'date'],
            'note'         => ['nullable', 'string'],
        ]);

        $line = null;
        $loan = null;

        DB::transaction(function () use ($request, $itemLoan, &$line, &$loan) {
            $loan = ItemLoan::whereKey($itemLoan->id)->lockForUpdate()->first();

            $remaining = (float) $loan->remaining;
            $ret = (float) $request->input('returned_qty');

            if ($ret > $remaining + 1e-9) {
                abort(422, 'Returned quantity exceeds remaining quantity (' . number_format($remaining, 2) . ').');
            }

            // Create return line
            $line = new ItemLoanReturn([
                'item_loan_id' => $loan->id,
                'returned_qty' => $ret,
                'return_date'  => $request->date('return_date')->format('Y-m-d'),
                'note'         => $request->input('note'),
                'user_id'      => Auth::id(),
            ]);
            $line->save();

            // Update header totals
            $loan->quantity_returned = (float) $loan->quantity_returned + $ret;
            $loan->refreshStatus();
            $loan->save();

            // Stock Movement for Return
            if ($loan->product_id) {
                $product = Product::find($loan->product_id);
                $cost    = (float) ($product->cost_price ?? 0);

                // If loan was GIVEN (stock OUT), return means stock IN
                // If loan was TAKEN (stock IN), return means stock OUT
                $type = $loan->direction === 'given' ? 'in' : 'out';

                StockMovement::create([
                    'product_id'  => $loan->product_id,
                    'type'        => $type,
                    'quantity'    => $ret,
                    'unit_cost'   => $cost,
                    'total_cost'  => round($ret * $cost, 2),
                    'source_type' => ItemLoanReturn::class,
                    'source_id'   => $line->id,
                    'user_id'     => Auth::id(),
                ]);
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Return recorded successfully.',
                'loan'    => $loan ? $loan->fresh(['partner', 'product', 'returns.user']) : null,
            ]);
        }

        return back()->with('success', 'Return recorded successfully.');
    }
}
