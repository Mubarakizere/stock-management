<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stock Movements & Inventory Flow Report</title>
    <style>
        @page { margin: 25px 25px 35px 25px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
            line-height: 1.4;
        }

        h2 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 3px;
            color: #0f172a;
            font-weight: 700;
        }

        p.subtitle {
            text-align: center;
            color: #64748b;
            font-size: 9.5px;
            margin: 0 0 10px 0;
        }

        /* Breakdown Pills */
        .wrap { text-align: center; margin: 6px 0 10px; }
        .pill {
            display: inline-block;
            font-size: 8.5px;
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: 600;
            margin: 2px 3px;
        }
        .pill-emerald { background: #dcfce7; color: #166534; }
        .pill-rose    { background: #fee2e2; color: #991b1b; }
        .pill-amber   { background: #fef3c7; color: #92400e; }
        .pill-sky     { background: #e0f2fe; color: #075985; }
        .pill-indigo  { background: #e0e7ff; color: #3730a3; }
        .pill-blue    { background: #dbeafe; color: #1e40af; }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            table-layout: fixed;
            word-wrap: break-word;
        }

        th, td {
            border: 1px solid #e2e8f0;
            padding: 5px 6px;
        }

        th {
            background: #f1f5f9;
            color: #334155;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
        }

        td { font-size: 9px; }
        tr:nth-child(even) { background: #f8fafc; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .badge-in {
            background: #dcfce7;
            color: #166534;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8.5px;
        }
        .badge-out {
            background: #fee2e2;
            color: #991b1b;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8.5px;
        }

        /* Totals */
        tfoot td {
            font-weight: 700;
            background: #f8fafc;
            border-top: 2px solid #cbd5e1;
            font-size: 9.5px;
        }

        /* Column widths */
        th:nth-child(1) { width: 12%; } /* Date */
        th:nth-child(2) { width: 18%; } /* Product */
        th:nth-child(3) { width: 7%; }  /* Flow */
        th:nth-child(4) { width: 8%; }  /* Qty */
        th:nth-child(5) { width: 9%; }  /* Unit Cost */
        th:nth-child(6) { width: 10%; } /* Total Cost */
        th:nth-child(7) { width: 16%; } /* Reference */
        th:nth-child(8) { width: 10%; } /* User */
        th:nth-child(9) { width: 10%; } /* Source */

        .footer {
            text-align: right;
            font-size: 8.5px;
            color: #64748b;
            margin-top: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <h2>Stock Movements & Inventory Flow Report</h2>
    <p class="subtitle">Generated on {{ now()->format('d M Y, H:i') }} | Nezerwa Plus ERP Audit System</p>

    @php
        $fmt = fn($n) => number_format((float)($n ?? 0), 2);
        $b = $breakdown ?? [];
    @endphp

    <div class="wrap">
        <span class="pill pill-emerald">Purchases IN: {{ $fmt($b['in_purchases'] ?? 0) }}</span>
        <span class="pill pill-rose">Sales OUT: {{ $fmt($b['out_sales'] ?? 0) }}</span>
        <span class="pill pill-amber">Production IN: {{ $fmt($b['in_production'] ?? 0) }}</span>
        <span class="pill pill-amber">Production OUT: {{ $fmt($b['out_production'] ?? 0) }}</span>
        <span class="pill pill-blue">Adjustments: {{ $fmt($b['adjustments'] ?? 0) }}</span>
        <span class="pill pill-sky">Returns: {{ $fmt(($b['in_sale_returns'] ?? 0) + ($b['out_returns'] ?? 0)) }}</span>
        <span class="pill pill-indigo">Loans: {{ $fmt(($b['out_loans'] ?? 0) + ($b['in_loan_returns'] ?? 0)) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Inventory Item</th>
                <th class="text-center">Flow</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Total Cost</th>
                <th>Reference / Memo</th>
                <th>User</th>
                <th>Source</th>
            </tr>
        </thead>
        <tbody>
            @php $totalIn = 0; $totalOut = 0; @endphp

            @foreach($movements as $m)
                @php
                    $isIn = $m->isFlowIn();
                    if ($isIn) { $totalIn += (float)($m->quantity ?? 0); }
                    else       { $totalOut += (float)($m->quantity ?? 0); }

                    $source = $m->sourceDetails()['label'];
                    $reference = $m->referenceText();
                    $unit = optional($m->product)->unit ?? 'pcs';
                @endphp

                <tr>
                    <td>{{ optional($m->created_at)->format('d M Y, H:i') }}</td>
                    <td>{{ optional($m->product)->name ?? 'Unknown' }}</td>
                    <td class="text-center">
                        @if($isIn)
                            <span class="badge-in">IN</span>
                        @else
                            <span class="badge-out">OUT</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $fmt($m->quantity ?? 0) }} {{ $unit }}</td>
                    <td class="text-right">{{ isset($m->unit_cost) ? $fmt($m->unit_cost) : '—' }}</td>
                    <td class="text-right">{{ isset($m->total_cost) ? $fmt($m->total_cost) : '—' }}</td>
                    <td>{{ $reference ?: '—' }}</td>
                    <td>{{ optional($m->user)->name ?? 'System' }}</td>
                    <td>{{ $source }}</td>
                </tr>
            @endforeach
        </tbody>

        @php $net = $totalIn - $totalOut; @endphp
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total Inflow:</td>
                <td class="text-right" style="color: #166534;">+{{ $fmt($totalIn) }}</td>
                <td colspan="2" class="text-right">Total Outflow:</td>
                <td colspan="3" class="text-right" style="color: #991b1b;">-{{ $fmt($totalOut) }}</td>
            </tr>
            <tr>
                <td colspan="7" class="text-right">Net Inventory Movement Delta:</td>
                <td colspan="2" class="text-right" style="color: {{ $net >= 0 ? '#166534' : '#991b1b' }}; font-size: 10px;">
                    {{ ($net >= 0 ? '+' : '') . $fmt($net) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        {{ config('app.name', 'Nezerwa Plus') }} — Confidential Internal Inventory Audit Report
    </div>

</body>
</html>
