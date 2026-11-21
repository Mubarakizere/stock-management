<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\DebitCredit;
use App\Models\PaymentChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoanPaymentController extends Controller
{
    /** Allow tiny rounding wiggle room when comparing remaining balance */
    private const EPSILON = 0.01;

    /**
     * Show the form to add a new payment for a loan.
     */
    public function create(Loan $loan)
    {
        // Block adding payments to already closed loans
        if ($loan->status === 'paid') {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', 'This loan is already marked as paid. You cannot add more payments.');
        }

        $paymentChannels = PaymentChannel::where('is_active', true)->orderBy('name')->get();

        return view('loan_payments.create', compact('loan', 'paymentChannels'));
    }

    /**
     * Store a new loan payment.
     */
    public function store(Request $request, Loan $loan)
    {
        if ($loan->status === 'paid') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Cannot record payment — this loan is already marked as paid.'], 422);
            }
            return redirect()->route('loans.show', $loan)
                ->with('error', 'Cannot record payment — this loan is already marked as paid.');
        }

        try {
            $validated = $request->validate([
                'amount'       => ['required', 'numeric', 'min:0.01'],
                'payment_date' => ['required', 'date'],
                'method'       => ['required', 'string'],
                'notes'        => ['nullable', 'string', 'max:500'],
            ]);

            $totalPaidSoFar = (float) $loan->payments()->sum('amount');
            $remaining = max(((float) $loan->amount) - $totalPaidSoFar, 0);

            if ((float)$validated['amount'] > ($remaining + self::EPSILON)) {
                $err = 'Payment amount (RWF ' . number_format($validated['amount'], 2) . ') exceeds remaining loan balance (RWF ' . number_format($remaining, 2) . ').';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'errors' => ['amount' => [$err]]], 422);
                }
                return back()->withInput()->withErrors(['amount' => $err]);
            }

            $payment = null;

            DB::transaction(function () use ($validated, $loan, &$payment) {
                $userId = Auth::id();

                $payment = $loan->payments()->create([
                    'user_id'      => $userId,
                    'amount'       => (float) $validated['amount'],
                    'payment_date' => $validated['payment_date'],
                    'method'       => strtolower(trim($validated['method'])),
                    'notes'        => $validated['notes'] ?? null,
                ]);

                $txnType = $loan->type === 'taken' ? 'debit' : 'credit';

                $transaction = Transaction::create([
                    'type'             => $txnType,
                    'amount'           => $payment->amount,
                    'transaction_date' => $validated['payment_date'],
                    'method'           => $payment->method,
                    'user_id'          => $userId,
                    'customer_id'      => $loan->customer_id,
                    'supplier_id'      => $loan->supplier_id,
                    'loan_id'          => $loan->id,
                    'notes'            => 'Loan payment for Loan #' . $loan->id,
                ]);

                DebitCredit::create([
                    'type'           => $txnType,
                    'amount'         => $payment->amount,
                    'description'    => 'Loan payment for Loan #' . $loan->id,
                    'date'           => $validated['payment_date'],
                    'user_id'        => $userId,
                    'customer_id'    => $loan->customer_id,
                    'supplier_id'    => $loan->supplier_id,
                    'transaction_id' => $transaction->id,
                ]);

                // Auto-close if fully paid
                $newTotalPaid = (float) $loan->payments()->sum('amount');
                if ($newTotalPaid >= ((float)$loan->amount - self::EPSILON)) {
                    $loan->update(['status' => 'paid']);
                }
            });

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment recorded successfully.',
                    'payment' => $payment,
                    'loan'    => $loan->fresh(['customer', 'supplier', 'payments'])
                ], 201);
            }

            return redirect()->route('loans.show', $loan)
                ->with('success', 'Payment recorded successfully.');
        } catch (ValidationException $ve) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'errors' => $ve->errors()], 422);
            }
            throw $ve;
        } catch (Throwable $e) {
            Log::error('❌ LoanPayment@store failed', ['loan_id' => $loan->id, 'error' => $e->getMessage()]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Failed to record payment: ' . $e->getMessage()]);
        }
    }
}
