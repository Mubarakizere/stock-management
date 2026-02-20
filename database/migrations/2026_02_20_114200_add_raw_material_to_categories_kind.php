<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE categories MODIFY COLUMN kind ENUM('product','expense','both','raw_material') NOT NULL DEFAULT 'both'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE categories MODIFY COLUMN kind ENUM('product','expense','both') NOT NULL DEFAULT 'both'");
    }
};
