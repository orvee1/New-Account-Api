<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;

use App\Http\Controllers\Api\CompanyUserController;
use App\Http\Controllers\Api\ChartAccountController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\FixedAssetController;
use App\Http\Controllers\Api\AssetDepreciationController;
use App\Http\Controllers\Api\AssetDisposalController;
use App\Http\Controllers\Api\PurchaseBillController;
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\CustomerController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

Route::post('password/forgot', [ForgotPasswordController::class, 'sendResetOTP']);
Route::post('password/reset', [ResetPasswordController::class, 'reset']);

/*
|--------------------------------------------------------------------------
| Protected Routes (auth:sanctum, verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

     Route::get('/user', function (Request $request) {
        /** @var \App\Models\CompanyUser|null $user */
        $user = $request->user();   // Sanctum token থেকে CompanyUser আসবে

        if (!$user) {
            return response()->json(['user' => null], 200);
        }

        // company relation সহ পাঠাতে চাইলে
        $user->load('company:id,name');

        return response()->json([
            'user' => $user,
        ]);
    });

    Route::get('/user/permissions', function (Request $request) {
        /** @var \App\Models\CompanyUser|null $user */
        $user = $request->user();

        return response()->json([
            'permissions' => $user?->permissions ?? [],
        ]);
    });

    // Auth
    Route::post('/logout', [LogoutController::class, 'logout']);

    // Company users
    Route::apiResource('company-users', CompanyUserController::class);
    Route::post('company-users/{companyUser}/toggle-status', [CompanyUserController::class, 'toggleStatus'])
        ->name('api.admin.company-users.toggle-status');
    Route::post('company-users/{companyUser}/make-primary', [CompanyUserController::class, 'makePrimary'])
        ->name('api.admin.company-users.make-primary');

    // ===== Chart of Accounts (generic options) =====
    Route::get('/chart-accounts/options', [ChartAccountController::class, 'options']);

    // ===== Company wise Chart of Accounts =====
    Route::prefix('companies/{company}')->group(function () {
        // index + store
        Route::get('chart-accounts', [ChartAccountController::class, 'index']);
        Route::post('chart-accounts', [ChartAccountController::class, 'store']);

        // show / update / delete নির্দিষ্ট নোডের জন্য
        Route::get('chart-accounts/{chartAccount}', [ChartAccountController::class, 'show']);
        Route::put('chart-accounts/{chartAccount}', [ChartAccountController::class, 'update']);
        Route::delete('chart-accounts/{chartAccount}', [ChartAccountController::class, 'destroy']);

        // soft-delete lifecycle
        Route::post('chart-accounts/{chartAccount}/restore', [ChartAccountController::class, 'restore']);
        Route::delete('chart-accounts/{chartAccount}/force', [ChartAccountController::class, 'forceDelete']);
    });

    // Products
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);

    // Fixed Asset
    Route::apiResource('assets', FixedAssetController::class);
    Route::apiResource('asset-depreciations', AssetDepreciationController::class);
    Route::apiResource('asset-disposals', AssetDisposalController::class);

    // Purchase
    Route::apiResource('purchase-bills', PurchaseBillController::class);
    Route::apiResource('purchase-returns', PurchaseReturnController::class);

    // Vendors / Warehouses
    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('warehouses/{warehouse}/make-default', [WarehouseController::class, 'makeDefault']);

    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/{customer}/restore', [CustomerController::class, 'restore'])
        ->name('customers.restore');
});
