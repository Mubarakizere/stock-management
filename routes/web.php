<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;

Route::get('/', function () {
    return view('welcome');
});
Route::resource('categories', CategoryController::class);
Route::resource('products',ProductController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('customers', CustomerController::class);
