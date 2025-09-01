<?php

use App\Http\Controllers\Admin\AdminDeviceLogController;
use App\Http\Controllers\Admin\AdminDeviceVerificationController;
use App\Http\Controllers\Admin\AdministratorController;

use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PermissionConroller;
use App\Http\Controllers\Admin\RoleConroller;
use App\Http\Controllers\Admin\AdminOtpSendController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\API\CompanyUserController;
use App\Http\Controllers\ProfileController;
use App\Models\Menu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'admin'], function () {
    // admin-device-verification
    Route::get('admin-device-verification', [AdminDeviceVerificationController::class, 'index'])->name('admin-device-verification.index');
    Route::get('admin-device-verification/create', [AdminDeviceVerificationController::class, 'create'])->name('admin-device-verification.create');
    Route::post('admin-device-verification', [AdminDeviceVerificationController::class, 'store'])->name('admin-device-verification.store');
    Route::post('admin-device-otp-send', [AdminDeviceVerificationController::class, 'otp_send'])->name('admin-device-verification.otp-send');
    Route::post('admin-device-verification-otp-store', [AdminDeviceVerificationController::class, 'check_otp_store'])->name('admin-device-verification.otp-store');
    Route::post('send-otp', [AdminOtpSendController::class, '@send_otp'])->name('admin.send-otp');
    Route::post('verify-otp', [AdminOtpSendController::class, 'verify_otp'])->name('admin.verify-otp');
    Route::post('change-password', [AdminOtpSendController::class, '@change_password'])->name('admin.change-password');
    Route::resource('companies', CompanyController::class);
    Route::post('companies/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])
        ->name('companies.toggle-status');
    Route::resource('company-users', CompanyUserController::class);
    Route::post('company-users/{companyUser}/toggle-status', [CompanyUserController::class, 'toggleStatus'])
        ->name('company-users.toggle-status');
    Route::post('company-users/{companyUser}/make-primary', [CompanyUserController::class, 'makePrimary'])
        ->name('company-users.make-primary');;
});

// 'admin.device.browser', 'admin' need to use for admin device
Route::group(['middleware' => ['auth'], 'prefix' => 'admin'], function () {
    Route::get('/', function () {
        $title = 'NEURON Admin : Dashboard';
        $menus = Cache::rememberForever('AdminPanelMenus', function () {
            return Menu::query()
                ->with('submenu.thirdmenu')
                ->mainMenu()
                ->orderBy('title')
                ->get();
        });
        return view('admin.dashboard', compact('title', 'menus'));
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::resource('administrator', AdministratorController::class)->middleware(['role:Administrator|Developer']);
    Route::resource('roles', RoleConroller::class)->middleware(['role:Administrator|Developer']);
    Route::resource('permissions', PermissionConroller::class)->middleware(['role:Administrator|Developer']);
    Route::resource('menus', MenuController::class)->middleware(['role:Administrator|Developer']);
    // Admin Device Log Routes
    Route::get('admin-device-log', [AdminDeviceLogController::class, 'index'])->middleware(['role:Administrator|Developer']);;
    Route::get('admin-device-log/{user}', [AdminDeviceLogController::class, 'show']);
    Route::put('admin-device-log/{admin_device}/requests/{admin_device_request}', [AdminDeviceLogController::class, 'acceptDeviceRequest'])
        ->name('admin-device.requests.update');
    Route::put('admin-device-log/{admin_device}/cancel-requests/{admin_device_request}', [AdminDeviceLogController::class, 'cancelDeviceRequest'])
        ->name('admin-device.requests.cancel');

});
require __DIR__ . '/auth.php';
