<?php

namespace App\Observers;

use App\Models\{Purchase, Transaction, DebitCredit};
use Illuminate\Support\Facades\{DB, Auth, Log};

class PurchaseObserver
{
    /**
     * Handle the Purchase "created" event.
     * Runs AFTER the DB transaction is committed (PostgreSQL-safe).
     */
    public function created(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                Log::info('🧾 PurchaseObserver: Auto transaction creation started', [
                    'purchase_id' => $purchase->id,
                ]);

                // ✅ Create Transaction (debit)
                $transaction = Transaction::create([
                    'type' => 'debit',
                    'user_id' => $purchase->user_id ?? Auth::id(),
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_id' => $purchase->id,
                    'amount' => $purchase->amount_paid ?? $purchase->total_amount ?? 0,
                    'transaction_date' => $purchase->purchase_date ?? now(),
                    'method' => 'cash',
                    'notes' => 'Auto-generated for Purchase #' . $purchase->id,
                ]);

                Log::info('💰 PurchaseObserver: Transaction created', [
                    'transaction_id' => $transaction->id,
                ]);

                // ✅ Create linked DebitCredit
                DebitCredit::create([
                    'type' => 'debit',
                    'amount' => $transaction->amount,
                    'description' => 'Purchase recorded - #' . $purchase->id,
                    'date' => now(),
                    'user_id' => $purchase->user_id ?? Auth::id(),
                    'supplier_id' => $purchase->supplier_id,
                    'transaction_id' => $transaction->id,
                ]);

                Log::info('💸 PurchaseObserver: DebitCredit created', [
                    'purchase_id' => $purchase->id,
                    'transaction_id' => $transaction->id,
                ]);
            } catch (\Exception $e) {
                Log::error('❌ PurchaseObserver: Creation failed', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Handle the Purchase "updated" event.
     */
    public function updated(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                $transaction = $purchase->transaction;

                if ($transaction) {
                    $transaction->update([
                        'amount' => $purchase->amount_paid ?? $purchase->total_amount ?? 0,
                        'transaction_date' => now(),
                        'notes' => 'Updated from Purchase #' . $purchase->id,
                    ]);

                    Log::info('♻️ PurchaseObserver: Transaction updated', [
                        'transaction_id' => $transaction->id,
                    ]);

                    $debitCredit = DebitCredit::where('transaction_id', $transaction->id)->first();
                    if ($debitCredit) {
                        $debitCredit->update([
                            'amount' => $transaction->amount,
                            'description' => 'Updated Purchase - #' . $purchase->id,
                            'date' => now(),
                        ]);

                        Log::info('♻️ PurchaseObserver: DebitCredit updated', [
                            'debit_credit_id' => $debitCredit->id,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('❌ PurchaseObserver: Update failed', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Handle the Purchase "deleted" event.
     */
    public function deleted(Purchase $purchase)
    {
        DB::afterCommit(function () use ($purchase) {
            try {
                $transaction = $purchase->transaction;
                if ($transaction) {
                    DebitCredit::where('transaction_id', $transaction->id)->delete();
                    $transaction->delete();

                    Log::info('🗑️ PurchaseObserver: Deleted linked records', [
                        'purchase_id' => $purchase->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ PurchaseObserver: Delete failed', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
