<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Production;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductionUnitFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_run_deducts_converted_stock_and_reversal_restores_it(): void
    {
        // 1. Create user with required permissions
        $user = User::factory()->create();
        Permission::findOrCreate('products.edit');
        Permission::findOrCreate('products.view');
        $user->givePermissionTo(['products.edit', 'products.view']);
        $this->actingAs($user);

        // Categories
        $rawCat = Category::create([
            'name' => 'Raw Materials Cat',
            'slug' => 'raw-materials-cat',
            'kind' => 'product',
        ]);

        $prodCat = Category::create([
            'name' => 'Finished Goods Cat',
            'slug' => 'finished-goods-cat',
            'kind' => 'product',
        ]);

        // 2. Create raw material: Flour in 'kg' with initial stock of 10.0 kg
        $rawMaterial = Product::create([
            'category_id' => $rawCat->id,
            'name'        => 'Flour',
            'type'        => 'raw_material',
            'unit'        => 'kg',
            'price'       => 2.50,
            'cost_price'  => 2.00,
            'stock'       => 10.0,
        ]);

        StockMovement::create([
            'product_id'  => $rawMaterial->id,
            'user_id'     => $user->id,
            'quantity'    => 10.0,
            'type'        => 'in',
            'unit_cost'   => 2.00,
            'total_cost'  => 20.00,
            'source_type' => Product::class,
            'source_id'   => $rawMaterial->id,
        ]);

        $this->assertEquals(10.0, $rawMaterial->currentStock());

        // 3. Create finished product: Bread Loaf in 'pcs'
        $product = Product::create([
            'category_id' => $prodCat->id,
            'name'        => 'Artisan Bread',
            'type'        => 'product',
            'unit'        => 'pcs',
            'price'       => 4.00,
            'cost_price'  => 1.50,
            'stock'       => 0.0,
        ]);

        // 4. Create recipe: 500 g of Flour per 1 Bread Loaf (conversion_factor = 0.001)
        ProductRecipe::create([
            'product_id'        => $product->id,
            'raw_material_id'   => $rawMaterial->id,
            'quantity'          => 500,
            'unit'              => 'g',
            'conversion_factor' => 0.001,
        ]);

        // 5. Submit production run: produce 6 loaves of bread
        // Expected flour needed = 6 * 500g = 3000g = 3.0 kg
        $response = $this->post(route('productions.store'), [
            'product_id'  => $product->id,
            'quantity'    => 6,
            'produced_at' => now()->format('Y-m-d\TH:i'),
            'notes'       => 'Morning bake',
        ]);

        $production = Production::first();
        $this->assertNotNull($production);
        $this->assertEquals(6.0, (float)$production->quantity);

        $response->assertRedirect(route('productions.show', $production));
        $response->assertSessionHas('success');

        // Check product stock: should have 6 pcs
        $this->assertEquals(6.0, $product->currentStock());

        // Check raw material stock: 10 - 3.0 = 7.0 kg
        $this->assertEquals(7.0, $rawMaterial->currentStock());

        $mat = $production->materials()->where('raw_material_id', $rawMaterial->id)->first();
        $this->assertNotNull($mat);
        $this->assertEquals(3000.0, (float)$mat->quantity_used);
        $this->assertEquals('g', $mat->unit);
        $this->assertEquals(3.0, (float)$mat->base_quantity_used);

        // 6. Reverse the batch
        $reverseResponse = $this->delete(route('productions.destroy', $production));

        $reverseResponse->assertRedirect(route('productions.index'));
        $reverseResponse->assertSessionHas('success');

        // Check stocks after reversal: product back to 0, raw material back to 10.0 kg
        $this->assertEquals(0.0, $product->fresh()->currentStock());
        $this->assertEquals(10.0, $rawMaterial->fresh()->currentStock());
    }
}
