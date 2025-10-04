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

// 🔹 Public landing page
Route::get('/', function () {
    return view('welcome');
});

// ======================================================
// 🔐 AUTHENTICATED ROUTES
// ======================================================
Route::middleware(['auth'])->group(function () {

    // =======================
    // 🏠 Dashboard
    // =======================
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:admin,manager,cashier'); // All roles can view dashboard

    // =======================
    // 🧾 Finance & Operations
    // =======================
    // Debits & Credits (finance)
    Route::resource('debits-credits', DebitCreditController::class)
        ->middleware('role:admin,manager,cashier');

    // Transactions
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
    // 💰 Sales & Purchases
    // =======================
    Route::resource('purchases', PurchaseController::class)
        ->middleware('role:admin,manager');
    Route::resource('sales', SaleController::class)
        ->middleware('role:admin,manager,cashier');

    // =======================
    // 🏦 Loans Management
    // =======================
    Route::resource('loans', LoanController::class)
        ->middleware('role:admin,manager');
});
