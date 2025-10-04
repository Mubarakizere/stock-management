<?php

namespace App\Observers;

use App\Models\Loan;
use App\Models\Transaction;
use App\Models\DebitCredit;

class LoanObserver
{
    /**
     * When a loan is created
     */
    public function created(Loan $loan)
    {
        $isGiven = $loan->type === 'given'; // We gave money → Debit
        $isTaken = $loan->type === 'taken'; // We received money → Credit

        // Create a Transaction entry
        Transaction::create([
            'type' => $isGiven ? 'debit' : 'credit',
            'user_id' => auth()->id() ?? 1,
            'customer_id' => $loan->customer_id,
            'supplier_id' => $loan->supplier_id,
            'amount' => $loan->amount,
            'transaction_date' => $loan->loan_date,
            'method' => 'cash',
            'notes' => ucfirst($loan->type) . ' loan recorded - #' . $loan->id,
        ]);

        // Create a DebitCredit entry
        DebitCredit::create([
            'type' => $isGiven ? 'debit' : 'credit',
            'amount' => $loan->amount,
            'description' => ucfirst($loan->type) . ' loan recorded - #' . $loan->id,
            'date' => $loan->loan_date,
            'user_id' => auth()->id() ?? 1,
            'customer_id' => $loan->customer_id,
            'supplier_id' => $loan->supplier_id,
            'transaction_id' => $loan->id,
        ]);
    }

    /**
     * When a loan is updated
     */
    public function updated(Loan $loan)
    {
        $debitCredit = DebitCredit::where('transaction_id', $loan->id)->first();
        if ($debitCredit) {
            $debitCredit->update([
                'amount' => $loan->amount,
                'description' => ucfirst($loan->type) . ' loan updated - #' . $loan->id,
                'date' => $loan->updated_at->toDateString(),
            ]);
        }
    }

    /**
     * When a loan is deleted
     */
    public function deleted(Loan $loan)
    {
        DebitCredit::where('transaction_id', $loan->id)->delete();
        Transaction::where('notes', 'like', '%' . $loan->id . '%')->delete();
    }
}
