<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::middleware('auth:sanctum', 'verified')->group(function () {
    // Authentication
    Route::post('/logout', [LogoutController::class, 'logout']);
    // Route::post('/send-otp', [EmailVerificationController::class, 'sendOtp']);
    // Route::post('/verify-otp',[EmailVerificationController::class, 'verifyOtp']);
    // Chart of Accounts
    Route::apiResource('company-users', CompanyUserController::class);
    Route::post('company-users/{companyUser}/toggle-status', [CompanyUserController::class, 'toggleStatus'])
        ->name('api.admin.company-users.toggle-status');
    Route::post('company-users/{companyUser}/make-primary', [CompanyUserController::class, 'makePrimary'])
        ->name('api.admin.company-users.make-primary');
    Route::get('/chart-accounts/options', [ChartAccountController::class, 'options']);
    Route::apiResource('chart-accounts', ChartAccountController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    // Soft delete lifecycle
    Route::post('/chart-accounts/{id}/restore', [ChartAccountController::class, 'restore']);
    Route::delete('/chart-accounts/{id}/force', [ChartAccountController::class, 'forceDelete']);
    //porducts
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    // Fixed Asset Route
    Route::apiResource('fixed-assets', FixedAssetController::class);
    // Asset Depreciation Route
    Route::apiResource('asset-depreciations', AssetDepreciationController::class);
    // Asset Disposal Route
    Route::apiResource('asset-disposals', AssetDisposalController::class);

    // Purchase Routes
    Route::apiResource('purchase-bills', PurchaseBillController::class);
    Route::apiResource('purchase-returns', PurchaseReturnController::class);
    // SalesInvoice Routes
    Route::apiResource('sales-invoices', SalesInvoiceController::class);
    Route::post('sales-invoices/{salesInvoice}/post', [SalesInvoiceController::class, 'post']);
    Route::post('sales-invoices/{salesInvoice}/void', [SalesInvoiceController::class, 'void']);
    // Sales Estimate Routes
    Route::apiResource('estimates', EstimateController::class);
    Route::post('estimates/{estimate}/finalize', [EstimateController::class, 'finalize']);
    // Sales Order Routes
    Route::apiResource('sales-orders', SalesOrderController::class);
    Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm']);
    Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel']);
    // Sales Return Routes
    Route::apiResource('sales-returns', SalesReturnController::class);
    Route::post('sales-returns/{salesReturn}/post', [SalesReturnController::class, 'post']);
    Route::post('sales-returns/{salesReturn}/unpost', [SalesReturnController::class, 'unpost']);
    // vendors
    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('/warehouses/{warehouse}/make-default', [WarehouseController::class, 'makeDefault']);
    // RESTful CRUD (index, store, show, update, destroy)
    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/{customer}/restore', [CustomerController::class, 'restore'])
        ->name('customers.restore');
});
Route::post('password/forgot', [ForgotPasswordController::class, 'sendResetOTP']);
Route::post('password/reset', [ResetPasswordController::class, 'reset']);
