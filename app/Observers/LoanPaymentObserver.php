<?php

namespace App\Observers;

use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\DebitCredit;

class LoanPaymentObserver
{
    public function created(LoanPayment $payment)
    {
        $loan = $payment->loan;

        // 1. Create financial entries
        Transaction::create([
            'type' => 'credit', // repayment = money in
            'user_id' => $payment->user_id,
            'customer_id' => $loan->customer_id,
            'supplier_id' => $loan->supplier_id,
            'amount' => $payment->amount,
            'transaction_date' => $payment->payment_date,
            'method' => $payment->method,
            'notes' => 'Loan payment for Loan #' . $loan->id,
        ]);

        DebitCredit::create([
            'type' => 'credit',
            'amount' => $payment->amount,
            'description' => 'Loan payment for Loan #' . $loan->id,
            'date' => $payment->payment_date,
            'user_id' => $payment->user_id,
            'customer_id' => $loan->customer_id,
            'supplier_id' => $loan->supplier_id,
            'transaction_id' => $loan->id,
        ]);

        // 2. Update loan balance
        $totalPaid = $loan->payments()->sum('amount');
        if ($totalPaid >= $loan->amount) {
            $loan->update(['status' => 'paid']);
        }
    }

    public function deleted(LoanPayment $payment)
    {
        DebitCredit::where('description', 'like', '%Loan #' . $payment->loan_id . '%')
            ->where('amount', $payment->amount)
            ->delete();

        Transaction::where('notes', 'like', '%Loan #' . $payment->loan_id . '%')
            ->where('amount', $payment->amount)
            ->delete();
    }
}
