<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('customer')->latest()->get();
        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $customers = Customer::all();
        $products = Product::all();
        return view('sales.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        $sale = Sale::create([
            'customer_id' => $request->customer_id,
            'user_id' => auth()->id(),
            'sale_date' => $request->sale_date,
            'total_amount' => 0,
        ]);

        $totalAmount = 0;

        foreach ($request->products as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
            ]);

            $product = Product::find($item['product_id']);
            $product->stock -= $item['quantity']; // decrease stock
            $product->save();

            $totalAmount += $subtotal;
        }

        $sale->update(['total_amount' => $totalAmount]);

        return redirect()->route('sales.index')->with('success', 'Sale recorded successfully.');
    }

    public function show(Sale $sale)
    {
        $sale->load('customer', 'items.product');
        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        $customers = Customer::all();
        $products = Product::all();
        $sale->load('items');
        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        // rollback old stock
        foreach ($sale->items as $item) {
            $product = Product::find($item->product_id);
            $product->stock += $item->quantity;
            $product->save();
        }

        $sale->items()->delete();

        $sale->update([
            'customer_id' => $request->customer_id,
            'sale_date' => $request->sale_date,
        ]);

        $totalAmount = 0;

        foreach ($request->products as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
            ]);

            $product = Product::find($item['product_id']);
            $product->stock -= $item['quantity'];
            $product->save();

            $totalAmount += $subtotal;
        }

        $sale->update(['total_amount' => $totalAmount]);

        return redirect()->route('sales.index')->with('success', 'Sale updated successfully.');
    }

    public function destroy(Sale $sale)
    {
        foreach ($sale->items as $item) {
            $product = Product::find($item->product_id);
            $product->stock += $item->quantity; // rollback stock
            $product->save();
        }

        $sale->delete();

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
    }
}
