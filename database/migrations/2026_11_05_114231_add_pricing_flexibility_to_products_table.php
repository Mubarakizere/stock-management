<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'last_purchase_price')) {
                $table->decimal('last_purchase_price', 15, 2)->nullable()->after('cost_price')
                    ->comment('Most recent purchase unit cost for replacement cost tracking');
            }
            if (!Schema::hasColumn('products', 'target_margin')) {
                $table->decimal('target_margin', 5, 2)->default(0)->after('price')
                    ->comment('Target profit margin percentage for selling prices');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'target_margin')) {
                $table->dropColumn('target_margin');
            }
            if (Schema::hasColumn('products', 'last_purchase_price')) {
                $table->dropColumn('last_purchase_price');
            }
        });
    }
};
