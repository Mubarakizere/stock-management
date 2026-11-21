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
        // 1. Productions table: Batch Lot & Yield Variance
        Schema::table('productions', function (Blueprint $table) {
            if (!Schema::hasColumn('productions', 'batch_number')) {
                $table->string('batch_number', 60)->nullable()->after('product_id')->index();
            }
            if (!Schema::hasColumn('productions', 'mfg_date')) {
                $table->date('mfg_date')->nullable()->after('batch_number');
            }
            if (!Schema::hasColumn('productions', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('mfg_date')->index();
            }
            if (!Schema::hasColumn('productions', 'expected_quantity')) {
                $table->decimal('expected_quantity', 10, 2)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('productions', 'waste_quantity')) {
                $table->decimal('waste_quantity', 10, 2)->default(0)->after('expected_quantity');
            }
            if (!Schema::hasColumn('productions', 'waste_reason')) {
                $table->string('waste_reason', 255)->nullable()->after('waste_quantity');
            }
            if (!Schema::hasColumn('productions', 'yield_percentage')) {
                $table->decimal('yield_percentage', 6, 2)->nullable()->after('waste_reason');
            }
            if (!Schema::hasColumn('productions', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->nullable()->after('yield_percentage');
            }
            if (!Schema::hasColumn('productions', 'total_cost')) {
                $table->decimal('total_cost', 14, 2)->nullable()->after('unit_cost');
            }
        });

        // 2. Stock Movements table: Batch Lot & Expiration
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'batch_number')) {
                $table->string('batch_number', 60)->nullable()->after('product_id')->index();
            }
            if (!Schema::hasColumn('stock_movements', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('batch_number');
            }
        });

        // 3. Sale Items table: Batch Lot allocation
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'batch_number')) {
                $table->string('batch_number', 60)->nullable()->after('product_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $cols = [
                'batch_number', 'mfg_date', 'expiry_date',
                'expected_quantity', 'waste_quantity', 'waste_reason',
                'yield_percentage', 'unit_cost', 'total_cost'
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('productions', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $cols = ['batch_number', 'expiry_date'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('stock_movements', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
        });
    }
};
