<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete()
                  ->comment('The finished product');
            $table->foreignId('raw_material_id')
                  ->constrained('products')
                  ->cascadeOnDelete()
                  ->comment('The raw material consumed');
            $table->decimal('quantity', 10, 2)
                  ->comment('Quantity of raw material needed to produce 1 unit');
            $table->timestamps();

            $table->unique(['product_id', 'raw_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
