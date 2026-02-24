<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: only add the column if it doesn't already exist.
        // This handles servers where the migration partially ran before.
        if (!Schema::hasColumn('products', 'type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('type', 20)->default('product')->after('category_id');
            });
        }

        // CHECK constraint — only on PostgreSQL (MySQL/MariaDB versions vary in support).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE products ADD CONSTRAINT products_type_check CHECK (type IN ('product','raw_material'))");
        }

        // Backfill: tag products whose category kind = 'raw_material'.
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
            // MySQL / MariaDB / SQLite compatible
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
        // Drop CHECK constraint only on PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS products_type_check");
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
