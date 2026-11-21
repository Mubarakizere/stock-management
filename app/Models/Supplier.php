<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];

    /**
     * Supplier purchase orders.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Supplier purchase returns.
     */
    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * Credit facilities / loans associated with this supplier.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Financial transactions linked to this supplier.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Operational expenses tied to this supplier.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Debit/Credit entries for double-entry bookkeeping.
     */
    public function debitCredits(): HasMany
    {
        return $this->hasMany(DebitCredit::class);
    }
}
