<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\DebitCredit;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // --- KPI summaries ---
        $totalSales       = Sale::sum('total_amount');
        $totalPurchases   = Purchase::sum('total_amount');
        $totalCredits     = DebitCredit::where('type', 'credit')->sum('amount');
        $totalDebits      = DebitCredit::where('type', 'debit')->sum('amount');
        $netBalance       = $totalCredits - $totalDebits;

        $totalLoansGiven  = Loan::where('type', 'given')->sum('amount');
        $totalLoansTaken  = Loan::where('type', 'taken')->sum('amount');
        $activeLoans      = Loan::where('status', 'pending')->count();
        $paidLoans        = Loan::where('status', 'paid')->count();
        $totalLoanPayments= LoanPayment::sum('amount');

        // --- Monthly trends (last 6 months) ---
        $months = collect(range(5,0))
            ->map(fn($i)=>Carbon::now()->subMonths($i)->format('M Y'));

        $salesTrend = [];
        $purchaseTrend = [];

        foreach ($months as $m) {
            $monthNum = Carbon::createFromFormat('M Y', $m)->month;
            $yearNum  = Carbon::createFromFormat('M Y', $m)->year;

            $salesTrend[] = Sale::whereYear('created_at',$yearNum)->whereMonth('created_at',$monthNum)->sum('total_amount');
            $purchaseTrend[] = Purchase::whereYear('created_at',$yearNum)->whereMonth('created_at',$monthNum)->sum('total_amount');
        }

        // --- Recent transactions ---
        $recentTransactions = DebitCredit::with(['customer','supplier','user'])
            ->latest()->take(10)->get();

        return view('dashboard.index', compact(
            'totalSales','totalPurchases','totalCredits','totalDebits','netBalance',
            'totalLoansGiven','totalLoansTaken','activeLoans','paidLoans','totalLoanPayments',
            'months','salesTrend','purchaseTrend','recentTransactions'
        ));
    }
}
