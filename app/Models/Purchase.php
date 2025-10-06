<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'invoice_number',
        'purchase_date',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'notes',
    ];

    /* ─────────── Relationships ─────────── */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    /* ─────────── Helper Methods ─────────── */

    public function updateTotals()
    {
        $subtotal = $this->items->sum('total_cost');
        $this->subtotal = $subtotal;
        $this->total_amount = ($subtotal + $this->tax) - $this->discount;
        $this->balance_due = $this->total_amount - $this->amount_paid;
        $this->save();
    }
}
