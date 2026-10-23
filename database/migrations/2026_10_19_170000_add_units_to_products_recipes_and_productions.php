<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add unit to products table (covers both finished products & raw materials)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit', 50)->default('pcs')->after('stock');
            }
        });

        // 2. Add unit and conversion_factor to product_recipes table
        Schema::table('product_recipes', function (Blueprint $table) {
            if (!Schema::hasColumn('product_recipes', 'unit')) {
                $table->string('unit', 50)->default('pcs')->after('quantity');
            }
            if (!Schema::hasColumn('product_recipes', 'conversion_factor')) {
                $table->decimal('conversion_factor', 12, 6)->default(1.000000)->after('unit')
                      ->comment('Multiplier to convert 1 recipe unit into raw material base unit');
            }
        });

        // 3. Add unit and base_quantity_used to production_materials table
        Schema::table('production_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('production_materials', 'unit')) {
                $table->string('unit', 50)->nullable()->after('quantity_per_unit');
            }
            if (!Schema::hasColumn('production_materials', 'base_quantity_used')) {
                $table->decimal('base_quantity_used', 12, 4)->nullable()->after('quantity_used')
                      ->comment('Actual quantity consumed in raw material base stock unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_materials', function (Blueprint $table) {
            if (Schema::hasColumn('production_materials', 'base_quantity_used')) {
                $table->dropColumn('base_quantity_used');
            }
            if (Schema::hasColumn('production_materials', 'unit')) {
                $table->dropColumn('unit');
            }
        });

        Schema::table('product_recipes', function (Blueprint $table) {
            if (Schema::hasColumn('product_recipes', 'conversion_factor')) {
                $table->dropColumn('conversion_factor');
            }
            if (Schema::hasColumn('product_recipes', 'unit')) {
                $table->dropColumn('unit');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
