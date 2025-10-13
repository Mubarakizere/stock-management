<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $sale->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container { width: 92%; margin: 0 auto; padding: 20px 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1e293b; font-size: 22px; }
        .header h3 { margin: 0; color: #111827; }
        .header p { margin: 2px 0; }

        h4 { margin-bottom: 6px; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
        }
        th {
            background-color: #f3f4f6;
            text-align: left;
            color: #111;
            font-weight: 600;
        }
        td { color: #333; }
        .total-row { background-color: #f9fafb; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        .section { margin-top: 20px; }
        .footer {
            text-align: center;
            margin-top: 35px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">

    {{-- 🔹 Header --}}
    <div class="header">
        <div>
            <h1>INVOICE</h1>
            <p><strong>Invoice #:</strong> {{ $sale->id }}</p>
            <p><strong>Date:</strong> {{ optional($sale->sale_date)->format('Y-m-d') }}</p>
            @if($sale->user)
                <p><strong>Processed by:</strong> {{ $sale->user->name }}</p>
            @endif
        </div>
        <div style="text-align:right;">
            <h3>{{ config('app.name', 'Stock Manager') }}</h3>
            <p>Professional Stock Management System</p>
        </div>
    </div>

    {{-- 🔹 Customer Info --}}
    <div class="section">
        <h4>Bill To:</h4>
        @if($sale->customer)
            <p><strong>Name:</strong> {{ $sale->customer->name }}</p>
            @if($sale->customer->phone)
                <p><strong>Phone:</strong> {{ $sale->customer->phone }}</p>
            @endif
            @if($sale->customer->email)
                <p><strong>Email:</strong> {{ $sale->customer->email }}</p>
            @endif
        @else
            <p>Walk-in Customer</p>
        @endif
    </div>

    {{-- 🔹 Items --}}
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        @php
            $balance = round(($sale->total_amount ?? 0) - ($sale->amount_paid ?? 0), 2);
        @endphp
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right">Total:</td>
                <td class="text-right">{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">Paid:</td>
                <td class="text-right">{{ number_format($sale->amount_paid ?? 0, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="text-right">Balance:</td>
                <td class="text-right" style="color: {{ $balance > 0 ? 'red' : 'green' }};">
                    {{ number_format($balance, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- 🔹 Linked Loan (if any) --}}
    @if($sale->loan)
        <div class="section" style="margin-top:25px;">
            <h4>Linked Loan</h4>
            <table>
                <tr>
                    <th>Loan Type</th>
                    <td>{{ ucfirst($sale->loan->type) }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>{{ number_format($sale->loan->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ ucfirst($sale->loan->status) }}</td>
                </tr>
                <tr>
                    <th>Loan Date</th>
                    <td>{{ $sale->loan->loan_date }}</td>
                </tr>
                @if($sale->loan->due_date)
                <tr>
                    <th>Due Date</th>
                    <td>{{ $sale->loan->due_date }}</td>
                </tr>
                @endif
            </table>
        </div>
    @endif

    {{-- 🔹 Notes --}}
    @if($sale->notes)
        <div class="section">
            <strong>Notes:</strong>
            <p>{{ $sale->notes }}</p>
        </div>
    @endif

    {{-- 🔹 Footer --}}
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Generated by {{ config('app.name') }} — {{ now()->format('d M Y, H:i') }}</p>
    </div>
</div>
</body>
</html>
