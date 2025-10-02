<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user','customer','supplier','sale','purchase']);

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('transaction_date','desc')->paginate(10);

        $totalCredits = Transaction::where('type','credit')->sum('amount');
        $totalDebits = Transaction::where('type','debit')->sum('amount');

        return view('transactions.index', compact('transactions','totalCredits','totalDebits'));
    }

    // 🔹 CSV Export
    public function exportCsv()
    {
        $transactions = Transaction::with(['user','customer','supplier'])->get();

        $filename = "transactions_" . now()->format('Y-m-d_H-i-s') . ".csv";
        $handle = fopen($filename, 'w+');

        // Headings
        fputcsv($handle, ['Date','Type','Amount','Method','User','Customer','Supplier','Notes']);

        // Data
        foreach ($transactions as $t) {
            fputcsv($handle, [
                $t->transaction_date,
                ucfirst($t->type),
                $t->amount,
                $t->method ?? '-',
                $t->user->name ?? '-',
                $t->customer->name ?? '-',
                $t->supplier->name ?? '-',
                $t->notes ?? '-',
            ]);
        }

        fclose($handle);

        return response()->download($filename)->deleteFileAfterSend(true);
    }

    // 🔹 PDF Export
    public function exportPdf()
    {
        $transactions = Transaction::with(['user','customer','supplier'])->get();

        $pdf = Pdf::loadView('transactions.export-pdf', compact('transactions'));
        return $pdf->download('transactions_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
}
