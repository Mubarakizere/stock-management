<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stock History Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 30px;
        }
        h2 {
            text-align: center;
            margin-bottom: 4px;
            color: #1f2937;
        }
        p {
            text-align: center;
            color: #555;
            margin-top: 0;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }
        th {
            background: #f3f4f6;
            color: #111827;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        td {
            font-size: 11px;
        }
        .text-right { text-align: right; }
        .badge-in {
            background: #dcfce7;
            color: #166534;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        .badge-out {
            background: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        tfoot td {
            font-weight: bold;
            background: #f9fafb;
            border-top: 2px solid #d1d5db;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <h2>Stock Movement Report</h2>
    <p>Generated on {{ now()->format('Y-m-d H:i') }}</p>

    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Type</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Total Cost</th>
                <th>Recorded By</th>
                <th>Source</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalIn = 0;
                $totalOut = 0;
            @endphp

            @foreach($movements as $m)
                <tr>
                    <td>{{ $m->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $m->product->name }}</td>
                    <td>
                        @if($m->type === 'in')
                            <span class="badge-in">IN</span>
                            @php $totalIn += $m->quantity; @endphp
                        @else
                            <span class="badge-out">OUT</span>
                            @php $totalOut += $m->quantity; @endphp
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($m->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($m->unit_cost ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($m->total_cost ?? 0, 2) }}</td>
                    <td>{{ $m->user->name ?? 'System' }}</td>
                    <td>{{ class_basename($m->source_type) }} #{{ $m->source_id }}</td>
                </tr>
            @endforeach
        </tbody>

        {{-- Summary Totals --}}
        <tfoot>
            @php
                $net = $totalIn - $totalOut;
            @endphp
            <tr>
                <td colspan="3">Totals</td>
                <td class="text-right">{{ number_format($totalIn, 2) }}</td>
                <td colspan="3" class="text-right">Total Out:</td>
                <td class="text-right">{{ number_format($totalOut, 2) }}</td>
            </tr>
            <tr>
                <td colspan="7" class="text-right">Net Movement:</td>
                <td class="text-right">{{ number_format($net, 2) }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
