<?php

use App\Http\Controllers\Api\AccountLedgerController;
use App\Http\Controllers\Api\AccountReconciliationController;
use App\Http\Controllers\Api\AssetDepreciationController;
use App\Http\Controllers\Api\AssetDisposalController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChartAccountController;
use App\Http\Controllers\Api\CompanyUserController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CreditNoteController;
use App\Http\Controllers\Api\DebitNoteController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\FixedAssetController;
use App\Http\Controllers\Api\ManualJournalController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PayrollRunController;
use App\Http\Controllers\Api\PayslipController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseBillController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\RecurringTransactionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SalarySetupController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\SalesInvoiceController;
use App\Http\Controllers\Api\SalesReturnController;
use App\Http\Controllers\Api\SalesPaymentController;
use App\Http\Controllers\Api\TransactionTransferController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\ContraController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        $user = $request->user(); // Sanctum token থেকে CompanyUser আসবে

        if (! $user) {
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

        // Account Ledger
        Route::get('accounts/{account}/ledger', [AccountLedgerController::class, 'show']);

        // Account Reconciliation
        Route::get('accounts/{account}/transactions-to-reconcile', [AccountReconciliationController::class, 'getTransactionsToReconcile']);
        Route::post('accounts/{account}/reconcile', [AccountReconciliationController::class, 'submitReconciliation']);
        Route::get('accounts/{account}/reconciliation-history', [AccountReconciliationController::class, 'getHistory']);
        Route::get('accounts/{account}/reconciliations/{reconciliation}', [AccountReconciliationController::class, 'show']);
        Route::delete('accounts/{account}/reconciliations/{reconciliation}', [AccountReconciliationController::class, 'destroy']);
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

    // Purchase Routes
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{purchaseOrder}/convert-to-bill', [PurchaseOrderController::class, 'convertToBill']);

    // Sales Routes
    Route::apiResource('sales-orders', SalesOrderController::class);
    Route::post('/sales-orders/{salesOrder}/convert-to-invoice', [SalesOrderController::class, 'convertToInvoice']);
    Route::apiResource('sales-invoices', SalesInvoiceController::class);
    Route::post('/sales-invoices/{salesInvoice}/create-return', [SalesInvoiceController::class, 'createReturn']);
    Route::post('/sales-invoices/{salesInvoice}/record-payment', [SalesInvoiceController::class, 'recordPayment']);
    Route::apiResource('sales-returns', SalesReturnController::class);
    Route::apiResource('sales-payments', SalesPaymentController::class);

    // Transactions
    Route::apiResource('receipts', ReceiptController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('contras', ContraController::class);
    Route::apiResource('debit-notes', DebitNoteController::class);
    Route::apiResource('credit-notes', CreditNoteController::class);
    Route::apiResource('manual-journals', ManualJournalController::class);
    Route::apiResource('recurring-transactions', RecurringTransactionController::class);
    Route::apiResource('transaction-transfers', TransactionTransferController::class);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('income-statement', [ReportController::class, 'incomeStatement']);
        Route::get('balance-sheet', [ReportController::class, 'balanceSheet']);
        Route::get('trial-balance', [ReportController::class, 'trialBalance']);
        Route::get('owners-equity', [ReportController::class, 'ownersEquity']);
        Route::get('stock-report', [ReportController::class, 'stockReport']);
        Route::get('cash-flow', [ReportController::class, 'cashFlow']);
        Route::get('vendor-ledger', [ReportController::class, 'vendorLedger']);
    });

    // ===== Payroll Management =====
    // Employees
    Route::apiResource('employees', EmployeeController::class);
    Route::post('employees/{id}/restore', [EmployeeController::class, 'restore'])
        ->name('employees.restore');

    // Salary Setup
    Route::apiResource('salary-setups', SalarySetupController::class);
    Route::post('salary-setups/{id}/restore', [SalarySetupController::class, 'restore'])
        ->name('salary-setups.restore');

    // Payroll Runs
    Route::apiResource('payroll-runs', PayrollRunController::class);
    Route::post('payroll-runs/{payrollRun}/process', [PayrollRunController::class, 'process'])
        ->name('payroll-runs.process');
    Route::post('payroll-runs/{payrollRun}/undo', [PayrollRunController::class, 'undo'])
        ->name('payroll-runs.undo');

    // Payslips
    Route::apiResource('payslips', PayslipController::class, ['only' => ['index', 'show']]);
    Route::get('payslips/employee/{employeeId}', [PayslipController::class, 'getByEmployee'])
        ->name('payslips.by-employee');
    Route::get('payslips/payroll-run/{payrollRunId}', [PayslipController::class, 'getByPayrollRun'])
        ->name('payslips.by-payroll-run');

    // Payroll Reports
    Route::prefix('payroll-reports')->group(function () {
        Route::get('monthly', [PayslipController::class, 'monthlyReport'])
            ->name('payroll-reports.monthly');
        Route::get('employee/{employeeId}', [PayslipController::class, 'employeeReport'])
            ->name('payroll-reports.employee');
        Route::get('department/{department}', [PayslipController::class, 'departmentReport'])
            ->name('payroll-reports.department');
    });
});
