<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add the type column (PostgreSQL-compatible)
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->default('product')->after('category_id');
        });

        // Add a CHECK constraint for allowed values
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_type_check CHECK (type IN ('product','raw_material'))");

        // Backfill: tag products whose category is raw_material
        DB::statement("
            UPDATE products p
            SET type = 'raw_material'
            FROM categories c
            WHERE p.category_id = c.id
              AND c.kind = 'raw_material'
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS products_type_check");

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
