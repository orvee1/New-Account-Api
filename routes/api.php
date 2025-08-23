<?php

use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\API\ChartAccountController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyUserController;
use Illuminate\Http\Request;
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
    Route::post('/send-otp', [EmailVerificationController::class, 'sendOtp']);
    Route::post('/verify-otp',[EmailVerificationController::class, 'verifyOtp']);
    // CompanyCrud
    Route::get('/companies',[CompanyController::class,'index']);
    Route::get('/companies',[CompanyController::class,'store']);
    Route::post('/companies/{company}',[CompanyController::class,'store']);
    Route::get('/companies/{company}',[CompanyController::class,'show']);
    Route::put('/companies/{company}',[CompanyController::class,'update']);
    Route::delete('/companies/{company}',[CompanyController::class,'destroy']);
    // Company List
    Route::get('me/companies',[CompanyController::class, 'myCompanies']);

    // Company User Crud
    Route::get('companies/{company}/users',[CompanyUserController::class, 'index']);
    Route::post('companies/{company}/users',[CompanyUserController::class, 'store']);
    Route::put('companies/{company}/users',[CompanyUserController::class, 'update']);
    Route::delete('companies/{company}/users',[CompanyUserController::class, 'destroy']);
    // Users Primary Company
    Route::get('companies/{company}/users/{user}/primary', [CompanyUserController::class, 'setPrimary']);
    // Ownership Transfer
    Route::put('companies/{company}/owner/{user}', [CompanyUserController::class, 'transferOwnership']);
    // Chart of Accounts
    Route::get('/chart-accounts/options', [ChartAccountController::class, 'options']);
    Route::get('/chart-accounts/tree',    [ChartAccountController::class, 'tree']);

    Route::apiResource('chart-accounts', ChartAccountController::class)
        ->only(['index','store','show','update','destroy']);

    // Soft delete lifecycle
    Route::post('/chart-accounts/{id}/restore', [ChartAccountController::class, 'restore']);
    Route::delete('/chart-accounts/{id}/force', [ChartAccountController::class, 'forceDelete']);
});
Route::post('password/forgot', [ForgotPasswordController::class, 'sendResetOTP']);
Route::post('password/reset', [ResetPasswordController::class, 'reset']);
