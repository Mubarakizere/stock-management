<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    /**
     * Display all sales.
     */
    public function index()
    {
        $sales = Sale::with('customer')->latest()->get();
        return view('sales.index', compact('sales'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('sales.create', compact('customers', 'products'));
    }

    /**
     * Store new sale record.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Create Sale
            $sale = Sale::create([
                'customer_id' => $request->customer_id,
                'user_id' => Auth::id(),
                'sale_date' => $request->sale_date,
                'total_amount' => 0,
                'amount_paid' => $request->amount_paid ?? 0,
                'method' => $request->method ?? 'cash',
                'notes' => $request->notes,
            ]);

            $totalAmount = 0;
            $totalProfit = 0;

            foreach ($request->products as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check stock availability
                if ($product->currentStock() < $item['quantity']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }

                $subtotal = $item['quantity'] * $item['unit_price'];
                $cost_price = $product->price; // use weighted avg cost
                $profit = ($item['unit_price'] - $cost_price) * $item['quantity'];

                // Create Sale Item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'cost_price' => $cost_price,
                    'profit' => $profit,
                ]);

                // Record Stock Movement (out)
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $cost_price,
                    'total_cost' => $cost_price * $item['quantity'],
                    'source_type' => Sale::class,
                    'source_id' => $sale->id,
                    'user_id' => Auth::id(),
                ]);

                $totalAmount += $subtotal;
                $totalProfit += $profit;
            }

            // Update total
            $sale->update(['total_amount' => $totalAmount]);

            // Create Transaction (money in)
            if ($sale->amount_paid > 0) {
                Transaction::create([
                    'type' => 'credit', // money in
                    'user_id' => Auth::id(),
                    'customer_id' => $sale->customer_id,
                    'sale_id' => $sale->id,
                    'amount' => $sale->amount_paid,
                    'method' => $request->method ?? 'cash',
                    'notes' => $sale->notes,
                ]);
            }

            DB::commit();
            return redirect()->route('sales.index')->with('success', 'Sale recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show sale details.
     */
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'items.product', 'transaction']);
        return view('sales.show', compact('sale'));
    }

    /**
     * Edit sale form.
     */
    public function edit(Sale $sale)
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $sale->load('items');
        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    /**
     * Update sale and stock movements.
     */
    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Remove old stock movements
            StockMovement::where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->delete();

            $sale->items()->delete();

            $totalAmount = 0;
            $totalProfit = 0;

            foreach ($request->products as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->currentStock() < $item['quantity']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }

                $subtotal = $item['quantity'] * $item['unit_price'];
                $cost_price = $product->price;
                $profit = ($item['unit_price'] - $cost_price) * $item['quantity'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'cost_price' => $cost_price,
                    'profit' => $profit,
                ]);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $cost_price,
                    'total_cost' => $cost_price * $item['quantity'],
                    'source_type' => Sale::class,
                    'source_id' => $sale->id,
                    'user_id' => Auth::id(),
                ]);

                $totalAmount += $subtotal;
                $totalProfit += $profit;
            }

            // Update sale info
            $sale->update([
                'customer_id' => $request->customer_id,
                'sale_date' => $request->sale_date,
                'total_amount' => $totalAmount,
                'amount_paid' => $request->amount_paid ?? 0,
                'method' => $request->method ?? 'cash',
                'notes' => $request->notes,
            ]);

            // Update or remove transaction
            if ($sale->amount_paid > 0) {
                Transaction::updateOrCreate(
                    ['sale_id' => $sale->id],
                    [
                        'type' => 'credit',
                        'user_id' => Auth::id(),
                        'customer_id' => $sale->customer_id,
                        'amount' => $sale->amount_paid,
                        'method' => $request->method ?? 'cash',
                        'notes' => $sale->notes,
                    ]
                );
            } else {
                Transaction::where('sale_id', $sale->id)->delete();
            }

            DB::commit();
            return redirect()->route('sales.show', $sale->id)->with('success', 'Sale updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete sale and rollback stock.
     */
    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            StockMovement::where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->delete();

            Transaction::where('sale_id', $sale->id)->delete();
            $sale->items()->delete();
            $sale->delete();
        });

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
    }

    /**
     * Generate PDF invoice.
     */
    public function invoice(Sale $sale)
    {
        $sale->load(['customer', 'items.product', 'transaction', 'user']);

        $pdf = Pdf::loadView('sales.invoice', compact('sale'))
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->stream('sale-invoice-' . $sale->id . '.pdf');
    }
}
