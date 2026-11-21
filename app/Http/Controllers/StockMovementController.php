<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Production;
use App\Models\StockAdjustment;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\ItemLoan;
use App\Models\ItemLoanReturn;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovementController extends Controller
{
    /**
     * Display filterable stock history and audit ledger.
     */
    public function index(Request $request)
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'unit', 'type']);

        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 25;
        }

        // Build the filtered query (+ eager loads)
        $query = $this->buildQuery($request);

        // Paginate results
        $movements = $query->paginate($perPage)->appends($request->query());

        // Dynamic Totals for current filter
        $inTotal      = (float) (clone $query)->where('type', 'in')->sum('quantity');
        $outTotal     = (float) (clone $query)->where('type', 'out')->sum('quantity');
        $inTotalCost  = (float) (clone $query)->where('type', 'in')->sum('total_cost');
        $outTotalCost = (float) (clone $query)->where('type', 'out')->sum('total_cost');

        $totals = [
            'in'       => $inTotal,
            'out'      => $outTotal,
            'net'      => $inTotal - $outTotal,
            'in_cost'  => $inTotalCost,
            'out_cost' => $outTotalCost,
            'net_cost' => $inTotalCost - $outTotalCost,
        ];

        // Origin Breakdown for current filter
        $breakdown = [
            'out_sales'       => (float) (clone $query)->where('type', 'out')->where('source_type', Sale::class)->sum('quantity'),
            'out_returns'     => (float) (clone $query)->where('type', 'out')->where('source_type', PurchaseReturn::class)->sum('quantity'),
            'out_production'  => (float) (clone $query)->where('type', 'out')->where('source_type', Production::class)->sum('quantity'),
            'out_loans'       => (float) (clone $query)->where('type', 'out')->where('source_type', ItemLoan::class)->sum('quantity'),
            'out_adjustments' => (float) (clone $query)->where('type', 'out')->whereIn('source_type', [StockAdjustment::class, User::class])->sum('quantity'),

            'in_purchases'    => (float) (clone $query)->where('type', 'in')->where('source_type', Purchase::class)->sum('quantity'),
            'in_production'   => (float) (clone $query)->where('type', 'in')->where('source_type', Production::class)->sum('quantity'),
            'in_sale_returns' => (float) (clone $query)->where('type', 'in')->where('source_type', SaleReturn::class)->sum('quantity'),
            'in_loan_returns' => (float) (clone $query)->where('type', 'in')->where('source_type', ItemLoanReturn::class)->sum('quantity'),
            'in_adjustments'  => (float) (clone $query)->where('type', 'in')->whereIn('source_type', [StockAdjustment::class, User::class])->sum('quantity'),

            'adjustments'     => (float) (clone $query)->whereIn('source_type', [StockAdjustment::class, User::class])->sum('quantity'),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'html'        => view('stock_movements._table', compact('movements'))->render(),
                'pagination'  => $movements->links()->toHtml(),
                'totals'      => $totals,
                'breakdown'   => $breakdown,
                'total_count' => $movements->total(),
            ]);
        }

        return view('stock_movements.index', compact('movements', 'products', 'totals', 'breakdown'));
    }

    /**
     * Build query (shared by web, CSV, PDF, API).
     */
    private function buildQuery(Request $request)
    {
        $query = StockMovement::query();

        // Include trashed records if requested
        if ($request->boolean('show_deleted')) {
            $query->withTrashed();
        }

        $query->with([
            'product:id,name,unit,price,cost_price,type',
            'user:id,name',
            'source',
        ])->latest('id');

        // Product search / filter
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($s = trim((string)$request->input('q'))) {
            $pattern = "%{$s}%";
            $op = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function($w) use ($op, $pattern, $s) {
                $w->whereHas('product', function($pq) use ($op, $pattern) {
                    $pq->where('name', $op, $pattern);
                });
                if (is_numeric($s)) {
                    $w->orWhere('id', (int)$s)
                      ->orWhere('source_id', (int)$s);
                }
            });
        }

        // Flow Type filter (in / out)
        if ($request->filled('type')) {
            $type = strtolower($request->type);
            if (in_array($type, ['in', 'out'], true)) {
                $query->where('type', $type);
            }
        }

        // Date range filters
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Origin filter
        if ($request->filled('origin')) {
            $map = [
                'purchase'        => Purchase::class,
                'sale'            => Sale::class,
                'production'      => Production::class,
                'adjustment'      => [StockAdjustment::class, User::class],
                'purchase_return' => PurchaseReturn::class,
                'sale_return'     => SaleReturn::class,
                'item_loan'       => ItemLoan::class,
                'item_loan_return'=> ItemLoanReturn::class,
            ];
            $val = $request->origin;
            if (isset($map[$val])) {
                if (is_array($map[$val])) {
                    $query->whereIn('source_type', $map[$val]);
                } else {
                    $query->where('source_type', $map[$val]);
                }
            }
        }

        // Role-based visibility
        $user = auth()->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('cashier')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * Export filtered data to CSV.
     */
    public function exportCsv(Request $request)
    {
        $filename  = 'stock_history_' . now()->format('Ymd_His') . '.csv';
        $movements = $this->buildQuery($request)->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($movements) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date', 'Product', 'Type', 'Quantity', 'Unit Cost (RWF)', 'Total Cost (RWF)',
                'Reference / Note', 'Recorded By', 'Source', 'Origin Type'
            ]);

            foreach ($movements as $m) {
                $details = $m->sourceDetails();
                $ref = $m->referenceText();

                fputcsv($handle, [
                    optional($m->created_at)->format('Y-m-d H:i'),
                    optional($m->product)->name,
                    strtoupper($m->type),
                    $m->quantity,
                    $m->unit_cost,
                    $m->total_cost,
                    $ref ?: '—',
                    optional($m->user)->name ?: 'System',
                    $details['label'],
                    $details['origin'],
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export filtered data to PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = $this->buildQuery($request);
        $movements = $query->get();

        $inTotal      = (float) (clone $query)->where('type', 'in')->sum('quantity');
        $outTotal     = (float) (clone $query)->where('type', 'out')->sum('quantity');
        $inTotalCost  = (float) (clone $query)->where('type', 'in')->sum('total_cost');
        $outTotalCost = (float) (clone $query)->where('type', 'out')->sum('total_cost');

        $totals = [
            'in'       => $inTotal,
            'out'      => $outTotal,
            'net'      => $inTotal - $outTotal,
            'in_cost'  => $inTotalCost,
            'out_cost' => $outTotalCost,
        ];

        $breakdown = [
            'out_sales'       => (float) (clone $query)->where('type', 'out')->where('source_type', Sale::class)->sum('quantity'),
            'out_returns'     => (float) (clone $query)->where('type', 'out')->where('source_type', PurchaseReturn::class)->sum('quantity'),
            'out_production'  => (float) (clone $query)->where('type', 'out')->where('source_type', Production::class)->sum('quantity'),
            'out_loans'       => (float) (clone $query)->where('type', 'out')->where('source_type', ItemLoan::class)->sum('quantity'),
            'in_purchases'    => (float) (clone $query)->where('type', 'in')->where('source_type', Purchase::class)->sum('quantity'),
            'in_production'   => (float) (clone $query)->where('type', 'in')->where('source_type', Production::class)->sum('quantity'),
            'in_sale_returns' => (float) (clone $query)->where('type', 'in')->where('source_type', SaleReturn::class)->sum('quantity'),
            'in_loan_returns' => (float) (clone $query)->where('type', 'in')->where('source_type', ItemLoanReturn::class)->sum('quantity'),
            'adjustments'     => (float) (clone $query)->whereIn('source_type', [StockAdjustment::class, User::class])->sum('quantity'),
        ];

        $pdf = Pdf::loadView('stock_movements.report', compact('movements', 'totals', 'breakdown'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('stock_history_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Soft delete a stock movement (admin only).
     */
    public function destroy(StockMovement $movement)
    {
        $movement->delete();
        return back()->with('success', 'Stock movement record moved to trash.');
    }

    /**
     * Restore a soft-deleted stock movement (admin only).
     */
    public function restore($id)
    {
        $movement = StockMovement::withTrashed()->findOrFail($id);
        $movement->restore();
        return back()->with('success', 'Stock movement record restored successfully.');
    }

    /**
     * Permanently delete a stock movement (admin only).
     */
    public function forceDestroy($id)
    {
        $movement = StockMovement::withTrashed()->findOrFail($id);
        $movement->forceDelete();
        return back()->with('success', 'Stock movement record permanently deleted.');
    }
}
