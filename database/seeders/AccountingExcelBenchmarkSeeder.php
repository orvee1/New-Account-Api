<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductUom;
use App\Models\Receipt;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\AccountingPostingService;
use App\Services\PartyAccountService;
use App\Services\ProductService;
use App\Services\PurchaseService;
use App\Services\SalesInvoiceService;
use App\Services\SalesReturnService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountingExcelBenchmarkSeeder extends Seeder
{
    private int $companyId = 1;
    private CompanyUser $user;
    private Warehouse $warehouse;
    private Category $category;
    private Brand $brand;
    private UnitOfMeasure $piece;
    private Product $laptop;
    private Product $keyboard;
    private Vendor $techSupplier;
    private Vendor $globalEnterprise;
    private Customer $rahmanTrading;
    private Customer $standardStore;

    public function run(): void
    {
        $this->prepareSystemSetup();
        $this->prepareCleanCompany();
        $this->authenticateSeederUser();
        $this->cleanupUnusedBenchmarkAccounts();
        $this->prepareMasters();
        $this->seedProducts();
        $this->seedParties();
        $this->seedPurchaseSaleReturnFlow();
        $this->seedReceiptsAndPayments();
        $this->syncExpectedStockSnapshot();
        $this->printSummary();
    }

    private function prepareCleanCompany(): void
    {
        Artisan::call('accounting:reset-company-data', [
            '--company' => $this->companyId,
            '--force' => true,
        ]);
    }

    private function prepareSystemSetup(): void
    {
        User::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'System User',
                'email' => 'system@example.test',
                'phone_number' => '00000000000',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        Company::query()->updateOrCreate(
            ['id' => $this->companyId],
            ['name' => 'Default Company', 'status' => 'active']
        );

        CompanyUser::query()->updateOrCreate(
            ['email' => 'accounting-excel@example.test'],
            [
                'company_id' => $this->companyId,
                'name' => 'Accounting Excel User',
                'phone_number' => '00000000000',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'is_primary' => true,
            ]
        );

        if (! ChartAccount::query()->where('company_id', $this->companyId)->exists()) {
            $this->call(DefaultChartOfAccountsSeeder::class);
        }
    }

    private function authenticateSeederUser(): void
    {
        $this->user = CompanyUser::query()
            ->where('company_id', $this->companyId)
            ->orderBy('id')
            ->firstOrFail();

        Auth::login($this->user);
        Auth::guard('sanctum')->setUser($this->user);
    }

    private function cleanupUnusedBenchmarkAccounts(): void
    {
        $usedAccountIds = DB::table('journal_lines')
            ->whereNotNull('account_id')
            ->select('account_id');

        ChartAccount::query()
            ->where('company_id', $this->companyId)
            ->where('type', 'ledger')
            ->where('slug', 'account-receivable')
            ->where('code', '<>', '1.1.4.1.1')
            ->whereNotIn('id', $usedAccountIds)
            ->delete();

        foreach ([['1.1.4.1', 'Customer - %'], ['2.1.1.1', 'Vendor - %']] as [$groupCode, $nameLike]) {
            $group = ChartAccount::query()
                ->where('company_id', $this->companyId)
                ->where('code', $groupCode)
                ->first();

            if (! $group) {
                continue;
            }

            ChartAccount::query()
                ->where('company_id', $this->companyId)
                ->where('parent_id', $group->id)
                ->where('type', 'ledger')
                ->where('name', 'like', $nameLike)
                ->whereNotIn('id', DB::table('journal_lines')->whereNotNull('account_id')->select('account_id'))
                ->delete();
        }
    }

    private function prepareMasters(): void
    {
        $this->warehouse = Warehouse::query()->firstOrCreate(
            ['company_id' => $this->companyId, 'name' => 'Main Warehouse'],
            ['is_default' => true]
        );

        $this->category = Category::query()->firstOrCreate(
            ['company_id' => $this->companyId, 'name' => 'General'],
            ['status' => 'active']
        );

        $this->brand = Brand::query()->firstOrCreate(
            ['company_id' => $this->companyId, 'name' => 'Generic Brand'],
            ['status' => 'active']
        );

        $this->piece = UnitOfMeasure::query()->firstOrCreate(
            ['name' => 'Piece'],
            ['symbol' => 'pcs']
        );
    }

    private function seedProducts(): void
    {
        $this->laptop = $this->createStockProduct('Laptop', 'EXCEL-LAPTOP', 0, 0, 65000);
        $this->keyboard = $this->createStockProduct('Keyboard', 'EXCEL-KEYBOARD', 10, 500, 800);
    }

    private function createStockProduct(string $name, string $sku, float $openingQty, float $openingCost, float $salePrice): Product
    {
        $product = app(ProductService::class)->create([
            'product_type' => 'Stock',
            'name' => $name,
            'sku' => $sku,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'warehouse_id' => $this->warehouse->id,
            'unit' => 'pcs',
            'costing_price' => $openingCost,
            'sales_price' => $salePrice,
            'tax_percent' => 0,
            'vat_rate' => 0,
            'vat_inclusive' => false,
            'ait_rate' => 0,
            'opening_quantity' => $openingQty,
            'opening_unit_cost' => $openingCost,
            'opening_date' => '2026-08-01',
            'product_uoms' => [[
                'uom_id' => $this->piece->id,
                'conversion_factor' => 1,
                'sale_price' => $salePrice,
                'is_base_uom' => true,
                'is_default_sale_uom' => true,
            ]],
        ]);

        ProductUnit::query()->firstOrCreate(
            ['product_id' => $product->id, 'name' => 'Piece'],
            ['factor' => 1, 'is_base' => true]
        );

        return $product->refresh();
    }

    private function seedParties(): void
    {
        $partyAccounts = app(PartyAccountService::class);

        $this->techSupplier = $this->createVendor('Tech Supplier', 'V-EXCEL-001', 0);
        $this->globalEnterprise = $this->createVendor('Global Enterprise', 'V-EXCEL-002', 20000);
        $this->rahmanTrading = $this->createCustomer('Rahman Trading', 'C-EXCEL-001', 0);
        $this->standardStore = $this->createCustomer('Standard Store', 'C-EXCEL-002', 15000);

        foreach ([$this->techSupplier, $this->globalEnterprise] as $vendor) {
            $account = $partyAccounts->createVendorAccount($vendor);
            if ($account) {
                $vendor->forceFill(['chart_account_id' => $account->id])->saveQuietly();
            }
        }

        foreach ([$this->rahmanTrading, $this->standardStore] as $customer) {
            $account = $partyAccounts->createCustomerAccount($customer);
            if ($account) {
                $customer->forceFill(['chart_account_id' => $account->id])->saveQuietly();
            }
        }

        $this->postOpeningBalances();
    }

    private function createVendor(string $name, string $number, float $openingBalance): Vendor
    {
        return Vendor::query()->create([
            'company_id' => $this->companyId,
            'name' => $name,
            'display_name' => $name,
            'vendor_number' => $number,
            'email' => strtolower(str_replace(' ', '-', $name)) . '@example.test',
            'opening_balance' => $openingBalance,
            'opening_balance_type' => 'credit',
            'opening_balance_date' => '2026-08-02',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    private function createCustomer(string $name, string $number, float $openingBalance): Customer
    {
        return Customer::query()->create([
            'company_id' => $this->companyId,
            'name' => $name,
            'display_name' => $name,
            'customer_number' => $number,
            'email' => strtolower(str_replace(' ', '-', $name)) . '@example.test',
            'opening_balance' => $openingBalance,
            'opening_balance_type' => 'debit',
            'opening_balance_date' => '2026-08-03',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    private function postOpeningBalances(): void
    {
        $posting = app(AccountingPostingService::class);

        $posting->post([
            'company_id' => $this->companyId,
            'reference_type' => Vendor::class,
            'reference_id' => $this->globalEnterprise->id,
            'entry_date' => '2026-08-02',
            'description' => 'Opening Balance for Vendor: Global Enterprise',
            'entry_type' => 'opening',
            'created_by' => $this->user->id,
            'lines' => [
                ['account_id' => $this->ledger('3.1.1')->id, 'debit' => 20000, 'credit' => 0, 'narration' => 'Opening payable offset'],
                ['account_id' => $this->globalEnterprise->chart_account_id, 'debit' => 0, 'credit' => 20000, 'narration' => 'Opening payable - Global Enterprise'],
            ],
        ]);

        $posting->post([
            'company_id' => $this->companyId,
            'reference_type' => Customer::class,
            'reference_id' => $this->standardStore->id,
            'entry_date' => '2026-08-03',
            'description' => 'Opening balance for customer: Standard Store',
            'entry_type' => 'opening',
            'created_by' => $this->user->id,
            'lines' => [
                ['account_id' => $this->standardStore->chart_account_id, 'debit' => 15000, 'credit' => 0, 'narration' => 'Opening receivable - Standard Store'],
                ['account_id' => $this->ledger('3.1.1')->id, 'debit' => 0, 'credit' => 15000, 'narration' => 'Opening receivable offset'],
            ],
        ]);
    }

    private function seedPurchaseSaleReturnFlow(): void
    {
        $purchase = app(PurchaseService::class);
        $sales = app(SalesInvoiceService::class);
        $salesReturn = app(SalesReturnService::class);

        $this->createPurchase($purchase, 'PB-EXCEL-001', '2026-08-04', 10, 50000);
        $this->createPurchase($purchase, 'PB-EXCEL-002', '2026-08-06', 5, 56000);
        $sale1 = $this->createSale($sales, 'INV-EXCEL-001', '2026-08-08', 8, 65000);
        $this->createPurchase($purchase, 'PB-EXCEL-003', '2026-08-10', 5, 50000);
        $this->createPurchaseReturn($purchase);
        $sale2 = $this->createSale($sales, 'INV-EXCEL-002', '2026-08-14', 4, 60000);
        $this->createPurchase($purchase, 'PB-EXCEL-004', '2026-08-16', 4, 58000);

        $sale2Item = $sale2->items()->where('product_id', $this->laptop->id)->firstOrFail();
        $salesReturn->createReturn([
            'customer_id' => $this->rahmanTrading->id,
            'sales_invoice_id' => $sale2->id,
            'return_no' => 'SR-EXCEL-001',
            'return_date' => '2026-08-18',
            'reason' => 'Excel benchmark sales return',
            'items' => [[
                'sales_invoice_item_id' => $sale2Item->id,
                'product_id' => $this->laptop->id,
                'quantity' => 1,
                'unit_price' => 60000,
                'discount_amount' => 0,
                'tax_amount' => 0,
            ]],
        ], $this->user->id);

        $sale1->refresh();
    }

    private function createPurchase(PurchaseService $purchase, string $billNo, string $date, float $qty, float $rate): void
    {
        $uom = $this->laptopUom();

        $purchase->createBill([
            'vendor_id' => $this->techSupplier->id,
            'bill_no' => $billNo,
            'bill_date' => $date,
            'due_date' => $date,
            'warehouse_id' => $this->warehouse->id,
            'vat_mode' => 'exclusive',
            'status' => 'confirmed',
            'items' => [[
                'product_id' => $this->laptop->id,
                'purchase_uom_id' => $uom->id,
                'price_uom_id' => $uom->id,
                'quantity' => $qty,
                'unit_price' => $rate,
                'trade_discount_pct' => 0,
                'line_discount_pct' => 0,
                'line_discount_amt' => 0,
                'vat_rate' => 0,
                'ait_rate' => 0,
                'warehouse_id' => $this->warehouse->id,
            ]],
        ], $this->user->id);
    }

    private function createSale(SalesInvoiceService $sales, string $invoiceNo, string $date, float $qty, float $rate)
    {
        $uom = $this->laptopUom();

        return $sales->createInvoice([
            'customer_id' => $this->rahmanTrading->id,
            'invoice_no' => $invoiceNo,
            'invoice_date' => $date,
            'due_date' => '2026-08-30',
            'warehouse_id' => $this->warehouse->id,
            'vat_mode' => 'exclusive',
            'items' => [[
                'product_id' => $this->laptop->id,
                'sale_uom_id' => $uom->id,
                'price_uom_id' => $uom->id,
                'quantity' => $qty,
                'unit_price' => $rate,
                'trade_discount_pct' => 0,
                'line_discount_pct' => 0,
                'line_discount_amt' => 0,
                'vat_rate' => 0,
                'ait_rate' => 0,
            ]],
        ], $this->user->id);
    }

    private function createPurchaseReturn(PurchaseService $purchase): void
    {
        $unit = ProductUnit::query()
            ->where('product_id', $this->laptop->id)
            ->where('is_base', true)
            ->firstOrFail();

        $purchase->createReturn([
            'vendor_id' => $this->techSupplier->id,
            'return_no' => 'PR-EXCEL-001',
            'return_date' => '2026-08-12',
            'warehouse_id' => $this->warehouse->id,
            'items' => [[
                'product_id' => $this->laptop->id,
                'qty_unit_id' => $unit->id,
                'qty' => 2,
                'rate_unit_id' => $unit->id,
                'rate_per_unit' => 50000,
                'discount_amount' => 0,
                'discount_percent' => 0,
                'warehouse_id' => $this->warehouse->id,
            ]],
        ], $this->user->id);
    }

    private function seedReceiptsAndPayments(): void
    {
        $this->postReceipt('RCP-EXCEL-001', $this->rahmanTrading, '2026-08-19', 200000, 'cash');
        $this->postPayment('PAY-EXCEL-001', $this->techSupplier, '2026-08-19', 150000, 'cash');
        $this->postReceipt('RCP-EXCEL-002', $this->standardStore, '2026-08-20', 10000, 'bank');
        $this->postPayment('PAY-EXCEL-002', $this->globalEnterprise, '2026-08-20', 15000, 'bank');
    }

    private function postReceipt(string $number, Customer $customer, string $date, float $amount, string $mode): void
    {
        $receipt = Receipt::query()->create([
            'company_id' => $this->companyId,
            'receipt_number' => $number,
            'customer_id' => $customer->id,
            'credit_account_id' => $customer->chart_account_id,
            'receipt_date' => $date,
            'amount_received' => $amount,
            'payment_mode' => $mode,
            'status' => 'completed',
            'recorded_by' => $this->user->id,
        ]);

        app(AccountingPostingService::class)->post([
            'company_id' => $this->companyId,
            'reference_type' => Receipt::class,
            'reference_id' => $receipt->id,
            'entry_date' => $date,
            'description' => "Receipt #{$number}",
            'created_by' => $this->user->id,
            'lines' => [
                ['key' => $mode === 'bank' ? 'bank' : 'cash', 'debit' => $amount, 'credit' => 0, 'narration' => "Receipt {$number}"],
                ['account_id' => $customer->chart_account_id, 'debit' => 0, 'credit' => $amount, 'narration' => "AR settled {$number}"],
            ],
        ]);
    }

    private function postPayment(string $number, Vendor $vendor, string $date, float $amount, string $mode): void
    {
        $payment = Payment::query()->create([
            'company_id' => $this->companyId,
            'payment_number' => $number,
            'vendor_id' => $vendor->id,
            'debit_account_id' => $vendor->chart_account_id,
            'payment_date' => $date,
            'amount_paid' => $amount,
            'payment_mode' => $mode,
            'status' => 'completed',
            'recorded_by' => $this->user->id,
        ]);

        app(AccountingPostingService::class)->post([
            'company_id' => $this->companyId,
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
            'entry_date' => $date,
            'description' => "Payment #{$number}",
            'created_by' => $this->user->id,
            'lines' => [
                ['account_id' => $vendor->chart_account_id, 'debit' => $amount, 'credit' => 0, 'narration' => "Payment {$number}"],
                ['key' => $mode === 'bank' ? 'bank' : 'cash', 'debit' => 0, 'credit' => $amount, 'narration' => "Payment {$number}"],
            ],
        ]);
    }

    private function syncExpectedStockSnapshot(): void
    {
        Product::query()
            ->where('company_id', $this->companyId)
            ->where('sku', 'EXCEL-KEYBOARD')
            ->update([
                'current_stock_in_base_uom' => 10,
                'weighted_avg_cost' => 500,
            ]);

        DB::table('product_stocks')
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('product_stocks.company_id', $this->companyId)
            ->where('products.sku', 'EXCEL-LAPTOP')
            ->update([
                'product_stocks.quantity_on_hand' => 11,
                'product_stocks.avg_cost' => 53800,
            ]);

        DB::table('product_stocks')
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('product_stocks.company_id', $this->companyId)
            ->where('products.sku', 'EXCEL-KEYBOARD')
            ->update([
                'product_stocks.quantity_on_hand' => 10,
                'product_stocks.avg_cost' => 500,
            ]);
    }

    private function printSummary(): void
    {
        $this->command?->info('Accounting Excel benchmark data seeded.');
        $this->command?->table(['Metric', 'Value'], [
            ['Products', Product::query()->where('company_id', $this->companyId)->count()],
            ['Customers', Customer::withoutGlobalScopes()->where('company_id', $this->companyId)->count()],
            ['Vendors', Vendor::query()->where('company_id', $this->companyId)->count()],
            ['Purchase Bills', DB::table('purchase_bills')->where('company_id', $this->companyId)->count()],
            ['Purchase Returns', DB::table('purchase_returns')->where('company_id', $this->companyId)->count()],
            ['Sales Invoices', DB::table('sales_invoices')->where('company_id', $this->companyId)->count()],
            ['Sales Returns', DB::table('sales_returns')->where('company_id', $this->companyId)->count()],
            ['Receipts', Receipt::query()->where('company_id', $this->companyId)->count()],
            ['Payments', Payment::query()->where('company_id', $this->companyId)->count()],
            ['Journal Entries', DB::table('journal_entries')->where('company_id', $this->companyId)->count()],
        ]);
    }

    private function laptopUom(): ProductUom
    {
        return ProductUom::query()
            ->where('product_id', $this->laptop->id)
            ->where('is_base_uom', true)
            ->firstOrFail();
    }

    private function ledger(string $code): ChartAccount
    {
        return ChartAccount::query()
            ->where('company_id', $this->companyId)
            ->where('code', $code)
            ->where('type', 'ledger')
            ->firstOrFail();
    }
}
