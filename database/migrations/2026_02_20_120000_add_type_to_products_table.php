<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add the type column with a safe default
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->default('product')->after('category_id');
        });

        // CHECK constraint — only PostgreSQL supports it reliably here;
        // MySQL 8.0.16+ / MariaDB 10.2.1+ do support it but older versions
        // silently ignore it, so we add it only for pgsql to avoid syntax errors.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE products ADD CONSTRAINT products_type_check CHECK (type IN ('product','raw_material'))");
        }

        // Backfill: tag products whose category kind = 'raw_material'
        // MySQL/MariaDB uses UPDATE...JOIN; PostgreSQL uses UPDATE...FROM.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE products p
                SET type = 'raw_material'
                FROM categories c
                WHERE p.category_id = c.id
                  AND c.kind = 'raw_material'
            ");
        } else {
            // MySQL / MariaDB / SQLite compatible syntax
            DB::statement("
                UPDATE products p
                JOIN categories c ON p.category_id = c.id
                SET p.type = 'raw_material'
                WHERE c.kind = 'raw_material'
            ");
        }
    }

    public function down(): void
    {
        // Drop the CHECK constraint only on PostgreSQL (it was only added there)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS products_type_check");
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
