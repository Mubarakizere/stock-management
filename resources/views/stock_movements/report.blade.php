<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock History Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .text-right { text-align: right; }
        h2 { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <h2>Stock History Report</h2>
    <p>Generated: {{ now()->format('Y-m-d H:i') }}</p>

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
            @foreach($movements as $m)
            <tr>
                <td>{{ $m->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $m->product->name }}</td>
                <td>{{ strtoupper($m->type) }}</td>
                <td class="text-right">{{ number_format($m->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($m->unit_cost ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($m->total_cost ?? 0, 2) }}</td>
                <td>{{ $m->user->name ?? 'System' }}</td>
                <td>{{ class_basename($m->source_type) }} #{{ $m->source_id }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
