<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    /** Show recipe management page for a finished product */
    public function edit(Product $product)
    {
        $product->load(['recipeItems.rawMaterial', 'category']);

        // Get all available raw materials for the dropdown
        $rawMaterials = Product::rawMaterials()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'cost_price']);

        return view('recipes.edit', compact('product', 'rawMaterials'));
    }

    /** Save / update the recipe for a product */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'ingredients'                => ['nullable', 'array'],
            'ingredients.*.raw_material_id' => ['required', 'integer', 'exists:products,id'],
            'ingredients.*.quantity'        => ['required', 'numeric', 'min:0.01'],
        ], [
            'ingredients.*.raw_material_id.exists' => 'One of the selected raw materials does not exist.',
            'ingredients.*.quantity.min'            => 'Quantity must be at least 0.01.',
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

            // Delete existing recipe items
            $product->recipeItems()->delete();

            // Insert new recipe items
            foreach ($ingredients as $item) {
                ProductRecipe::create([
                    'product_id'      => $product->id,
                    'raw_material_id' => (int) $item['raw_material_id'],
                    'quantity'        => (float) $item['quantity'],
                ]);
            }

            DB::commit();
            Log::info('Recipe updated', ['product_id' => $product->id, 'ingredient_count' => count($ingredients)]);

            return redirect()
                ->route('products.show', $product)
                ->with('success', 'Recipe saved successfully — ' . count($ingredients) . ' ingredient(s).');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Recipe update failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to save recipe: ' . $e->getMessage()])->withInput();
        }
    }
}
