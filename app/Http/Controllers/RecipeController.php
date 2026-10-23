<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Services\UnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    /** Show recipe management page for a finished product */
    public function edit(Product $product)
    {
        $product->load(['recipeItems.rawMaterial', 'category']);

        // Get all available raw materials for the dropdown (including their base unit)
        $rawMaterials = Product::rawMaterials()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'cost_price', 'unit']);

        $unitFamilies = UnitService::getGrouped();

        return view('recipes.edit', compact('product', 'rawMaterials', 'unitFamilies'));
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

            // Fetch referenced raw materials to know their base stock units
            $rawMaterialsMap = Product::whereIn('id', $materialIds)
                ->get(['id', 'unit', 'price'])
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

            DB::commit();
            Log::info('Recipe updated with units', [
                'product_id'       => $product->id,
                'ingredient_count' => count($ingredients)
            ]);

            return redirect()
                ->route('products.show', $product)
                ->with('success', 'Recipe saved successfully — ' . count($ingredients) . ' ingredient(s) configured.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Recipe update failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to save recipe: ' . $e->getMessage()])->withInput();
        }
    }
}
