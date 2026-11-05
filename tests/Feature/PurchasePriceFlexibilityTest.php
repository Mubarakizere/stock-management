<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchasePriceFlexibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_updates_catalog_cost_and_syncs_finished_goods(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('purchases.create');
        Permission::findOrCreate('purchases.view');
        $user->givePermissionTo(['purchases.create', 'purchases.view']);
        $this->actingAs($user);

        $supplier = Supplier::create([
            'name'  => 'Premier Supplies Ltd',
            'phone' => '+250788000000',
        ]);

        $rawCat = Category::create([
            'name' => 'Grains & Baking',
            'slug' => 'grains-baking',
            'kind' => 'product',
        ]);

        $prodCat = Category::create([
            'name' => 'Bakery Items',
            'slug' => 'bakery-items',
            'kind' => 'product',
        ]);

        // Raw Material initially at RWF 1000/kg
        $sugar = Product::create([
            'category_id'         => $rawCat->id,
            'name'                => 'Refined Sugar',
            'type'                => 'raw_material',
            'unit'                => 'kg',
            'price'               => 1000.00,
            'cost_price'          => 1000.00,
            'last_purchase_price' => 1000.00,
            'stock'               => 5.0,
        ]);

        // Finished Good: Cake (needs 0.2 kg sugar per piece, 25% target margin)
        $cake = Product::create([
            'category_id'   => $prodCat->id,
            'name'          => 'Vanilla Cake Slice',
            'type'          => 'product',
            'unit'          => 'pcs',
            'price'         => 300.00,
            'cost_price'    => 200.00,
            'target_margin' => 25.00,
            'stock'         => 0,
        ]);

        ProductRecipe::create([
            'product_id'        => $cake->id,
            'raw_material_id'   => $sugar->id,
            'quantity'          => 200,
            'unit'              => 'g',
            'conversion_factor' => 0.001,
        ]);

        // Initial recipe cost: 200g (0.2kg) * 1000 = 200 RWF
        $this->assertEquals(200.00, $cake->estimatedRecipeCost());

        // Now supplier price increases to RWF 1500/kg on purchase
        $response = $this->post(route('purchases.store'), [
            'supplier_id'     => $supplier->id,
            'purchase_date'   => now()->toDateString(),
            'payment_channel' => 'cash',
            'amount_paid'     => 15000,
            'products'        => [
                [
                    'product_id'           => $sugar->id,
                    'quantity'             => 10,
                    'unit_cost'            => 1500.00,
                    'update_catalog_cost'  => '1',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        // 1. Check sugar updated
        $sugar->refresh();
        $this->assertEquals(1500.00, (float)$sugar->cost_price);
        $this->assertEquals(1500.00, (float)$sugar->last_purchase_price);

        // 2. Check finished product automatically recalculated: 0.2kg * 1500 = 300 RWF
        $cake->refresh();
        $this->assertEquals(300.00, (float)$cake->cost_price);

        // 3. With 25% target margin, selling price: 300 * 1.25 = 375 RWF
        $this->assertEquals(375.00, (float)$cake->price);
    }

    public function test_purchase_can_directly_update_selling_price_with_custom_value(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('purchases.create');
        Permission::findOrCreate('purchases.view');
        $user->givePermissionTo(['purchases.create', 'purchases.view']);
        $this->actingAs($user);

        $supplier = Supplier::create(['name' => 'Wholesale Depot']);
        $cat = Category::create(['name' => 'General', 'kind' => 'product']);

        $product = Product::create([
            'category_id'         => $cat->id,
            'name'                => 'Sunflower Oil 1L',
            'type'                => 'product',
            'unit'                => 'bottle',
            'price'               => 2000.00,
            'cost_price'          => 1600.00,
            'last_purchase_price' => 1600.00,
            'stock'               => 2.0,
        ]);

        // Purchase at 1900 with direct selling price adjustment to 2500
        $this->post(route('purchases.store'), [
            'supplier_id'     => $supplier->id,
            'purchase_date'   => now()->toDateString(),
            'payment_channel' => 'cash',
            'amount_paid'     => 19000,
            'products'        => [
                [
                    'product_id'           => $product->id,
                    'quantity'             => 10,
                    'unit_cost'            => 1900.00,
                    'update_catalog_cost'  => '1',
                    'update_selling_price' => '1',
                    'new_selling_price'    => 2500.00,
                ],
            ],
        ]);

        $product->refresh();
        $this->assertEquals(1900.00, (float)$product->cost_price);
        $this->assertEquals(1900.00, (float)$product->last_purchase_price);
        $this->assertEquals(2500.00, (float)$product->price);
        $this->assertEquals(24.0, $product->profitMarginPercentage());
    }

    public function test_product_controller_stores_cost_price_and_target_margin(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('products.create');
        Permission::findOrCreate('products.view');
        Permission::findOrCreate('products.edit');
        $user->givePermissionTo(['products.create', 'products.view', 'products.edit']);
        $this->actingAs($user);

        $cat = Category::create(['name' => 'Dairy', 'kind' => 'product']);

        $res = $this->post(route('products.store'), [
            'name'          => 'Pasteurized Milk 500ml',
            'category_id'   => $cat->id,
            'cost_price'    => 400.00,
            'target_margin' => 25.00,
            'price'         => 500.00,
            'stock'         => 50,
            'unit'          => 'pack',
        ]);

        $res->assertRedirect(route('products.index'));

        $product = Product::where('name', 'Pasteurized Milk 500ml')->first();
        $this->assertNotNull($product);
        $this->assertEquals(400.00, (float)$product->cost_price);
        $this->assertEquals(25.00, (float)$product->target_margin);
        $this->assertEquals(500.00, (float)$product->price);
        $this->assertEquals(20.0, $product->profitMarginPercentage());
    }
}

