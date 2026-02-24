<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // On PostgreSQL: drop old constraint and add a new one that includes 'raw_material'.
        // On MySQL/MariaDB: CHECK constraints are managed differently; skip to avoid errors.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_kind_check");
            DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_kind_check CHECK (kind IN ('product','expense','both','raw_material'))");
        }
    }

    public function down(): void
    {
        // On PostgreSQL: restore the old constraint.
        // Note: this will fail if any rows still have kind='raw_material' — handle data first.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_kind_check");
            DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_kind_check CHECK (kind IN ('product','expense','both'))");
        }
    }
};
