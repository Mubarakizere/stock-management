<?php

namespace App\Http\Controllers;

use App\Models\{Sale, SaleItem, Purchase, Loan, LoanPayment, DebitCredit, Product, Expense, Category, Production};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Auth, Schema, Log};
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        try {
            $user = Auth::user();
            $role = strtolower($user->getRoleNames()->first() ?? 'guest');

            $sections = [
                'kpis'               => in_array($role, ['admin','manager','accountant']),
                'finance'            => in_array($role, ['admin','accountant']),
                'loans'              => in_array($role, ['admin','manager','accountant']),
                'charts'             => in_array($role, ['admin','manager','accountant']),
                'recentTransactions' => in_array($role, ['admin','manager','accountant']),
                'cashierDaily'       => in_array($role, ['cashier']),
                'insights'           => in_array($role, ['admin','manager']),
                'expenses'           => in_array($role, ['admin','manager','accountant']),
            ];

            $today      = Carbon::today();
            $monthStart = now()->startOfMonth();
            $weekStart  = now()->startOfWeek();

            // ========== TOTALS ==========
            $totalSales      = $this->safeSum(Sale::class, 'total_amount');
            $totalPurchases  = $this->safeSum(Purchase::class, 'total_amount');
            $totalProfit     = $this->safeSum(SaleItem::class, DB::raw('COALESCE(profit, (unit_price - cost_price) * quantity)'));
            $pendingBalances = $this->safeSum(
                Sale::where('status', 'pending'),
                DB::raw('total_amount - COALESCE(amount_paid, 0)')
            );

            // ========== LEDGER ==========
            $totalCredits = $this->safeSum(DebitCredit::where('type', 'credit'), 'amount');
            $totalDebits  = $this->safeSum(DebitCredit::where('type', 'debit'), 'amount');
            $netBalance   = $totalCredits - $totalDebits;

            // ========== EXPENSES ==========
            $totalExpenses = $this->safeSum(Expense::class, 'amount');
            $todayExpenses = $this->safeSum(Expense::whereDate('date', $today), 'amount');
            $weekExpenses  = $this->safeSum(Expense::where('date', '>=', $weekStart), 'amount');
            $monthExpenses = $this->safeSum(Expense::where('date', '>=', $monthStart), 'amount');

            // ========== LOANS ==========
            $totalLoansGiven   = $this->safeSum(Loan::where('type', 'given'), 'amount');
            $totalLoansTaken   = $this->safeSum(Loan::where('type', 'taken'), 'amount');
            $activeLoans       = Loan::where('status', '!=', 'paid')->count();
            $overdueLoans      = Loan::where('status', '!=', 'paid')
                                     ->whereNotNull('due_date')
                                     ->where('due_date', '<', now())
                                     ->count();
            $paidLoans         = Loan::where('status', 'paid')->count();
            $totalLoanPayments = $this->safeSum(LoanPayment::class, 'amount');

            // ========== TIME-BASED SALES ==========
            $todaySales     = $this->safeSum(Sale::whereDate('created_at', $today), 'total_amount');
            $monthSales     = $this->safeSum(Sale::where('created_at', '>=', $monthStart), 'total_amount');
            $weekSales      = $this->safeSum(Sale::where('created_at', '>=', $weekStart), 'total_amount');
            $yesterdaySales = $this->safeSum(Sale::whereDate('created_at', $today->copy()->subDay()), 'total_amount');
            $salesChange    = $yesterdaySales > 0 ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 : 0;

            // ========== STOCK ==========
            $totalStockValue = $this->calculateStockValue();

            // ========== MY DAY (for cashiers) ==========
            $myTodaySalesTotal = $this->safeSum(
                Sale::where('user_id', $user->id)->whereDate('created_at', $today),
                'total_amount'
            );
            $myTodaySalesCount = Sale::where('user_id', $user->id)
                                    ->whereDate('created_at', $today)
                                    ->count();
            $myLatestSales = Sale::where('user_id', $user->id)
                                ->with(['customer:id,name'])
                                ->latest()
                                ->take(5)
                                ->get();

            // ========== RECENT TRANSACTIONS ==========
            $recentTransactions = DebitCredit::with(['user:id,name', 'customer:id,name', 'supplier:id,name'])
                                            ->latest()
                                            ->take(10)
                                            ->get();

            // ========== CHART DATA ==========
            [$chartLabels, $chartSales] = $this->getSalesChartData(30);
            [$months, $salesTrend, $purchaseTrend] = $this->getTrendData(6);

            // ========== INSIGHTS ==========
            $topProducts = $this->getTopProducts(5);
            $topCustomers = $this->getTopCustomers(5);

            // ========== ACTIVE LOANS ==========
            $activeLoansList = $this->getActiveLoans(8);

            // ========== EXPENSE BY CATEGORY ==========
            $expenseByCategory = $this->getExpensesByCategory($monthStart);
            $recentExpenses = Expense::with(['category:id,name', 'creator:id,name', 'supplier:id,name'])
                                    ->orderByDesc('date')
                                    ->take(8)
                                    ->get();

            // ========== RAW MATERIALS ==========
            $rawMaterialsTotal     = Product::rawMaterials()->count();
            $rawMaterialsLowStock  = 0;
            $rawMaterialsStockValue = 0.0;
            $lowStockRawMaterials  = collect();
            try {
                $allRawMaterials = Product::rawMaterials()->get(['id', 'name', 'cost_price']);
                foreach ($allRawMaterials as $rm) {
                    $stock = $rm->currentStock();
                    $rm->current_stock = $stock;
                    $rm->stock_value   = round($stock * (float)$rm->cost_price, 2);
                    $rawMaterialsStockValue += $rm->stock_value;
                    if ($rm->isLowStock()) $rawMaterialsLowStock++;
                }
                $lowStockRawMaterials = $allRawMaterials
                    ->filter(fn($rm) => $rm->isLowStock())
                    ->sortBy('current_stock')
                    ->take(6)
                    ->values();
            } catch (\Exception $e) {
                Log::warning('Raw material stats failed: ' . $e->getMessage());
            }

            // ========== PRODUCTIONS ==========
            $totalProductions      = 0;
            $monthProductions      = 0;
            $recentProductions     = collect();
            try {
                $totalProductions  = Production::count();
                $monthProductions  = Production::where('created_at', '>=', $monthStart)->count();
                $recentProductions = Production::with('product:id,name')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'product_id', 'quantity', 'created_at', 'status']);
            } catch (\Exception $e) {
                Log::warning('Production stats failed: ' . $e->getMessage());
            }

            return view('dashboard', compact(
                'role', 'sections',
                'totalSales', 'totalPurchases', 'totalProfit', 'pendingBalances',
                'totalCredits', 'totalDebits', 'netBalance',
                'totalExpenses', 'todayExpenses', 'weekExpenses', 'monthExpenses',
                'totalLoansGiven', 'totalLoansTaken', 'activeLoans', 'overdueLoans', 'paidLoans', 'totalLoanPayments',
                'todaySales', 'monthSales', 'weekSales', 'salesChange',
                'totalStockValue',
                'myTodaySalesTotal', 'myTodaySalesCount', 'myLatestSales',
                'recentTransactions',
                'months', 'salesTrend', 'purchaseTrend', 'chartLabels', 'chartSales',
                'topProducts', 'topCustomers',
                'expenseByCategory', 'recentExpenses', 'activeLoansList',
                'rawMaterialsTotal', 'rawMaterialsLowStock', 'rawMaterialsStockValue', 'lowStockRawMaterials',
                'totalProductions', 'monthProductions', 'recentProductions'
            ));

        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return view('dashboard')->with([
                'error' => 'Unable to load dashboard data. Please check logs.',
                'role' => 'guest',
                'sections' => []
            ]);
        }
    }

    // ========== HELPER METHODS ==========

    /**
     * Safely sum values, returning 0 on error
     */
    private function safeSum($query, $column = 'amount'): float
    {
        try {
            if (is_string($query)) {
                $query = $query::query();
            }
            return (float) $query->sum($column);
        } catch (\Exception $e) {
            Log::warning("Sum failed for {$column}: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calculate total stock value
     */
    private function calculateStockValue(): float
    {
        try {
            // Calculate stock value: sum((in - out) * cost_price)
            // We use a direct DB query for performance
            $value = DB::table('stock_movements')
                ->join('products', 'stock_movements.product_id', '=', 'products.id')
                ->selectRaw('SUM(CASE WHEN stock_movements.type = ? THEN stock_movements.quantity ELSE -stock_movements.quantity END * products.cost_price) as total', ['in'])
                ->value('total');

            return (float) max(0, $value);
        } catch (\Exception $e) {
            Log::warning('Stock value calculation failed: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get sales chart data for last N days
     */
    private function getSalesChartData(int $days = 30): array
    {
        try {
            $start = now()->subDays($days)->startOfDay();
            $end   = now()->endOfDay();

            // Get actual sales grouped by date
            $raw = Sale::select(
                        DB::raw('DATE(created_at) as d'),
                        DB::raw('SUM(total_amount) as s')
                    )
                    ->where('created_at', '>=', $start)
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->orderBy('d')
                    ->pluck('s', 'd');

            // Generate ALL days (zero-fill missing ones)
            $labels = collect();
            $sales  = collect();
            for ($d = $start->copy(); $d <= $end; $d->addDay()) {
                $key = $d->toDateString();
                $labels->push(Carbon::parse($key)->format('M d'));
                $sales->push((float) ($raw[$key] ?? 0));
            }

            return [$labels, $sales];
        } catch (\Exception $e) {
            Log::warning('Sales chart data failed: ' . $e->getMessage());
            return [collect(), collect()];
        }
    }

    /**
     * Get trend data for last N months
     */
    private function getTrendData(int $months = 6): array
    {
        try {
            $startDate = now()->subMonths($months)->startOfMonth();

            // Generate month labels and keys
            $monthLabels = collect();
            $monthKeys   = collect();
            for ($i = 1; $i <= $months; $i++) {
                $d = now()->subMonths($months - $i);
                $monthLabels->push($d->format('M'));
                $monthKeys->push($d->format('Y-m'));
            }

            // Get aggregated sales by month
            $salesRaw = Sale::selectRaw("to_char(created_at, 'YYYY-MM') as m, SUM(total_amount) as t")
                            ->where('created_at', '>=', $startDate)
                            ->groupBy('m')
                            ->orderBy('m')
                            ->pluck('t', 'm');

            // Get aggregated purchases by month
            $purchaseRaw = Purchase::selectRaw("to_char(created_at, 'YYYY-MM') as m, SUM(total_amount) as t")
                                   ->where('created_at', '>=', $startDate)
                                   ->groupBy('m')
                                   ->orderBy('m')
                                   ->pluck('t', 'm');

            // Map to correct positions using month keys
            $salesTrend    = $monthKeys->map(fn($k) => (float) ($salesRaw[$k] ?? 0));
            $purchaseTrend = $monthKeys->map(fn($k) => (float) ($purchaseRaw[$k] ?? 0));

            return [$monthLabels, $salesTrend, $purchaseTrend];
        } catch (\Exception $e) {
            Log::warning('Trend data failed: ' . $e->getMessage());
            $empty = collect(array_fill(0, $months, 0));
            return [collect(), $empty, $empty];
        }
    }

    /**
     * Get top selling products
     */
    private function getTopProducts(int $limit = 5)
    {
        try {
            return SaleItem::select(
                        'product_id',
                        DB::raw('SUM(quantity) as total_qty'),
                        DB::raw('SUM(subtotal) as total_revenue')
                    )
                    ->groupBy('product_id')
                    ->orderByDesc('total_qty')
                    ->take($limit)
                    ->with('product:id,name')
                    ->get();
        } catch (\Exception $e) {
            Log::warning('Top products failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get top customers
     */
    private function getTopCustomers(int $limit = 5)
    {
        try {
            return Sale::select(
                        'customer_id',
                        DB::raw('SUM(total_amount) as total_spent')
                    )
                    ->whereNotNull('customer_id')
                    ->groupBy('customer_id')
                    ->orderByDesc('total_spent')
                    ->take($limit)
                    ->with('customer:id,name')
                    ->get();
        } catch (\Exception $e) {
            Log::warning('Top customers failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get active loans with payment sums
     */
    private function getActiveLoans(int $limit = 8)
    {
        try {
            return Loan::with(['customer:id,name', 'supplier:id,name'])
                      ->withSum('payments as paid_sum', 'amount')
                      ->where('status', '!=', 'paid')
                      ->latest()
                      ->take($limit)
                      ->get()
                      ->map(function ($loan) {
                          $paid = (float) ($loan->paid_sum ?? 0);
                          $loan->paid_sum = $paid;
                          $loan->remaining = max(0, (float) $loan->amount - $paid);
                          return $loan;
                      });
        } catch (\Exception $e) {
            Log::warning('Active loans failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get expenses grouped by category
     */
    private function getExpensesByCategory($fromDate): array
    {
        try {
            $result = ['labels' => [], 'amounts' => []];

            if (!Schema::hasTable('categories')) {
                return $result;
            }

            $data = Expense::join('categories', 'categories.id', '=', 'expenses.category_id')
                          ->where('expenses.date', '>=', $fromDate)
                          ->groupBy('categories.id', 'categories.name')
                          ->orderByDesc(DB::raw('SUM(expenses.amount)'))
                          ->select([
                              'categories.name',
                              DB::raw('SUM(expenses.amount) as total')
                          ])
                          ->get();

            $result['labels'] = $data->pluck('name')->toArray();
            $result['amounts'] = $data->pluck('total')->toArray();

            // Add uncategorized expenses
            $uncategorized = Expense::whereNull('category_id')
                                   ->where('date', '>=', $fromDate)
                                   ->sum('amount');

            if ($uncategorized > 0) {
                $result['labels'][] = 'Uncategorized';
                $result['amounts'][] = $uncategorized;
            }

            return $result;
        } catch (\Exception $e) {
            Log::warning('Expense category data failed: ' . $e->getMessage());
            return ['labels' => [], 'amounts' => []];
        }
    }

    // ========== API ENDPOINTS ==========

    public function salesChartData()
    {
        [$labels, $sales] = $this->getSalesChartData(30);

        return response()->json([
            'labels' => $labels,
            'sales' => $sales,
        ]);
    }

    public function cashflowChartData()
    {
        try {
            $start = now()->subDays(30)->startOfDay();
            $end = now()->endOfDay();

            // Generate all dates
            $labels = collect();
            for ($d = $start->copy(); $d <= $end; $d->addDay()) {
                $labels->push($d->copy());
            }

            // Get aggregated data
            $credits = DebitCredit::select(
                            DB::raw('DATE(created_at) as d'),
                            DB::raw('SUM(amount) as t')
                        )
                        ->where('type', 'credit')
                        ->whereBetween('created_at', [$start, $end])
                        ->groupBy(DB::raw('DATE(created_at)'))
                        ->pluck('t', 'd');

            $debits = DebitCredit::select(
                            DB::raw('DATE(created_at) as d'),
                            DB::raw('SUM(amount) as t')
                        )
                        ->where('type', 'debit')
                        ->whereBetween('created_at', [$start, $end])
                        ->groupBy(DB::raw('DATE(created_at)'))
                        ->pluck('t', 'd');

            return response()->json([
                'labels' => $labels->map(fn($d) => $d->format('M d')),
                'inflow' => $labels->map(fn($d) => (float) ($credits[$d->toDateString()] ?? 0)),
                'outflow' => $labels->map(fn($d) => (float) ($debits[$d->toDateString()] ?? 0)),
            ]);
        } catch (\Exception $e) {
            Log::error('Cashflow chart failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load cashflow data'], 500);
        }
    }
}
