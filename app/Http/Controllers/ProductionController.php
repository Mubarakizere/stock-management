<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionMaterial;
use App\Models\StockMovement;
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

        // Search by product name or notes
        if ($s = trim((string) $request->input('q'))) {
            $op = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $pattern = "%{$s}%";
            $query->where(function ($w) use ($op, $pattern) {
                $w->whereHas('product', fn($q) => $q->where('name', $op, $pattern))
                  ->orWhere('notes', $op, $pattern);
            });
        }

        $productions = $query->paginate($perPage)->appends($request->query());

        // Products for filter dropdown
        $products = Product::products()->orderBy('name')->get(['id', 'name']);

        return view('productions.index', compact('productions', 'products'));
    }

    /** Show the create production form */
    public function create(Request $request)
    {
        // All finished products (with their recipes if defined)
        $products = Product::products()
            ->with(['recipeItems.rawMaterial'])
            ->orderBy('name')
            ->get();

        // Pre-select product if passed via query string
        $selectedProductId = $request->integer('product_id');

        return view('productions.create', compact('products', 'selectedProductId'));
    }

    /** Store a new production run */
    public function store(Request $request)
    {
        $request->validate([
            'product_id'  => ['required', 'integer', 'exists:products,id'],
            'quantity'    => ['required', 'numeric', 'min:0.01'],
            'produced_at' => ['nullable', 'date'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::with('recipeItems.rawMaterial')->findOrFail($request->integer('product_id'));

        // Verify product has a recipe
        if ($product->recipeItems->isEmpty()) {
            return back()->withErrors(['product_id' => 'This product has no recipe defined. Please set up a recipe first.'])->withInput();
        }

        $qty = (float) $request->input('quantity');

        // Check raw material availability
        $shortages = [];
        foreach ($product->recipeItems as $ri) {
            $needed   = $ri->quantity * $qty;
            $available = $ri->rawMaterial->currentStock();
            if ($available < $needed) {
                $shortages[] = "{$ri->rawMaterial->name}: need " . number_format($needed, 2) . ", have " . number_format($available, 2);
            }
        }

        if (!empty($shortages)) {
            return back()->withErrors([
                'quantity' => 'Insufficient raw materials: ' . implode('; ', $shortages),
            ])->withInput();
        }

        try {
            DB::beginTransaction();

            // Create production record
            $production = Production::create([
                'product_id'  => $product->id,
                'quantity'    => $qty,
                'status'      => 'completed',
                'notes'       => $request->input('notes'),
                'user_id'     => Auth::id(),
                'produced_at' => $request->input('produced_at') ?? now(),
            ]);

            // Deduct raw materials and record stock movements
            foreach ($product->recipeItems as $ri) {
                $totalUsed = $ri->quantity * $qty;

                ProductionMaterial::create([
                    'production_id'    => $production->id,
                    'raw_material_id'  => $ri->raw_material_id,
                    'quantity_per_unit' => $ri->quantity,
                    'quantity_used'    => $totalUsed,
                ]);

                // Stock out for raw material
                StockMovement::create([
                    'product_id'  => $ri->raw_material_id,
                    'type'        => 'out',
                    'quantity'    => $totalUsed,
                    'unit_cost'   => $ri->rawMaterial->price ?? 0,
                    'total_cost'  => ($ri->rawMaterial->price ?? 0) * $totalUsed,
                    'source_type' => Production::class,
                    'source_id'   => $production->id,
                    'user_id'     => Auth::id(),
                ]);

                // Update the raw material stock field
                $ri->rawMaterial->decrement('stock', $totalUsed);
            }

            // Stock in for finished product
            StockMovement::create([
                'product_id'  => $product->id,
                'type'        => 'in',
                'quantity'    => $qty,
                'unit_cost'   => $product->cost_price ?? $product->price ?? 0,
                'total_cost'  => ($product->cost_price ?? $product->price ?? 0) * $qty,
                'source_type' => Production::class,
                'source_id'   => $production->id,
                'user_id'     => Auth::id(),
            ]);

            // Update finished product stock
            $product->increment('stock', $qty);

            DB::commit();
            Log::info('Production recorded', [
                'production_id' => $production->id,
                'product_id'    => $product->id,
                'quantity'      => $qty,
            ]);

            return redirect()
                ->route('productions.show', $production)
                ->with('success', "Production recorded: {$qty} units of {$product->name}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Production failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to record production: ' . $e->getMessage()])->withInput();
        }
    }

    /** Show a single production run */
    public function show(Production $production)
    {
        $production->load(['product', 'user', 'materials.rawMaterial']);

        return view('productions.show', compact('production'));
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
            $production->delete();

            DB::commit();
            Log::info('Production deleted & reversed', ['production_id' => $production->id]);

            return redirect()
                ->route('productions.index')
                ->with('success', "Production of {$productName} reversed and deleted.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Production delete failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete production: ' . $e->getMessage()]);
        }
    }
}
