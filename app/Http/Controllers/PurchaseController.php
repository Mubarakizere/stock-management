<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{
    /**
     * List all purchases.
     */
    public function index()
    {
        $purchases = Purchase::with('supplier')->latest()->get();
        return view('purchases.index', compact('purchases'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        return view('purchases.create', compact('suppliers', 'products'));
    }

    /**
     * Store a new purchase.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.cost_price' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'user_id' => Auth::id(),
                'purchase_date' => $request->purchase_date,
                'total_amount' => 0,
                'amount_paid' => $request->amount_paid ?? 0,
                'notes' => $request->notes ?? null,
            ]);

            $totalAmount = 0;

            foreach ($request->products as $item) {
                $subtotal = $item['quantity'] * $item['cost_price'];
                $totalAmount += $subtotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'subtotal' => $subtotal,
                ]);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['cost_price'],
                    'total_cost' => $subtotal,
                    'source_type' => Purchase::class,
                    'source_id' => $purchase->id,
                    'user_id' => Auth::id(),
                ]);

                $product = Product::find($item['product_id']);
                $this->updateWeightedAverageCost($product, $item['quantity'], $item['cost_price']);
            }

            $purchase->update(['total_amount' => $totalAmount]);

            if ($purchase->amount_paid > 0) {
                Transaction::create([
                    'type' => 'debit',
                    'user_id' => Auth::id(),
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_id' => $purchase->id,
                    'amount' => $purchase->amount_paid,
                    'method' => $request->method ?? 'cash',
                    'notes' => $purchase->notes,
                ]);
            }

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Purchase recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Weighted Average Cost Calculation
     */
    private function updateWeightedAverageCost(Product $product, float $qtyIn, float $costIn): void
    {
        $currentStock = $product->currentStock();
        $oldCost = $product->price ?? 0;

        $oldValue = $currentStock * $oldCost;
        $newValue = $qtyIn * $costIn;
        $newQty = $currentStock + $qtyIn;

        if ($newQty > 0) {
            $newAverage = ($oldValue + $newValue) / $newQty;
            $product->price = round($newAverage, 2);
        }

        $product->save();
    }

    /**
     * Show purchase details.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'transaction']);
        return view('purchases.show', compact('purchase'));
    }

    /**
     * Edit purchase.
     */
    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        $purchase->load('items');
        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    /**
     * Update purchase and its items.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.cost_price' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Rollback old stock movements
            StockMovement::where('source_type', Purchase::class)
                ->where('source_id', $purchase->id)
                ->delete();

            // Delete old items
            $purchase->items()->delete();

            $totalAmount = 0;

            // Recreate items and movements
            foreach ($request->products as $item) {
                $subtotal = $item['quantity'] * $item['cost_price'];
                $totalAmount += $subtotal;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'subtotal' => $subtotal,
                ]);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'in',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['cost_price'],
                    'total_cost' => $subtotal,
                    'source_type' => Purchase::class,
                    'source_id' => $purchase->id,
                    'user_id' => Auth::id(),
                ]);

                $product = Product::find($item['product_id']);
                $this->updateWeightedAverageCost($product, $item['quantity'], $item['cost_price']);
            }

            // Update purchase
            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'total_amount' => $totalAmount,
                'amount_paid' => $request->amount_paid ?? 0,
                'notes' => $request->notes ?? null,
            ]);

            // Update or create transaction
            if ($purchase->amount_paid > 0) {
                Transaction::updateOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'type' => 'debit',
                        'user_id' => Auth::id(),
                        'supplier_id' => $purchase->supplier_id,
                        'amount' => $purchase->amount_paid,
                        'method' => $request->method ?? 'cash',
                        'notes' => $purchase->notes,
                    ]
                );
            } else {
                Transaction::where('purchase_id', $purchase->id)->delete();
            }

            DB::commit();

            return redirect()->route('purchases.show', $purchase->id)->with('success', 'Purchase updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete purchase with rollback.
     */
    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            StockMovement::where('source_type', Purchase::class)
                ->where('source_id', $purchase->id)
                ->delete();

            Transaction::where('purchase_id', $purchase->id)->delete();
            $purchase->items()->delete();
            $purchase->delete();
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
    }

    /**
     * Generate PDF Invoice (DomPDF)
     */
    public function invoice(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'transaction', 'user']);
        $pdf = Pdf::loadView('purchases.invoice', compact('purchase'))
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->stream('purchase-invoice-' . $purchase->id . '.pdf');
    }
}
