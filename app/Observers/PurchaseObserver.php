<?php

namespace App\Observers;

use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\DebitCredit;

class PurchaseObserver
{
    public function created(Purchase $purchase)
    {
        // Auto-create Transaction
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

        // Auto-create DebitCredit
        DebitCredit::create([
            'type' => 'debit',
            'amount' => $purchase->total_amount ?? 0,
            'description' => 'Purchase recorded - #' . $purchase->id,
            'date' => $purchase->created_at->toDateString(),
            'user_id' => $purchase->user_id,
            'supplier_id' => $purchase->supplier_id,
            'transaction_id' => $purchase->id,
        ]);
    }

    public function updated(Purchase $purchase)
    {
        // Update Transaction
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

        // Update DebitCredit
        $debitCredit = DebitCredit::where('transaction_id', $purchase->id)->first();
        if ($debitCredit) {
            $debitCredit->update([
                'amount' => $purchase->total_amount ?? 0,
                'description' => 'Updated Purchase - #' . $purchase->id,
                'date' => $purchase->updated_at->toDateString(),
                'user_id' => $purchase->user_id,
                'supplier_id' => $purchase->supplier_id,
            ]);
        }
    }

    public function deleted(Purchase $purchase)
    {
        // Delete linked transaction
        if ($purchase->transaction) {
            $purchase->transaction->delete();
        }

        // Delete linked DebitCredit
        $debitCredit = DebitCredit::where('transaction_id', $purchase->id)->first();
        if ($debitCredit) {
            $debitCredit->delete();
        }
    }
}
