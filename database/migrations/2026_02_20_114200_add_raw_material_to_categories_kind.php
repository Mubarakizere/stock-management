<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: kind is a varchar column, we just need to ensure
        // the CHECK constraint (if any) allows 'raw_material'.
        // Drop old check constraint if it exists; add new one.
        DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_kind_check");
        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_kind_check CHECK (kind IN ('product','expense','both','raw_material'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_kind_check");
        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_kind_check CHECK (kind IN ('product','expense','both'))");
    }
};
