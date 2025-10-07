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
        $role = strtolower($user->roleNames()[0] ?? 'guest');

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
        // Safe defaults for all data
        // =========================
        $payload = [
            'role'               => $role,
            'sections'           => $sections,
            'months'             => collect(),
            'salesTrend'         => [],
            'purchaseTrend'      => [],
            'recentTransactions' => collect(),

            // Always defined numeric values
            'totalSales'         => 0,
            'totalPurchases'     => 0,
            'totalCredits'       => 0,
            'totalDebits'        => 0,
            'netBalance'         => 0,
            'totalLoansGiven'    => 0,
            'totalLoansTaken'    => 0,
            'activeLoans'        => 0,
            'paidLoans'          => 0,
            'totalLoanPayments'  => 0,
            'todaySalesTotal'    => 0,
            'myTodaySalesTotal'  => 0,
            'myTodaySalesCount'  => 0,
            'myLatestSales'      => collect(),
        ];

        // =========================
        // CASHIER DASHBOARD
        // =========================
        if ($sections['cashierDaily']) {
            $payload['todaySalesTotal'] = Sale::whereDate('created_at', today())->sum('total_amount');
            $payload['myTodaySalesTotal'] = Sale::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->sum('total_amount');
            $payload['myTodaySalesCount'] = Sale::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();
            $payload['myLatestSales'] = Sale::where('user_id', $user->id)
                ->latest()
                ->take(10)
                ->get();

            return view('dashboard.index', $payload);
        }

        // =========================
        // SHARED METRICS (ADMIN + MANAGER)
        // =========================
        $payload['months'] = collect(range(5, 0))
            ->map(fn($i) => Carbon::now()->subMonths($i)->format('M Y'));

        // Start base queries
        $salesQuery     = Sale::query();
        $purchaseQuery  = Purchase::query();
        $loanQuery      = Loan::query();
        $debitQuery     = DebitCredit::query();

        // =========================
        // Role-based data scoping
        // =========================

        if ($role === 'cashier') {
            $salesQuery->where('user_id', $user->id);
            $purchaseQuery->where('user_id', $user->id);
            $loanQuery->where('user_id', $user->id);
            $debitQuery->where('user_id', $user->id);
        }

        // Example: if you later add branch/team logic
        // if ($role === 'manager' && method_exists($user, 'managedUsers')) {
        //     $ids = $user->managedUsers->pluck('id');
        //     $salesQuery->whereIn('user_id', $ids);
        //     $purchaseQuery->whereIn('user_id', $ids);
        //     $loanQuery->whereIn('user_id', $ids);
        //     $debitQuery->whereIn('user_id', $ids);
        // }

        // =========================
        // KPIs
        // =========================
        if ($sections['kpis']) {
            $payload['totalSales']     = $salesQuery->sum('total_amount');
            $payload['totalPurchases'] = $purchaseQuery->sum('total_amount');
        }

        // =========================
        // Finance (Admin only)
        // =========================
        if ($sections['finance']) {
            $payload['totalCredits'] = DebitCredit::where('type', 'credit')->sum('amount');
            $payload['totalDebits']  = DebitCredit::where('type', 'debit')->sum('amount');
            $payload['netBalance']   = $payload['totalCredits'] - $payload['totalDebits'];
        }

        // =========================
        // Loans
        // =========================
        if ($sections['loans']) {
            $payload['totalLoansGiven']   = $loanQuery->where('type', 'given')->sum('amount');
            $payload['totalLoansTaken']   = Loan::where('type', 'taken')->sum('amount');
            $payload['activeLoans']       = $loanQuery->where('status', 'pending')->count();
            $payload['paidLoans']         = $loanQuery->where('status', 'paid')->count();
            $payload['totalLoanPayments'] = LoanPayment::sum('amount');
        }

        // =========================
        // Charts
        // =========================
        if ($sections['charts']) {
            $salesTrend = [];
            $purchaseTrend = [];

            foreach ($payload['months'] as $m) {
                $dt = Carbon::createFromFormat('M Y', $m);
                $salesTrend[] = (clone $salesQuery)
                    ->whereYear('created_at', $dt->year)
                    ->whereMonth('created_at', $dt->month)
                    ->sum('total_amount');
                $purchaseTrend[] = (clone $purchaseQuery)
                    ->whereYear('created_at', $dt->year)
                    ->whereMonth('created_at', $dt->month)
                    ->sum('total_amount');
            }

            $payload['salesTrend'] = $salesTrend;
            $payload['purchaseTrend'] = $purchaseTrend;
        }

        // =========================
        // Recent Transactions
        // =========================
        if ($sections['recentTransactions']) {
            $payload['recentTransactions'] = $debitQuery
                ->with(['customer', 'supplier', 'user'])
                ->latest()
                ->take(10)
                ->get();
        }

        // =========================
        // Return the view
        // =========================
        return view('dashboard.index', $payload);
    }
}
