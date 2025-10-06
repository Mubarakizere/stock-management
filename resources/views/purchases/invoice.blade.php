<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Invoice #{{ $purchase->id }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color:#111; font-size:13px; }
        .wrap { width:100%; padding:20px; }
        .row { display:flex; justify-content:space-between; gap:20px; }
        .col { width:48%; }
        .title { font-size:22px; font-weight:700; color:#111; margin:0 0 4px; }
        .muted { color:#555; }
        .hr { border-top:1px solid #e5e7eb; margin:14px 0; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #e5e7eb; padding:8px; }
        th { background:#f3f4f6; text-align:left; }
        td.num, th.num { text-align:right; }
        .total-row td { font-weight:700; background:#f9fafb; }
        .badge { display:inline-block; padding:2px 8px; border-radius:12px; font-size:11px; }
        .badge-green { background:#dcfce7; color:#166534; }
        .badge-yellow { background:#fef9c3; color:#854d0e; }
        .badge-red { background:#fee2e2; color:#991b1b; }
        .footer { display:flex; justify-content:space-between; margin-top:16px; color:#555; }
    </style>
</head>
<body>
<div class="wrap">

    {{-- HEADER: Company (no logo) + Invoice meta --}}
    <div class="row">
        <div class="col">
            <div class="title">{{ config('company.name') }}</div>
            <div>{{ config('company.address_line1') }}</div>
            @if(config('company.address_line2'))<div>{{ config('company.address_line2') }}</div>@endif
            @if(config('company.phone'))<div>Phone: {{ config('company.phone') }}</div>@endif
            @if(config('company.email'))<div>Email: {{ config('company.email') }}</div>@endif
            @if(config('company.tax_id'))<div>{{ config('company.tax_id') }}</div>@endif
        </div>
        <div class="col" style="text-align:right;">
            <div class="title">Purchase Invoice</div>
            <div>Invoice #: <strong>#{{ $purchase->id }}</strong></div>
            <div>Date: <strong>{{ $purchase->purchase_date }}</strong></div>
            <div>Recorded by: <strong>{{ $purchase->user->name ?? 'System' }}</strong></div>
            <div>Status:
                @php
                    $status = $purchase->status ?? 'completed';
                    $badge = $status === 'completed' ? 'badge-green' : ($status === 'pending' ? 'badge-yellow' : 'badge-red');
                @endphp
                <span class="badge {{ $badge }}">{{ ucfirst($status) }}</span>
            </div>
        </div>
    </div>

    <div class="hr"></div>

    {{-- SUPPLIER INFO --}}
    <div class="row">
        <div class="col">
            <strong>Supplier</strong><br>
            {{ $purchase->supplier->name }}<br>
            {{-- If you add supplier fields later, render them here (phone, address, etc.) --}}
        </div>
        <div class="col" style="text-align:right;">
            @if($purchase->invoice_number)
                <div>Supplier Invoice: <strong>{{ $purchase->invoice_number }}</strong></div>
            @endif
            @if(isset($purchase->method))
                <div>Payment Method: <strong>{{ ucfirst($purchase->method) }}</strong></div>
            @endif
        </div>
    </div>

    {{-- ITEMS TABLE --}}
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="num">Quantity</th>
                <th class="num">Unit Cost</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="num">{{ number_format($item->quantity, 2) }}</td>
                    <td class="num">{{ number_format($item->cost_price, 2) }}</td>
                    <td class="num">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        @php
            $subtotal = $purchase->subtotal ?? $purchase->items->sum('subtotal');
            $tax = $purchase->tax ?? 0;
            $discount = $purchase->discount ?? 0;
            $total = $purchase->total_amount ?? (($subtotal + $tax) - $discount);
            $paid = $purchase->amount_paid ?? 0;
            $balance = $total - $paid;
        @endphp
        <tfoot>
            <tr>
                <td colspan="3" class="num muted">Subtotal</td>
                <td class="num">{{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="num muted">Tax</td>
                <td class="num">{{ number_format($tax, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="num muted">Discount</td>
                <td class="num">-{{ number_format($discount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" class="num">Total Amount</td>
                <td class="num">{{ number_format($total, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="num muted">Amount Paid</td>
                <td class="num">{{ number_format($paid, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="num muted">Balance Due</td>
                <td class="num">{{ number_format($balance, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- NOTES --}}
    @if($purchase->notes)
        <div style="margin-top:12px;">
            <strong>Notes:</strong>
            <div class="muted">{{ $purchase->notes }}</div>
        </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        <div>Generated on {{ now()->format('d M Y, H:i') }}</div>
        <div>{{ config('company.name') }} • {{ config('company.email') }}</div>
    </div>
</div>
</body>
</html>
