<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'customer_id',
        'supplier_id',
        'amount',
        'loan_date',
        'due_date',
        'status',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function checkIfFullyPaid(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        if ($totalPaid >= $this->amount && $this->status !== 'paid') {
            $this->update(['status' => 'paid']);
        }
    }
}
