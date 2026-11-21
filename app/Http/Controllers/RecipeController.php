<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Services\UnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    /** Show all finished products, their dynamic BOM cost, and Margin Guard status */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $op = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        $basis = $request->input('basis', 'wac'); // 'wac' or 'latest_po'

        $categories = Category::active()->forProducts()->orderBy('name')->get();

        $query = Product::products()
            ->with([
                'category' => fn($q) => $q->withTrashed(),
                'recipeItems.rawMaterial.stockMovements',
            ])
            ->withCount('recipeItems');

        if ($s = trim((string) $request->input('q'))) {
            $pattern = "%{$s}%";
            $query->where(function ($w) use ($op, $pattern, $s) {
                $w->where('name', $op, $pattern);
                if (is_numeric($s)) {
                    $w->orWhere('id', (int) $s);
                }
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($status = $request->input('status')) {
            if ($status === 'configured') {
                $query->has('recipeItems');
            } elseif ($status === 'missing') {
                $query->doesntHave('recipeItems');
            }
        }

        $allConfiguredProducts = Product::products()->has('recipeItems')->with(['recipeItems.rawMaterial.stockMovements'])->get();

        // Calculate Margin Guard status for all configured products to compute global KPIs
        $warningCount  = 0;
        $criticalCount = 0;
        $healthyCount  = 0;
        $totalMarginSum = 0;

        foreach ($allConfiguredProducts as $cp) {
            $guard = $cp->marginGuardStatus($basis);
            if ($guard['status'] === 'critical') {
                $criticalCount++;
            } elseif ($guard['status'] === 'warning') {
                $warningCount++;
            } else {
                $healthyCount++;
            }
            $totalMarginSum += $guard['margin_pct'];
        }

        $configuredCount = $allConfiguredProducts->count();
        $avgMarginPct = $configuredCount > 0 ? round($totalMarginSum / $configuredCount, 1) : 0.0;

        // Apply Margin Status Filter if requested
        if ($marginFilter = $request->input('margin_status')) {
            if ($marginFilter === 'warning') {
                $warningIds = $allConfiguredProducts->filter(fn($p) => $p->marginGuardStatus($basis)['status'] === 'warning')->pluck('id');
                $query->whereIn('id', $warningIds);
            } elseif ($marginFilter === 'critical') {
                $criticalIds = $allConfiguredProducts->filter(fn($p) => $p->marginGuardStatus($basis)['status'] === 'critical')->pluck('id');
                $query->whereIn('id', $criticalIds);
            } elseif ($marginFilter === 'healthy') {
                $healthyIds = $allConfiguredProducts->filter(fn($p) => $p->marginGuardStatus($basis)['status'] === 'healthy')->pluck('id');
                $query->whereIn('id', $healthyIds);
            } elseif ($marginFilter === 'alerts') {
                $alertIds = $allConfiguredProducts->filter(fn($p) => in_array($p->marginGuardStatus($basis)['status'], ['warning', 'critical']))->pluck('id');
                $query->whereIn('id', $alertIds);
            }
        }

        $products = $query->orderBy('name')->paginate($perPage)->appends($request->query());

        // Global KPI Stats
        $allProductsCount = Product::products()->count();
        $missingCount     = max(0, $allProductsCount - $configuredCount);
        $totalIngredients = ProductRecipe::count();
        $avgIngredients   = $configuredCount > 0 ? round($totalIngredients / $configuredCount, 1) : 0;

        $stats = [
            'total_products'   => $allProductsCount,
            'configured_count' => $configuredCount,
            'missing_count'    => $missingCount,
            'avg_ingredients'  => $avgIngredients,
            'coverage_pct'     => $allProductsCount > 0 ? round(($configuredCount / $allProductsCount) * 100, 1) : 0,
            'healthy_count'    => $healthyCount,
            'warning_count'    => $warningCount,
            'critical_count'   => $criticalCount,
            'margin_alerts'    => ($warningCount + $criticalCount),
            'avg_margin_pct'   => $avgMarginPct,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'html'       => view('recipes._table', compact('products', 'basis'))->render(),
                'pagination' => $products->links()->toHtml(),
                'stats'      => $stats
            ]);
        }

        return view('recipes.index', compact('products', 'categories', 'stats', 'basis'));
    }

    /** Show recipe management page for a finished product */
    public function edit(Product $product)
    {
        $product->load(['recipeItems.rawMaterial.stockMovements', 'category']);

        // Get all available raw materials for the dropdown (including their base unit and costs)
        $rawMaterials = Product::rawMaterials()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'cost_price', 'last_purchase_price', 'unit']);

        $unitFamilies = UnitService::getGrouped();

        // Calculate dynamic BOM Cost & Margin Guard preview
        $bomWac = $product->bomCost('wac');
        $bomPo  = $product->bomCost('latest_po');
        $marginGuard = $product->marginGuardStatus('wac');
        $bomBreakdown = $product->bomCostBreakdown('wac');

        return view('recipes.edit', compact(
            'product',
            'rawMaterials',
            'unitFamilies',
            'bomWac',
            'bomPo',
            'marginGuard',
            'bomBreakdown'
        ));
    }

    /** Save / update the recipe for a product */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'ingredients'                     => ['nullable', 'array'],
            'ingredients.*.raw_material_id'   => ['required', 'integer', 'exists:products,id'],
            'ingredients.*.quantity'          => ['required', 'numeric', 'min:0.0001'],
            'ingredients.*.unit'              => ['nullable', 'string', 'max:50'],
            'ingredients.*.conversion_factor' => ['nullable', 'numeric', 'min:0.000001'],
            'target_margin'                   => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'auto_adjust_price'               => ['nullable', 'boolean'],
        ], [
            'ingredients.*.raw_material_id.exists' => 'One of the selected raw materials does not exist.',
            'ingredients.*.quantity.min'           => 'Quantity must be greater than 0.',
        ]);

        $ingredients = $request->input('ingredients', []);

        // Check for duplicate raw materials
        $materialIds = array_column($ingredients, 'raw_material_id');
        if (count($materialIds) !== count(array_unique($materialIds))) {
            return back()->withErrors(['ingredients' => 'Duplicate raw materials are not allowed in a recipe.'])->withInput();
        }

        // Ensure no ingredient references the product itself
        foreach ($materialIds as $mid) {
            if ((int) $mid === $product->id) {
                return back()->withErrors(['ingredients' => 'A product cannot be an ingredient of itself.'])->withInput();
            }
        }

        try {
            DB::beginTransaction();

            if ($request->filled('target_margin')) {
                $product->target_margin = (float) $request->input('target_margin');
            }

            // Fetch referenced raw materials to know their base stock units
            $rawMaterialsMap = Product::whereIn('id', $materialIds)
                ->get(['id', 'unit', 'price', 'cost_price', 'last_purchase_price'])
                ->keyBy('id');

            // Delete existing recipe items
            $product->recipeItems()->delete();

            // Insert new recipe items with unit and calculated conversion factor
            foreach ($ingredients as $item) {
                $rawMatId = (int) $item['raw_material_id'];
                $rm = $rawMaterialsMap->get($rawMatId);
                $baseUnit = $rm ? ($rm->unit ?: 'pcs') : 'pcs';

                $recipeUnit = !empty($item['unit']) ? strtolower(trim($item['unit'])) : $baseUnit;

                // Determine conversion factor
                if (!empty($item['conversion_factor']) && (float) $item['conversion_factor'] > 0) {
                    $factor = (float) $item['conversion_factor'];
                } else {
                    $factor = UnitService::getConversionFactor($recipeUnit, $baseUnit);
                }

                ProductRecipe::create([
                    'product_id'        => $product->id,
                    'raw_material_id'   => $rawMatId,
                    'quantity'          => (float) $item['quantity'],
                    'unit'              => $recipeUnit,
                    'conversion_factor' => $factor,
                ]);
            }

            // Recalculate finished good cost_price and Margin Guard
            $product->load('recipeItems.rawMaterial.stockMovements');
            $recCost = $product->bomCost('wac');
            $product->cost_price = $recCost;

            if ($request->boolean('auto_adjust_price') && (float)($product->target_margin ?? 0) > 0) {
                $targetMargin = (float)$product->target_margin;
                $product->price = round($recCost / (1 - ($targetMargin / 100)), 2);
            }
            $product->save();

            DB::commit();
            Log::info('Recipe updated with dynamic BOM costing', [
                'product_id'       => $product->id,
                'ingredient_count' => count($ingredients),
                'new_bom_cost'     => $recCost,
            ]);

            return redirect()
                ->route('products.show', $product)
                ->with('success', 'Recipe saved successfully — BOM unit cost calculated at ' . number_format($recCost, 2) . ' RWF.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Recipe update failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to save recipe: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * 1-Click Sync BOM Cost to Product Cost Price and optionally re-align selling price
     */
    public function syncBomCost(Request $request, Product $product)
    {
        $adjustPrice = $request->boolean('adjust_price');
        $basis = $request->input('basis', 'wac');

        $result = $product->syncBomCostToCostPrice($adjustPrice, $basis);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $result['synced'],
                'data'    => $result,
                'guard'   => $product->marginGuardStatus($basis),
                'message' => $result['synced'] 
                    ? "BOM cost synchronized to {$product->name} (Cost: " . number_format($result['new_cost'], 2) . " RWF" . ($result['price_changed'] ? ", Price: " . number_format($result['new_price'], 2) . " RWF" : "") . ")"
                    : "Failed to synchronize: {$result['message']}",
            ]);
        }

        if ($result['synced']) {
            $msg = "BOM cost synchronized for {$product->name}: Cost updated to " . number_format($result['new_cost'], 2) . " RWF.";
            if ($result['price_changed']) {
                $msg .= " Selling price adjusted to " . number_format($result['new_price'], 2) . " RWF to maintain target margin.";
            }
            return back()->with('success', $msg);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Recalculate dynamic BOM costs across all finished products
     */
    public function recalculateAllBom(Request $request)
    {
        $basis = $request->input('basis', 'wac');
        $adjustPrice = $request->boolean('adjust_price');

        $products = Product::products()->has('recipeItems')->get();
        $updatedCount = 0;

        foreach ($products as $p) {
            $res = $p->syncBomCostToCostPrice($adjustPrice, $basis);
            if ($res['synced']) {
                $updatedCount++;
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'       => true,
                'updated_count' => $updatedCount,
                'message'       => "Successfully recalculated BOM costs for {$updatedCount} product recipes.",
            ]);
        }

        return back()->with('success', "Recalculated BOM costs for {$updatedCount} product recipes based on " . strtoupper($basis) . ".");
    }

    /** Clear / delete recipe for a product */
    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            $count = $product->recipeItems()->count();
            $product->recipeItems()->delete();

            DB::commit();
            Log::info('Recipe cleared', ['product_id' => $product->id, 'deleted_count' => $count]);

            return back()->with('success', "Recipe cleared: {$count} ingredient(s) detached from {$product->name}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Recipe delete failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to clear recipe: ' . $e->getMessage()]);
        }
    }
}
