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
    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null'); // nullable for walk-ins
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // who recorded the sale
        $table->date('sale_date');
        $table->decimal('total_amount', 12, 2);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
