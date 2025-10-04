<?php

namespace App\Observers;

use App\Models\Sale;
use App\Models\Transaction;
use App\Models\DebitCredit;

class SaleObserver
{
    public function created(Sale $sale)
    {
        // Auto-create Transaction
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

        // Auto-create DebitCredit
        DebitCredit::create([
            'type' => 'credit',
            'amount' => $sale->total_amount ?? 0,
            'description' => 'Sale recorded - Invoice #' . $sale->id,
            'date' => $sale->created_at->toDateString(),
            'user_id' => $sale->user_id,
            'customer_id' => $sale->customer_id,
            'transaction_id' => $sale->id,
        ]);
    }

    public function updated(Sale $sale)
    {
        // Update Transaction
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

        // Update DebitCredit
        $debitCredit = DebitCredit::where('transaction_id', $sale->id)->first();
        if ($debitCredit) {
            $debitCredit->update([
                'amount' => $sale->total_amount ?? 0,
                'description' => 'Updated Sale - Invoice #' . $sale->id,
                'date' => $sale->updated_at->toDateString(),
                'user_id' => $sale->user_id,
                'customer_id' => $sale->customer_id,
            ]);
        }
    }

    public function deleted(Sale $sale)
    {
        if ($sale->transaction) {
            $sale->transaction->delete();
        }

        $debitCredit = DebitCredit::where('transaction_id', $sale->id)->first();
        if ($debitCredit) {
            $debitCredit->delete();
        }
    }
}
