<?php

namespace App\Observers;

use App\Models\Sale;
use App\Models\Transaction;

class SaleObserver
{
    public function created(Sale $sale)
    {
        Transaction::create([
            'type' => 'credit',
            'user_id' => $sale->user_id,
            'customer_id' => $sale->customer_id,
            'sale_id' => $sale->id,
            'amount' => $sale->total_amount ?? 0,
            'transaction_date' => now(),
            'method' => 'cash',
            'notes' => 'Auto-generated from Sale #' . $sale->id,
        ]);
    }

    public function updated(Sale $sale)
    {
        $transaction = $sale->transaction;
        if ($transaction) {
            $transaction->update([
                'user_id' => $sale->user_id,
                'customer_id' => $sale->customer_id,
                'amount' => $sale->total_amount ?? 0,
                'transaction_date' => now(),
                'notes' => 'Updated from Sale #' . $sale->id,
            ]);
        }
    }

    public function deleted(Sale $sale)
    {
        if ($sale->transaction) {
            $sale->transaction->delete();
        }
    }
}
