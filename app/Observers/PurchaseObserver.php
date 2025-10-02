<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\Transaction;

class PurchaseObserver
{
    public function created(Purchase $purchase)
    {
        Transaction::create([
            'type' => 'debit',
            'user_id' => $purchase->user_id,
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'amount' => $purchase->total_amount ?? 0,
            'transaction_date' => now(),
            'method' => 'cash',
            'notes' => 'Auto-generated from Purchase #' . $purchase->id,
        ]);
    }

    public function updated(Purchase $purchase)
    {
        $transaction = $purchase->transaction;
        if ($transaction) {
            $transaction->update([
                'user_id' => $purchase->user_id,
                'supplier_id' => $purchase->supplier_id,
                'amount' => $purchase->total_amount ?? 0,
                'transaction_date' => now(),
                'notes' => 'Updated from Purchase #' . $purchase->id,
            ]);
        }
    }

    public function deleted(Purchase $purchase)
    {
        if ($purchase->transaction) {
            $purchase->transaction->delete();
        }
    }
}
