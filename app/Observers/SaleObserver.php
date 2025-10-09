<?php

namespace App\Observers;

use App\Models\Sale;
use App\Models\Transaction;
use App\Models\DebitCredit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleObserver
{
    /**
     * When a new Sale is created, record matching Transaction and DebitCredit.
     */
    public function created(Sale $sale)
    {
        DB::afterCommit(function () use ($sale) {
            try {
                // ✅ Create the Transaction record
                $transaction = Transaction::create([
                    'type'             => 'credit',
                    'user_id'          => $sale->user_id ?? Auth::id(),
                    'customer_id'      => $sale->customer_id,
                    'sale_id'          => $sale->id,
                    'amount'           => $sale->amount_paid ?? $sale->total_amount ?? 0,
                    'transaction_date' => now(),
                    'method'           => $sale->method ?? 'cash',
                    'notes'            => 'Auto-generated from Sale #' . $sale->id,
                ]);

                // ✅ Create the DebitCredit linked to the Transaction
                DebitCredit::create([
                    'type'           => 'credit',
                    'amount'         => $sale->amount_paid ?? $sale->total_amount ?? 0,
                    'description'    => 'Sale recorded - Invoice #' . $sale->id,
                    'date'           => $sale->created_at->toDateString(),
                    'user_id'        => $sale->user_id ?? Auth::id(),
                    'customer_id'    => $sale->customer_id,
                    'transaction_id' => $transaction->id,
                ]);

                Log::info('SaleObserver created Transaction + DebitCredit', [
                    'sale_id' => $sale->id,
                    'transaction_id' => $transaction->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('SaleObserver failed after commit', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * When Sale is updated, sync related Transaction and DebitCredit.
     */
    public function updated(Sale $sale)
    {
        DB::afterCommit(function () use ($sale) {
            try {
                // Update linked Transaction
                $transaction = $sale->transaction;
                if ($transaction) {
                    $transaction->update([
                        'user_id'          => $sale->user_id ?? Auth::id(),
                        'customer_id'      => $sale->customer_id,
                        'amount'           => $sale->amount_paid ?? $sale->total_amount ?? 0,
                        'transaction_date' => now(),
                        'notes'            => 'Updated from Sale #' . $sale->id,
                    ]);
                }

                // Update DebitCredit linked to that Transaction
                $debitCredit = DebitCredit::where('transaction_id', $transaction?->id)->first();
                if ($debitCredit) {
                    $debitCredit->update([
                        'amount'       => $sale->amount_paid ?? $sale->total_amount ?? 0,
                        'description'  => 'Updated Sale - Invoice #' . $sale->id,
                        'date'         => now()->toDateString(),
                        'user_id'      => $sale->user_id ?? Auth::id(),
                        'customer_id'  => $sale->customer_id,
                    ]);
                }

                Log::info('SaleObserver updated Transaction + DebitCredit', [
                    'sale_id' => $sale->id,
                    'transaction_id' => $transaction?->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('SaleObserver update failed', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * When Sale is deleted, also remove related records.
     */
    public function deleted(Sale $sale)
    {
        DB::afterCommit(function () use ($sale) {
            try {
                if ($sale->transaction) {
                    $sale->transaction->delete();
                }

                DebitCredit::whereHas('transaction', function ($q) use ($sale) {
                    $q->where('sale_id', $sale->id);
                })->delete();

                Log::info('SaleObserver cleanup done', ['sale_id' => $sale->id]);
            } catch (\Throwable $e) {
                Log::error('SaleObserver delete failed', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
