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

        /**
         * Which sections should appear for this role?
         * Adjust as needed.
         */
        $sections = [
            'kpis'               => in_array($role, ['admin', 'manager']),
            'finance'            => in_array($role, ['admin']),            // net balance, credits/debits
            'loans'              => in_array($role, ['admin', 'manager']), // loans & payments
            'charts'             => in_array($role, ['admin', 'manager']), // monthly trends
            'recentTransactions' => in_array($role, ['admin', 'manager']),
            'cashierDaily'       => in_array($role, ['cashier']),          // cashier-focused summary
        ];

        // ---------- Common/time helpers ----------
        $months = collect(range(5, 0))
            ->map(fn ($i) => Carbon::now()->subMonths($i)->format('M Y'));

        // Prepare payload with safe defaults
        $payload = [
            'role'               => $role,
            'sections'           => $sections,
            'months'             => $months,
            'salesTrend'         => [],
            'purchaseTrend'      => [],
            'recentTransactions' => collect(),
        ];

        // ---------- Role: Cashier (focused, minimal, scoped to user) ----------
        if ($sections['cashierDaily']) {
            $payload['todaySalesTotal'] = Sale::whereDate('created_at', today())->sum('total_amount');
            $payload['myTodaySalesTotal'] = Sale::whereDate('created_at', today())
                ->where('user_id', $user->id)
                ->sum('total_amount');
            $payload['myTodaySalesCount'] = Sale::whereDate('created_at', today())
                ->where('user_id', $user->id)
                ->count();

            // Optionally a small list for quick view
            $payload['myLatestSales'] = Sale::where('user_id', $user->id)
                ->latest()
                ->take(10)
                ->get();

            // Cashier returns early with only what’s needed
            return view('dashboard.index', $payload);
        }

        // ---------- Roles: Admin / Manager ----------
        // KPIs (visible to admin, manager)
        if ($sections['kpis']) {
            $payload['totalSales']     = Sale::sum('total_amount');
            $payload['totalPurchases'] = Purchase::sum('total_amount');
        }

        // Finance block (admin only)
        if ($sections['finance']) {
            $payload['totalCredits'] = DebitCredit::where('type', 'credit')->sum('amount');
            $payload['totalDebits']  = DebitCredit::where('type', 'debit')->sum('amount');
            $payload['netBalance']   = ($payload['totalCredits'] ?? 0) - ($payload['totalDebits'] ?? 0);
        }

        // Loans block (admin, manager)
        if ($sections['loans']) {
            $payload['totalLoansGiven']   = Loan::where('type', 'given')->sum('amount');
            $payload['totalLoansTaken']   = Loan::where('type', 'taken')->sum('amount');
            $payload['activeLoans']       = Loan::where('status', 'pending')->count();
            $payload['paidLoans']         = Loan::where('status', 'paid')->count();
            $payload['totalLoanPayments'] = LoanPayment::sum('amount');
        }

        // Charts (admin, manager) — monthly trends last 6 months
        if ($sections['charts']) {
            $salesTrend = [];
            $purchaseTrend = [];

            foreach ($months as $m) {
                $dt = Carbon::createFromFormat('M Y', $m);
                $yearNum  = $dt->year;
                $monthNum = $dt->month;

                $salesTrend[] = Sale::whereYear('created_at', $yearNum)
                    ->whereMonth('created_at', $monthNum)
                    ->sum('total_amount');

                $purchaseTrend[] = Purchase::whereYear('created_at', $yearNum)
                    ->whereMonth('created_at', $monthNum)
                    ->sum('total_amount');
            }

            $payload['salesTrend'] = $salesTrend;
            $payload['purchaseTrend'] = $purchaseTrend;
        }

        // Recent transactions (admin, manager)
        if ($sections['recentTransactions']) {
            $payload['recentTransactions'] = DebitCredit::with(['customer', 'supplier', 'user'])
                ->latest()
                ->take(10)
                ->get();
        }

        return view('dashboard.index', $payload);
    }
}
