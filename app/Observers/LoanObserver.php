<?php

namespace App\Observers;

use App\Models\Loan;
use App\Models\Transaction;
use App\Models\DebitCredit;
use Illuminate\Support\Facades\Log;

class LoanObserver
{
    /**
     * When a loan is created.
     */
    public function created(Loan $loan)
    {
        try {
            $isGiven = $loan->type === 'given'; // We gave money → Debit
            $isTaken = $loan->type === 'taken'; // We received money → Credit

            // 1️⃣ Create a Transaction record
            $transaction = Transaction::create([
                'type'             => $isGiven ? 'debit' : 'credit',
                'user_id'          => auth()->id() ?? 1,
                'customer_id'      => $loan->customer_id,
                'supplier_id'      => $loan->supplier_id,
                'amount'           => $loan->amount,
                'transaction_date' => $loan->loan_date,
                'method'           => 'cash',
                'notes'            => ucfirst($loan->type) . ' loan recorded - #' . $loan->id,
            ]);

            // 2️⃣ Create DebitCredit entry linked to that transaction
            DebitCredit::create([
                'type'          => $isGiven ? 'debit' : 'credit',
                'amount'        => $loan->amount,
                'description'   => ucfirst($loan->type) . ' loan recorded - #' . $loan->id,
                'date'          => $loan->loan_date,
                'user_id'       => auth()->id() ?? 1,
                'customer_id'   => $loan->customer_id,
                'supplier_id'   => $loan->supplier_id,
                'transaction_id'=> $transaction->id, // ✅ Correct reference
            ]);

            Log::info('✅ LoanObserver success', [
                'loan_id' => $loan->id,
                'transaction_id' => $transaction->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver create failed', [
                'loan_id' => $loan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * When a loan is updated.
     */
    public function updated(Loan $loan)
    {
        try {
            $debitCredit = DebitCredit::whereHas('transaction', function ($q) use ($loan) {
                $q->where('notes', 'like', '%' . $loan->id . '%');
            })->first();

            if ($debitCredit) {
                $debitCredit->update([
                    'amount'      => $loan->amount,
                    'description' => ucfirst($loan->type) . ' loan updated - #' . $loan->id,
                    'date'        => $loan->updated_at->toDateString(),
                ]);
            }

            Log::info('🔁 LoanObserver updated', ['loan_id' => $loan->id]);

        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver update failed', [
                'loan_id' => $loan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * When a loan is deleted.
     */
    public function deleted(Loan $loan)
    {
        try {
            DebitCredit::whereHas('transaction', function ($q) use ($loan) {
                $q->where('notes', 'like', '%' . $loan->id . '%');
            })->delete();

            Transaction::where('notes', 'like', '%' . $loan->id . '%')->delete();

            Log::info('🗑️ LoanObserver deleted records for loan', ['loan_id' => $loan->id]);

        } catch (\Throwable $e) {
            Log::error('❌ LoanObserver delete failed', [
                'loan_id' => $loan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
