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
    Schema::create('purchases', function (Blueprint $table) {
        $table->id();
        $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // who recorded the purchase
        $table->date('purchase_date');
        $table->decimal('total_amount', 12, 2);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
