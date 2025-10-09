<?php

namespace App\Http\Controllers;

use App\Models\{
    Sale,
    SaleItem,
    Product,
    Customer,
    Transaction,
    StockMovement
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    /**
     * Display all sales.
     */
    public function index()
    {
        $sales = Sale::with(['customer', 'user'])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'price', 'cost_price']);

        return view('sales.create', compact('customers', 'products'));
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(Request $request)
    {
        Log::info('Starting sale creation...', ['user' => Auth::id(), 'payload' => $request->all()]);

        // 🧹 Clean empty product rows before validating
        $products = collect($request->input('products', []))
            ->filter(fn($p) =>
                !empty($p['product_id']) &&
                isset($p['quantity']) &&
                isset($p['unit_price']) &&
                floatval($p['quantity']) > 0
            )
            ->values()
            ->toArray();

        $request->merge(['products' => $products]);

        $request->validate([
            'customer_id'           => 'nullable|exists:customers,id',
            'sale_date'             => 'required|date',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
            'amount_paid'           => 'nullable|numeric|min:0',
            'method'                => 'nullable|string|max:50',
            'notes'                 => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            Log::info('Transaction started');

            // Create the sale
            $sale = Sale::create([
                'customer_id'  => $request->customer_id,
                'user_id'      => Auth::id(),
                'sale_date'    => $request->sale_date,
                'total_amount' => 0,
                'amount_paid'  => $request->amount_paid ?? 0,
                'method'       => $request->method ?? 'cash',
                'status'       => 'pending',
                'notes'        => $request->notes,
            ]);

            Log::info('Sale created', ['sale_id' => $sale->id]);

            $totalAmount = 0;
            $totalProfit = 0;

            foreach ($request->products as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

                if (method_exists($product, 'currentStock') && $product->currentStock() < $item['quantity']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }

                $qty       = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $subtotal  = round($qty * $unitPrice, 2);
                $cost      = (float) ($product->cost_price ?? $product->price ?? 0);
                $profit    = round(($unitPrice - $cost) * $qty, 2);

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal,
                    'cost_price' => $cost,
                    'profit'     => $profit,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type'       => 'out',
                    'quantity'   => $qty,
                    'unit_cost'  => $cost,
                    'total_cost' => round($cost * $qty, 2),
                    'source_type'=> Sale::class,
                    'source_id'  => $sale->id,
                    'user_id'    => Auth::id(),
                ]);

                $totalAmount += $subtotal;
                $totalProfit += $profit;
            }

            $sale->update([
                'total_amount' => $totalAmount,
                'status'       => ($totalAmount <= ($sale->amount_paid ?? 0)) ? 'completed' : 'pending',
            ]);

            if ($sale->amount_paid > 0) {
                Transaction::create([
                    'type'        => 'credit',
                    'user_id'     => Auth::id(),
                    'customer_id' => $sale->customer_id,
                    'sale_id'     => $sale->id,
                    'amount'      => $sale->amount_paid,
                    'method'      => $sale->method,
                    'notes'       => $sale->notes,
                ]);
            }

            DB::commit();
            Log::info('Sale stored successfully', ['sale_id' => $sale->id]);

            return redirect()->route('sales.index')->with('success', 'Sale recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sale creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create sale: ' . $e->getMessage()])
                         ->withInput();
        }
    }

    /**
     * Display a single sale.
     */
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'items.product', 'transaction', 'user']);
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing an existing sale.
     */
    public function edit(Sale $sale)
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'price', 'cost_price']);
        $sale->load('items.product');

        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    /**
     * Update a sale.
     */
    public function update(Request $request, Sale $sale)
    {
        Log::info('Updating sale', ['sale_id' => $sale->id, 'user' => Auth::id()]);

        // 🧹 Clean empty product rows before validating
        $products = collect($request->input('products', []))
            ->filter(fn($p) =>
                !empty($p['product_id']) &&
                isset($p['quantity']) &&
                isset($p['unit_price']) &&
                floatval($p['quantity']) > 0
            )
            ->values()
            ->toArray();

        $request->merge(['products' => $products]);

        $request->validate([
            'customer_id'           => 'nullable|exists:customers,id',
            'sale_date'             => 'required|date',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity'   => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
            'amount_paid'           => 'nullable|numeric|min:0',
            'method'                => 'nullable|string|max:50',
            'notes'                 => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            StockMovement::where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->delete();
            $sale->items()->delete();

            $totalAmount = 0;
            $totalProfit = 0;

            foreach ($request->products as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

                if (method_exists($product, 'currentStock') && $product->currentStock() < $item['quantity']) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }

                $qty       = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $subtotal  = round($qty * $unitPrice, 2);
                $cost      = (float) ($product->cost_price ?? $product->price ?? 0);
                $profit    = round(($unitPrice - $cost) * $qty, 2);

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal,
                    'cost_price' => $cost,
                    'profit'     => $profit,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type'       => 'out',
                    'quantity'   => $qty,
                    'unit_cost'  => $cost,
                    'total_cost' => round($cost * $qty, 2),
                    'source_type'=> Sale::class,
                    'source_id'  => $sale->id,
                    'user_id'    => Auth::id(),
                ]);

                $totalAmount += $subtotal;
                $totalProfit += $profit;
            }

            $sale->update([
                'customer_id'  => $request->customer_id,
                'sale_date'    => $request->sale_date,
                'total_amount' => $totalAmount,
                'amount_paid'  => $request->amount_paid ?? 0,
                'method'       => $request->method ?? 'cash',
                'status'       => ($totalAmount <= ($request->amount_paid ?? 0)) ? 'completed' : 'pending',
                'notes'        => $request->notes,
            ]);

            if ($sale->amount_paid > 0) {
                Transaction::updateOrCreate(
                    ['sale_id' => $sale->id],
                    [
                        'type'        => 'credit',
                        'user_id'     => Auth::id(),
                        'customer_id' => $sale->customer_id,
                        'amount'      => $sale->amount_paid,
                        'method'      => $sale->method,
                        'notes'       => $sale->notes,
                    ]
                );
            } else {
                Transaction::where('sale_id', $sale->id)->delete();
            }

            DB::commit();
            Log::info('Sale updated successfully', ['sale_id' => $sale->id]);

            return redirect()->route('sales.show', $sale->id)->with('success', 'Sale updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sale update failed', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to update sale: ' . $e->getMessage()])
                         ->withInput();
        }
    }

    /**
     * Remove a sale and revert stock.
     */
    public function destroy(Sale $sale)
    {
        Log::warning('Deleting sale', ['sale_id' => $sale->id, 'user' => Auth::id()]);

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
