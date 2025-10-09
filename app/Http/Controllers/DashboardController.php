<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\DebitCredit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $role = strtolower($user->getRoleNames()->first() ?? 'guest');

        // =========================
        // Which sections each role sees
        // =========================
        $sections = [
            'kpis'               => in_array($role, ['admin', 'manager']),
            'finance'            => in_array($role, ['admin']),
            'loans'              => in_array($role, ['admin', 'manager']),
            'charts'             => in_array($role, ['admin', 'manager']),
            'recentTransactions' => in_array($role, ['admin', 'manager']),
            'cashierDaily'       => in_array($role, ['cashier']),
        ];

        // =========================
        // Example statistics
        // =========================
        $today = Carbon::today();

        $totalSales       = Sale::sum('total_amount');
        $totalPurchases   = Purchase::sum('total_amount');
        $totalCredits     = DebitCredit::where('type', 'credit')->sum('amount');
        $totalDebits      = DebitCredit::where('type', 'debit')->sum('amount');
        $netBalance       = $totalCredits - $totalDebits;
        $totalLoansGiven  = Loan::where('type', 'given')->sum('amount');
        $totalLoansTaken  = Loan::where('type', 'taken')->sum('amount');
        $activeLoans      = Loan::where('status', 'active')->count();
        $paidLoans        = Loan::where('status', 'paid')->count();
        $totalLoanPayments = LoanPayment::sum('amount');

        $todaySalesTotal   = Sale::whereDate('created_at', $today)->sum('total_amount');
        $myTodaySalesTotal = Sale::where('user_id', $user->id)
                                 ->whereDate('created_at', $today)
                                 ->sum('total_amount');
        $myTodaySalesCount = Sale::where('user_id', $user->id)
                                 ->whereDate('created_at', $today)
                                 ->count();
        $myLatestSales     = Sale::where('user_id', $user->id)
                                 ->latest()
                                 ->take(5)
                                 ->get();

        $recentTransactions = DebitCredit::with(['user', 'customer', 'supplier'])
                                         ->latest()
                                         ->take(10)
                                         ->get();

        // =========================
        // Chart data (PostgreSQL-safe)
        // =========================
        $months = collect(range(1, 6))
            ->map(fn($m) => now()->subMonths(6 - $m)->format('M'));

        $salesTrend = Sale::selectRaw('EXTRACT(MONTH FROM created_at) AS month, SUM(total_amount) AS total')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderByRaw('MIN(created_at)')
            ->pluck('total');

        $purchaseTrend = Purchase::selectRaw('EXTRACT(MONTH FROM created_at) AS month, SUM(total_amount) AS total')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderByRaw('MIN(created_at)')
            ->pluck('total');

        return view('dashboard', compact(
            'role',
            'sections',
            'totalSales',
            'totalPurchases',
            'totalCredits',
            'totalDebits',
            'netBalance',
            'totalLoansGiven',
            'totalLoansTaken',
            'activeLoans',
            'paidLoans',
            'totalLoanPayments',
            'todaySalesTotal',
            'myTodaySalesTotal',
            'myTodaySalesCount',
            'myLatestSales',
            'recentTransactions',
            'months',
            'salesTrend',
            'purchaseTrend'
        ));
    }
}
