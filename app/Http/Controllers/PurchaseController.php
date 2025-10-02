<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier')->latest()->get();
        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.cost_price' => 'required|numeric|min:0',
        ]);

        $purchase = Purchase::create([
            'supplier_id' => $request->supplier_id,
            'user_id' => auth()->id(),
            'purchase_date' => $request->purchase_date,
            'total_amount' => 0,
        ]);

        $totalAmount = 0;

        foreach ($request->products as $item) {
            $subtotal = $item['quantity'] * $item['cost_price'];

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'cost_price' => $item['cost_price'],
                'subtotal' => $subtotal,
            ]);

            $product = Product::find($item['product_id']);
            $product->stock += $item['quantity'];
            $product->save();

            $totalAmount += $subtotal;
        }

        $purchase->update(['total_amount' => $totalAmount]);

        return redirect()->route('purchases.index')->with('success', 'Purchase recorded successfully.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.product');
        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        $purchase->load('items');
        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.cost_price' => 'required|numeric|min:0',
        ]);

        // rollback old stock
        foreach ($purchase->items as $item) {
            $product = Product::find($item->product_id);
            $product->stock -= $item->quantity;
            $product->save();
        }

        // delete old items
        $purchase->items()->delete();

        // update purchase info
        $purchase->update([
            'supplier_id' => $request->supplier_id,
            'purchase_date' => $request->purchase_date,
        ]);

        $totalAmount = 0;

        // add new items
        foreach ($request->products as $item) {
            $subtotal = $item['quantity'] * $item['cost_price'];

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'cost_price' => $item['cost_price'],
                'subtotal' => $subtotal,
            ]);

            $product = Product::find($item['product_id']);
            $product->stock += $item['quantity'];
            $product->save();

            $totalAmount += $subtotal;
        }

        $purchase->update(['total_amount' => $totalAmount]);

        return redirect()->route('purchases.index')->with('success', 'Purchase updated successfully.');
    }

    public function destroy(Purchase $purchase)
    {
        // rollback stock
        foreach ($purchase->items as $item) {
            $product = Product::find($item->product_id);
            $product->stock -= $item->quantity;
            $product->save();
        }

        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
    }
}
