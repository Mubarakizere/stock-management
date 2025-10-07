<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DebitCreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\Auth\RoleRedirectController;

// ======================================================
// 🔐 Redirect Root to Login
// ======================================================
Route::get('/', function () {
    return redirect()->route('login');
});

// ======================================================
// 🚦 Role-Based Redirect after Login (Laravel Breeze)
// ======================================================
Route::get('/redirect-by-role', RoleRedirectController::class)
    ->middleware(['auth'])
    ->name('redirect.by.role');

// ======================================================
// 🔐 Authenticated Routes (Protected by Auth & Verified)
// ======================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // =======================
    // 🏠 Dashboard
    // =======================
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:admin,manager,cashier');

    // =======================
    // 👥 Users (Admin only)
    // =======================
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', function () {
            return view('users.index'); // simple placeholder
        })->name('users.index');
    });

    // =======================
    // 🧾 Finance & Operations
    // =======================
    Route::resource('debit-credits', DebitCreditController::class)
        ->middleware('role:admin,manager,cashier');

    Route::get('/transactions', [TransactionController::class, 'index'])
        ->name('transactions.index')
        ->middleware('role:admin,manager');

    Route::get('/transactions/export/csv', [TransactionController::class, 'exportCsv'])
        ->name('transactions.export.csv')
        ->middleware('role:admin,manager');

    Route::get('/transactions/export/pdf', [TransactionController::class, 'exportPdf'])
        ->name('transactions.export.pdf')
        ->middleware('role:admin,manager');

    // =======================
    // 📦 Stock Management
    // =======================
    Route::resource('categories', CategoryController::class)
        ->middleware('role:admin,manager');

    Route::resource('products', ProductController::class)
        ->middleware('role:admin,manager,cashier');

    Route::resource('suppliers', SupplierController::class)
        ->middleware('role:admin,manager');

    Route::resource('customers', CustomerController::class)
        ->middleware('role:admin,manager,cashier');

    // =======================
    // 📊 Stock Movements
    // =======================
    Route::get('/stock-history', [StockMovementController::class, 'index'])
        ->name('stock.history')
        ->middleware('role:admin,manager,cashier');

    Route::get('/stock-history/export/csv', [StockMovementController::class, 'exportCsv'])
        ->name('stock.history.export.csv')
        ->middleware('role:admin,manager');

    Route::get('/stock-history/export/pdf', [StockMovementController::class, 'exportPdf'])
        ->name('stock.history.export.pdf')
        ->middleware('role:admin,manager');
    // =======================
// 📈 Reports (Admin only)
// =======================
Route::middleware('role:admin')->group(function () {
    Route::get('/reports', function () {
        return view('reports.index');
    })->name('reports.index');
});

    // =======================
    // 💰 Sales & Purchases
    // =======================
    Route::resource('purchases', PurchaseController::class)
        ->middleware('role:admin,manager');

    Route::resource('sales', SaleController::class)
        ->middleware('role:admin,manager,cashier');

    // 🧾 Invoices
    Route::get('/sales/{sale}/invoice', [SaleController::class, 'invoice'])
        ->name('sales.invoice')
        ->middleware('role:admin,manager,cashier');

    Route::get('/purchases/{purchase}/invoice', [PurchaseController::class, 'invoice'])
        ->name('purchases.invoice')
        ->middleware('role:admin,manager');

    // =======================
    // 🏦 Loans Management
    // =======================
    Route::resource('loans', LoanController::class)
        ->middleware('role:admin,manager');

    // =======================
    // 👤 User Profile (Breeze)
    // =======================
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

// ======================================================
// 🔁 Auth Routes (Breeze)
// ======================================================
require __DIR__.'/auth.php';
