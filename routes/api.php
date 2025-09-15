<?php

// use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\API\ChartAccountController;
use App\Http\Controllers\API\CompanyUserController;
use App\Http\Controllers\Api\FixedAssetController;
use App\Http\Controllers\Api\ProductController;
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
Route::post('/register',[RegisterController::class, 'register']);
Route::post('/login',[LoginController::class, 'login']);
Route::middleware('auth:sanctum', 'verified')->group( function () {
    // Authentication
    Route::post('/logout',[LogoutController::class, 'logout']);
    // Route::post('/send-otp', [EmailVerificationController::class, 'sendOtp']);
    // Route::post('/verify-otp',[EmailVerificationController::class, 'verifyOtp']);
    // Chart of Accounts
    Route::apiResource('company-users', CompanyUserController::class);
    Route::post('company-users/{companyUser}/toggle-status', [CompanyUserController::class, 'toggleStatus'])
        ->name('api.admin.company-users.toggle-status');
    Route::post('company-users/{companyUser}/make-primary', [CompanyUserController::class, 'makePrimary'])
        ->name('api.admin.company-users.make-primary');
    Route::get('/chart-accounts/options', [ChartAccountController::class, 'options']);
    Route::get('/chart-accounts/tree',    [ChartAccountController::class, 'tree']);
    Route::apiResource('chart-accounts', ChartAccountController::class)
        ->only(['index','store','show','update','destroy']);

    // Soft delete lifecycle
    Route::post('/chart-accounts/{id}/restore', [ChartAccountController::class, 'restore']);
    Route::delete('/chart-accounts/{id}/force', [ChartAccountController::class, 'forceDelete']);

    Route::get('/products', [ProductController::class,'index']);
    Route::get('/products/{product}', [ProductController::class,'show']);
    Route::post('/products', [ProductController::class,'store']);
    Route::match(['put','patch'],'/products/{product}', [ProductController::class,'update']);
    Route::delete('/products/{product}', [ProductController::class,'destroy']);

    Route::apiResource('assets', FixedAssetController::class);
});
Route::post('password/forgot', [ForgotPasswordController::class, 'sendResetOTP']);
Route::post('password/reset', [ResetPasswordController::class, 'reset']);
