<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transactions Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h2>Transactions Report</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th><th>Type</th><th>Amount</th><th>Method</th>
                <th>User</th><th>Customer</th><th>Supplier</th><th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>{{ $t->transaction_date }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ number_format($t->amount, 2) }}</td>
                    <td>{{ $t->method ?? '-' }}</td>
                    <td>{{ $t->user->name ?? '-' }}</td>
                    <td>{{ $t->customer->name ?? '-' }}</td>
                    <td>{{ $t->supplier->name ?? '-' }}</td>
                    <td>{{ $t->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
