<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionMaterial;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionController extends Controller
{
    /** List all production runs */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = Production::query()
            ->with(['product', 'user', 'materials.rawMaterial'])
            ->latest('produced_at');

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        // Filter by batch expiration status
        if ($expStatus = $request->input('expiry_status')) {
            $today = now()->toDateString();
            if ($expStatus === 'fresh') {
                $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '>', now()->addDays(30)->toDateString());
            } elseif ($expStatus === 'expiring_soon') {
                $query->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [$today, now()->addDays(30)->toDateString()]);
            } elseif ($expStatus === 'expired') {
                $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', $today);
            }
        }

        // Filter by yield efficiency
        if ($yieldStatus = $request->input('yield_status')) {
            if ($yieldStatus === 'optimal') {
                $query->where('yield_percentage', '>=', 95.0);
            } elseif ($yieldStatus === 'moderate_scrap') {
                $query->whereBetween('yield_percentage', [85.0, 94.99]);
            } elseif ($yieldStatus === 'high_waste') {
                $query->where('yield_percentage', '<', 85.0);
            }
        }

        // Search by product name, notes, or batch number
        if ($s = trim((string) $request->input('q'))) {
            $op = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $pattern = "%{$s}%";
            $query->where(function ($w) use ($op, $pattern) {
                $w->whereHas('product', fn($q) => $q->where('name', $op, $pattern))
                  ->orWhere('batch_number', $op, $pattern)
                  ->orWhere('notes', $op, $pattern);
            });
        }

        $productions = $query->paginate($perPage)->appends($request->query());

        // Products for filter dropdown
        $products = Product::products()->orderBy('name')->get(['id', 'name', 'unit']);

        // Executive Manufacturing & Yield KPIs
        $totalRuns = Production::count();
        $totalProduced = (float) Production::sum('quantity');
        $avgYield = $totalRuns > 0 ? (float) Production::avg('yield_percentage') : 100.0;
        $totalScrapQty = (float) Production::sum('waste_quantity');
        $expiringSoonCount = Production::expiringSoon(30)->count();
        $expiredCount = Production::expired()->count();

        // Calculate total scrap loss cost
        $totalScrapCost = (float) Production::where('waste_quantity', '>', 0)
            ->selectRaw('SUM(waste_quantity * COALESCE(unit_cost, 0)) as scrap_loss')
            ->value('scrap_loss');

        $stats = [
            'total_runs'          => $totalRuns,
            'total_produced'      => $totalProduced,
            'avg_yield'           => round($avgYield, 1),
            'total_scrap_qty'     => $totalScrapQty,
            'total_scrap_cost'    => $totalScrapCost,
            'expiring_soon_count' => $expiringSoonCount,
            'expired_count'       => $expiredCount,
            'active_recipes'      => Product::products()->has('recipeItems')->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('productions._table', compact('productions'))->render(),
                'pagination' => $productions->links()->toHtml(),
                'stats' => [
                    'total_runs'       => number_format($stats['total_runs']),
                    'total_produced'   => number_format($stats['total_produced'], 2),
                    'avg_yield'        => number_format($stats['avg_yield'], 1) . '%',
                    'total_scrap_qty'  => number_format($stats['total_scrap_qty'], 2),
                    'total_scrap_cost' => number_format($stats['total_scrap_cost'], 2) . ' RWF',
                    'expiring_soon'    => $stats['expiring_soon_count'],
                    'active_recipes'   => number_format($stats['active_recipes']),
                ]
            ]);
        }

        return view('productions.index', compact('productions', 'products', 'stats'));
    }

    /** Show the create production form */
    public function create(Request $request)
    {
        // All finished products with recipes and raw material pricing
        $products = Product::products()
            ->with(['recipeItems.rawMaterial'])
            ->orderBy('name')
            ->get();

        $selectedProductId = $request->integer('product_id');

        // Generate batch number suggestion
        $defaultBatchNumber = Production::generateBatchNumber($selectedProductId ?: null);
        $defaultMfgDate = now()->toDateString();
        $defaultExpiryDate = now()->addMonths(6)->toDateString();

        return view('productions.create', compact(
            'products',
            'selectedProductId',
            'defaultBatchNumber',
            'defaultMfgDate',
            'defaultExpiryDate'
        ));
    }

    /** Store a new production run */
    public function store(Request $request)
    {
        $request->validate([
            'product_id'        => ['required', 'integer', 'exists:products,id'],
            'quantity'          => ['required', 'numeric', 'min:0.01'],
            'expected_quantity' => ['nullable', 'numeric', 'min:0'],
            'waste_quantity'    => ['nullable', 'numeric', 'min:0'],
            'waste_reason'      => ['nullable', 'string', 'max:255'],
            'batch_number'      => ['nullable', 'string', 'max:60'],
            'mfg_date'          => ['nullable', 'date'],
            'expiry_date'       => ['nullable', 'date'],
            'produced_at'       => ['nullable', 'date'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::with('recipeItems.rawMaterial')->findOrFail($request->integer('product_id'));

        // Verify product has a recipe
        if ($product->recipeItems->isEmpty()) {
            return back()->withErrors(['product_id' => 'This product has no recipe defined. Please set up a recipe first.'])->withInput();
        }

        $qtyActual   = (float) $request->input('quantity');
        $wasteQty    = (float) $request->input('waste_quantity', 0);
        $expectedQty = $request->filled('expected_quantity')
            ? (float) $request->input('expected_quantity')
            : ($qtyActual + $wasteQty);

        if ($expectedQty <= 0) {
            $expectedQty = $qtyActual;
        }

        // Calculate Yield Percentage
        $yieldPercentage = round(($qtyActual / $expectedQty) * 100, 2);

        // Batch lot identifier
        $batchNumber = trim((string) $request->input('batch_number')) ?: Production::generateBatchNumber($product->id);
        $mfgDate     = $request->input('mfg_date') ?: now()->toDateString();
        $expiryDate  = $request->input('expiry_date');

        // Total input batch size that determined raw material consumption
        $batchInputSize = max($expectedQty, $qtyActual + $wasteQty);

        // Check raw material availability based on batch input size
        $shortages = [];
        foreach ($product->recipeItems as $ri) {
            $neededBase = $ri->baseQuantityNeeded($batchInputSize);
            $available  = $ri->rawMaterial->currentStock();
            $rmUnit     = $ri->rawMaterial->unit ?: 'pcs';
            if ($available < $neededBase) {
                $shortages[] = "{$ri->rawMaterial->name}: need " . number_format($neededBase, 2) . " {$rmUnit}, have " . number_format($available, 2) . " {$rmUnit}";
            }
        }

        if (!empty($shortages)) {
            return back()->withErrors([
                'quantity' => 'Insufficient raw materials: ' . implode('; ', $shortages),
            ])->withInput();
        }

        try {
            DB::beginTransaction();

            // 1. Calculate raw material total cost using Weighted Average Cost (WAC)
            $totalMaterialCost = 0.0;
            $materialAllocations = [];

            foreach ($product->recipeItems as $ri) {
                $totalRecipeQty = $ri->quantity * $batchInputSize;
                $totalBaseQty   = $ri->baseQuantityNeeded($batchInputSize);
                $rmUnit         = $ri->rawMaterial->unit ?: 'pcs';
                $recipeUnit     = $ri->unit ?: $rmUnit;

                // Determine unit cost (prefer raw material WAC, then cost_price, then price)
                $wac = $ri->rawMaterial->weightedAverageCost();
                $matUnitCost = $wac > 0 ? $wac : (float)($ri->rawMaterial->cost_price ?: $ri->rawMaterial->price ?: 0);
                $matLineCost = round($totalBaseQty * $matUnitCost, 2);
                $totalMaterialCost += $matLineCost;

                $materialAllocations[] = [
                    'ri'              => $ri,
                    'totalRecipeQty'  => $totalRecipeQty,
                    'totalBaseQty'    => $totalBaseQty,
                    'recipeUnit'      => $recipeUnit,
                    'matUnitCost'     => $matUnitCost,
                    'matLineCost'     => $matLineCost,
                ];
            }

            // Unit cost per actual produced unit (scrapped units absorb into finished goods unit cost)
            $unitCost = $qtyActual > 0 ? round($totalMaterialCost / $qtyActual, 2) : 0.0;

            // 2. Create Production Record
            $production = Production::create([
                'product_id'        => $product->id,
                'batch_number'      => $batchNumber,
                'mfg_date'          => $mfgDate,
                'expiry_date'       => $expiryDate,
                'quantity'          => $qtyActual,
                'expected_quantity' => $expectedQty,
                'waste_quantity'    => $wasteQty,
                'waste_reason'      => $request->input('waste_reason'),
                'yield_percentage'  => $yieldPercentage,
                'unit_cost'         => $unitCost,
                'total_cost'        => $totalMaterialCost,
                'status'            => 'completed',
                'notes'             => $request->input('notes'),
                'user_id'           => Auth::id(),
                'produced_at'       => $request->input('produced_at') ?? now(),
            ]);

            // 3. Deduct raw materials and record stock movements
            foreach ($materialAllocations as $alloc) {
                $ri = $alloc['ri'];

                ProductionMaterial::create([
                    'production_id'      => $production->id,
                    'raw_material_id'    => $ri->raw_material_id,
                    'quantity_per_unit'  => $ri->quantity,
                    'quantity_used'      => $alloc['totalRecipeQty'],
                    'unit'               => $alloc['recipeUnit'],
                    'base_quantity_used' => $alloc['totalBaseQty'],
                ]);

                // Stock OUT for raw material (in base stock unit)
                StockMovement::create([
                    'product_id'   => $ri->raw_material_id,
                    'batch_number' => $batchNumber,
                    'type'         => 'out',
                    'quantity'     => $alloc['totalBaseQty'],
                    'unit_cost'    => $alloc['matUnitCost'],
                    'total_cost'   => $alloc['matLineCost'],
                    'source_type'  => Production::class,
                    'source_id'    => $production->id,
                    'user_id'      => Auth::id(),
                ]);

                // Decrement raw material stock
                $ri->rawMaterial->decrement('stock', $alloc['totalBaseQty']);
            }

            // 4. Stock IN for finished product with Batch Lot & Expiry Stamp
            StockMovement::create([
                'product_id'   => $product->id,
                'batch_number' => $batchNumber,
                'expiry_date'  => $expiryDate,
                'type'         => 'in',
                'quantity'     => $qtyActual,
                'unit_cost'    => $unitCost,
                'total_cost'   => $totalMaterialCost,
                'source_type'  => Production::class,
                'source_id'    => $production->id,
                'user_id'      => Auth::id(),
            ]);

            // Update finished product stock & refresh cost_price
            $product->increment('stock', $qtyActual);
            $product->cost_price = $unitCost;
            $product->save();

            DB::commit();

            Log::info('Production run completed with batch tracking', [
                'production_id' => $production->id,
                'batch_number'  => $batchNumber,
                'yield_pct'     => $yieldPercentage,
                'waste_qty'     => $wasteQty,
                'unit_cost'     => $unitCost,
            ]);

            $wasteNotice = $wasteQty > 0 ? " ({$wasteQty} units scrap recorded: {$yieldPercentage}% yield)" : "";

            return redirect()
                ->route('productions.show', $production)
                ->with('success', "Batch {$batchNumber} recorded: {$qtyActual} units of {$product->name} produced{$wasteNotice}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Production failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to record production: ' . $e->getMessage()])->withInput();
        }
    }

    /** Show a single production run */
    public function show(Production $production)
    {
        $production->load([
            'product.category',
            'user',
            'materials.rawMaterial',
            'stockMovements',
            'saleItems.sale.customer'
        ]);

        // Compute scrap and yield metrics
        $yieldBadge   = $production->yieldStatusBadge();
        $expiryBadge  = $production->expiryStatusBadge();
        $scrapPct     = $production->scrapPercentage();
        $totalScrapCost = $production->totalScrapCost();

        return view('productions.show', compact(
            'production',
            'yieldBadge',
            'expiryBadge',
            'scrapPct',
            'totalScrapCost'
        ));
    }

    /** Delete a production run (reverses stock movements) */
    public function destroy(Production $production)
    {
        try {
            DB::beginTransaction();

            // Reverse stock movements
            StockMovement::where('source_type', Production::class)
                ->where('source_id', $production->id)
                ->each(function ($movement) {
                    if ($movement->type === 'out') {
                        // Restore raw material stock
                        Product::where('id', $movement->product_id)->increment('stock', $movement->quantity);
                    } else {
                        // Remove finished product stock
                        Product::where('id', $movement->product_id)->decrement('stock', $movement->quantity);
                    }
                    $movement->delete();
                });

            $productName = $production->product->name ?? 'Unknown';
            $batchNumber = $production->batch_number ?? "#{$production->id}";
            $production->delete();

            DB::commit();
            Log::info('Production deleted & reversed', ['production_id' => $production->id, 'batch' => $batchNumber]);

            return redirect()
                ->route('productions.index')
                ->with('success', "Production batch {$batchNumber} ({$productName}) was reversed and deleted.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Production delete failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete production: ' . $e->getMessage()]);
        }
    }

    /** Bulk delete and reverse multiple production runs */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:productions,id'],
        ]);

        $ids = $request->input('ids');

        try {
            DB::beginTransaction();

            $runs = Production::whereIn('id', $ids)->get();
            $count = $runs->count();

            foreach ($runs as $production) {
                // Reverse stock movements
                StockMovement::where('source_type', Production::class)
                    ->where('source_id', $production->id)
                    ->each(function ($movement) {
                        if ($movement->type === 'out') {
                            Product::where('id', $movement->product_id)->increment('stock', $movement->quantity);
                        } else {
                            Product::where('id', $movement->product_id)->decrement('stock', $movement->quantity);
                        }
                        $movement->delete();
                    });

                $production->delete();
            }

            DB::commit();
            Log::info('Productions bulk reversed & deleted', ['count' => $count, 'ids' => $ids]);

            return redirect()
                ->route('productions.index')
                ->with('success', "{$count} production batch run(s) reversed and deleted.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Productions bulk delete failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to reverse production runs: ' . $e->getMessage()]);
        }
    }
}
