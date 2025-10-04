<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;

class LoanPaymentController extends Controller
{
    public function create(Loan $loan)
    {
        return view('loan_payments.create', compact('loan'));
    }

    public function store(Request $request, Loan $loan)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'method' => 'required|string|max:255',
        ]);

        LoanPayment::create([
            'loan_id' => $loan->id,
            'user_id' => auth()->id() ?? 1,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'method' => $request->method,
            'notes' => $request->notes,
        ]);

        return redirect()->route('loans.show', $loan)->with('success', 'Payment recorded successfully.');
    }
}
