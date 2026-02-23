<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->comment('The finished product being produced');
            $table->decimal('quantity', 10, 2)
                  ->comment('Number of finished units produced');
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who recorded the production');
            $table->timestamp('produced_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('production_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')
                  ->constrained('productions')
                  ->cascadeOnDelete();
            $table->foreignId('raw_material_id')
                  ->constrained('products')
                  ->comment('The raw material consumed');
            $table->decimal('quantity_per_unit', 10, 2)
                  ->comment('Recipe quantity per finished unit');
            $table->decimal('quantity_used', 10, 2)
                  ->comment('Total quantity consumed = quantity_per_unit * production qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_materials');
        Schema::dropIfExists('productions');
    }
};
