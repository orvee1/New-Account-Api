<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LargeDemoDataSeeder extends Seeder
{
    private int $companyId;
    private ?int $userId;
    private string $prefix = 'LDT';
    private Carbon $now;

    public function run(): void
    {
        $this->companyId = (int) env('LARGE_DEMO_COMPANY_ID', 1);
        $this->userId = DB::table('users')->where('id', 1)->value('id');
        $this->now = now();

        if (! DB::table('companies')->where('id', $this->companyId)->exists()) {
            $this->command?->error("Company #{$this->companyId} was not found.");
            return;
        }

        $this->command?->info('Seeding large demo data for loading-state checks...');

        $helpers = $this->seedHelpers();
        $this->seedCustomers((int) env('LARGE_DEMO_CUSTOMERS', 15000));
        $this->seedVendors((int) env('LARGE_DEMO_VENDORS', 10000));
        $this->seedProducts((int) env('LARGE_DEMO_PRODUCTS', 20000), $helpers);
        $this->seedSalesInvoices((int) env('LARGE_DEMO_SALES_INVOICES', 20000), $helpers);
        $this->seedSalesOrders((int) env('LARGE_DEMO_SALES_ORDERS', 8000));
        $this->seedSalesReturns((int) env('LARGE_DEMO_SALES_RETURNS', 5000));
        $this->seedPurchaseBills((int) env('LARGE_DEMO_PURCHASE_BILLS', 15000), $helpers);
        $this->seedPurchaseOrders((int) env('LARGE_DEMO_PURCHASE_ORDERS', 8000), $helpers);
        $this->seedPurchaseReturns((int) env('LARGE_DEMO_PURCHASE_RETURNS', 5000), $helpers);
        $this->seedFixedAssets((int) env('LARGE_DEMO_FIXED_ASSETS', 10000));
        $this->seedPayroll((int) env('LARGE_DEMO_EMPLOYEES', 9000), (int) env('LARGE_DEMO_PAYROLL_RUNS', 1000));
        $this->seedTransactions((int) env('LARGE_DEMO_TRANSACTIONS_PER_TYPE', 5000));
        $this->seedAccountingPeriods();
        $this->redistributeDemoDates();

        $this->command?->info('Large demo data seeding complete.');
        $this->command?->line('Approx primary rows: 100,000, plus invoice/bill lines, salary setups, payroll pivots, and payslips.');
        $this->command?->line('Extra module rows are also seeded for orders, returns, transaction forms, and accounting periods.');
        $this->command?->line('Demo dates are spread from previous November to today.');
    }

    private function seedHelpers(): array
    {
        $this->insertChunks('warehouses', [[
            'company_id' => $this->companyId,
            'name' => "{$this->prefix} Main Warehouse",
            'is_default' => true,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]]);

        $categories = [];
        $brands = [];
        for ($i = 1; $i <= 25; $i++) {
            $categories[] = [
                'company_id' => $this->companyId,
                'name' => "{$this->prefix} Category {$i}",
                'slug' => Str::slug("{$this->prefix} Category {$i}"),
                'description' => 'Large demo category',
                'status' => 'active',
                'parent_id' => null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
            $brands[] = [
                'company_id' => $this->companyId,
                'name' => "{$this->prefix} Brand {$i}",
                'slug' => Str::slug("{$this->prefix} Brand {$i}"),
                'description' => 'Large demo brand',
                'status' => 'active',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('categories', $categories);
        $this->insertChunks('brands', $brands);

        $this->insertChunks('units_of_measure', [
            ['name' => "{$this->prefix} Piece", 'symbol' => 'pcs', 'created_at' => $this->now, 'updated_at' => $this->now],
        ]);

        return [
            'warehouse_id' => DB::table('warehouses')->where('company_id', $this->companyId)->where('name', "{$this->prefix} Main Warehouse")->value('id'),
            'category_ids' => DB::table('categories')->where('company_id', $this->companyId)->where('name', 'like', "{$this->prefix} Category%")->pluck('id')->values()->all(),
            'brand_ids' => DB::table('brands')->where('company_id', $this->companyId)->where('name', 'like', "{$this->prefix} Brand%")->pluck('id')->values()->all(),
            'uom_id' => DB::table('units_of_measure')->where('name', "{$this->prefix} Piece")->value('id'),
        ];
    }

    private function seedCustomers(int $count): void
    {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $rows[] = [
                'company_id' => $this->companyId,
                'name' => "Load Test Customer {$n}",
                'display_name' => "Customer {$n}",
                'proprietor_name' => "Owner {$n}",
                'customer_number' => "{$this->prefix}-CUST-{$n}",
                'phone_number' => '017' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'email' => "customer{$n}@load.test",
                'address' => "House {$i}, Demo Road, Dhaka",
                'nid' => 'NID-C-' . $n,
                'bank_details' => 'Demo bank details',
                'notes' => 'Large demo customer',
                'credit_limit' => 50000 + ($i % 100) * 1000,
                'opening_balance' => $i % 7 === 0 ? 1000 + ($i * 3) : 0,
                'opening_balance_type' => 'debit',
                'opening_balance_date' => $this->dateFor($i),
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        $this->insertChunks('customers', $rows);
        $this->command?->line("Customers ready: {$count}");
    }

    private function seedVendors(int $count): void
    {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $rows[] = [
                'company_id' => $this->companyId,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'name' => "Load Test Vendor {$n}",
                'display_name' => "Vendor {$n}",
                'proprietor_name' => "Vendor Owner {$n}",
                'vendor_number' => "{$this->prefix}-VEND-{$n}",
                'phone_number' => '018' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'address' => "Vendor Area {$i}, Dhaka",
                'nid' => 'NID-V-' . $n,
                'email' => "vendor{$n}@load.test",
                'bank_details' => 'Demo vendor bank details',
                'credit_limit' => 75000 + ($i % 100) * 1000,
                'notes' => 'Large demo vendor',
                'opening_balance' => $i % 9 === 0 ? 2000 + ($i * 2) : 0,
                'opening_balance_type' => $i % 2 === 0 ? 'credit' : 'debit',
                'opening_balance_date' => $this->dateFor($i),
                'custom_fields' => json_encode(['source' => 'large-demo']),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        $this->insertChunks('vendors', $rows);
        $this->command?->line("Vendors ready: {$count}");
    }

    private function seedProducts(int $count, array $helpers): void
    {
        $rows = [];
        $categoryIds = $helpers['category_ids'];
        $brandIds = $helpers['brand_ids'];

        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $cost = 100 + ($i % 300) * 7.5;
            $price = round($cost * 1.35, 2);
            $rows[] = [
                'company_id' => $this->companyId,
                'product_type' => $i % 12 === 0 ? 'Service' : 'Stock',
                'name' => "Load Test Product {$n}",
                'sku' => "{$this->prefix}-SKU-{$n}",
                'barcode' => "{$this->prefix}-BAR-{$n}",
                'category_id' => $categoryIds[$i % count($categoryIds)] ?? null,
                'brand_id' => $brandIds[$i % count($brandIds)] ?? null,
                'warehouse_id' => $helpers['warehouse_id'],
                'unit' => 'pcs',
                'base_uom_id' => $helpers['uom_id'],
                'costing_price' => $cost,
                'weighted_avg_cost' => $cost,
                'sales_price' => $price,
                'tax_percent' => 15,
                'current_stock_in_base_uom' => 500 + ($i % 1500),
                'vat_rate' => 15,
                'vat_inclusive' => false,
                'ait_rate' => $i % 5 === 0 ? 5 : 0,
                'has_warranty' => $i % 4 === 0,
                'warranty_days' => $i % 4 === 0 ? 365 : null,
                'description' => 'Large demo product for list and form loading checks.',
                'status' => 'active',
                'meta' => json_encode(['source' => 'large-demo']),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        $this->insertChunks('products', $rows);

        $productIds = DB::table('products')
            ->where('company_id', $this->companyId)
            ->where('sku', 'like', "{$this->prefix}-SKU-%")
            ->pluck('id');
        $existingUomProductIds = DB::table('product_uoms')
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->all();
        $existingUomProductIds = array_flip($existingUomProductIds);

        $uoms = [];
        foreach ($productIds as $productId) {
            if (isset($existingUomProductIds[$productId])) {
                continue;
            }
            $uoms[] = [
                'product_id' => $productId,
                'uom_id' => $helpers['uom_id'],
                'conversion_factor' => 1,
                'sale_price' => 100,
                'is_base_uom' => true,
                'is_default_sale_uom' => true,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('product_uoms', $uoms, false);

        $this->command?->line("Products ready: {$count}");
    }

    private function seedSalesInvoices(int $count, array $helpers): void
    {
        $customerIds = DB::table('customers')->where('company_id', $this->companyId)->where('customer_number', 'like', "{$this->prefix}-CUST-%")->pluck('id')->values()->all();
        $products = DB::table('products')->where('company_id', $this->companyId)->where('sku', 'like', "{$this->prefix}-SKU-%")->get(['id', 'sales_price', 'weighted_avg_cost'])->values();
        $productUomByProduct = DB::table('product_uoms')->whereIn('product_id', $products->pluck('id'))->pluck('id', 'product_id')->all();

        $invoices = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $subtotal = 1000 + ($i % 250) * 120;
            $discount = $i % 6 === 0 ? 250 : 0;
            $tax = round(($subtotal - $discount) * 0.15, 2);
            $total = $subtotal - $discount + $tax;
            $paid = $i % 4 === 0 ? $total : ($i % 4 === 1 ? round($total / 2, 2) : 0);
            $invoices[] = [
                'company_id' => $this->companyId,
                'customer_id' => $customerIds[$i % count($customerIds)],
                'invoice_no' => "{$this->prefix}-INV-{$n}",
                'invoice_date' => $this->dateFor($i),
                'due_date' => Carbon::parse($this->dateFor($i))->addDays(30)->toDateString(),
                'warehouse_id' => $helpers['warehouse_id'],
                'notes' => 'Large demo sales invoice',
                'status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partially_paid' : 'sent'),
                'subtotal' => $subtotal,
                'trade_discount_amt' => 0,
                'line_discount_amt' => 0,
                'taxable_amount' => $subtotal - $discount,
                'vat_amount' => $tax,
                'ait_amount' => 0,
                'invoice_discount_amt' => $discount,
                'grand_total' => $total,
                'vat_mode' => 'exclusive',
                'discount_total' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'created_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('sales_invoices', $invoices);

        $invoiceIdsWithItems = DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.invoice_no', 'like', "{$this->prefix}-INV-%")
            ->pluck('sales_invoice_id')
            ->unique()
            ->all();
        $invoiceIdsWithItems = array_flip($invoiceIdsWithItems);

        $items = [];
        $demoInvoices = DB::table('sales_invoices')->where('company_id', $this->companyId)->where('invoice_no', 'like', "{$this->prefix}-INV-%")->get(['id', 'invoice_no'])->values();
        foreach ($demoInvoices as $index => $invoice) {
            if (isset($invoiceIdsWithItems[$invoice->id])) {
                continue;
            }
            $product = $products[$index % $products->count()];
            $qty = 1 + ($index % 5);
            $unitPrice = (float) $product->sales_price;
            $lineSubtotal = round($qty * $unitPrice, 4);
            $vat = round($lineSubtotal * 0.15, 4);
            $cost = round($qty * (float) $product->weighted_avg_cost, 4);
            $items[] = [
                'sales_invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'sale_uom_id' => $productUomByProduct[$product->id] ?? null,
                'price_uom_id' => $productUomByProduct[$product->id] ?? null,
                'quantity' => $qty,
                'quantity_in_sale_uom' => $qty,
                'quantity_in_base_uom' => $qty,
                'unit_price' => $unitPrice,
                'unit_price_original' => $unitPrice,
                'trade_discount_pct' => 0,
                'trade_discount_amt' => 0,
                'net_unit_price' => $unitPrice,
                'line_gross_amount' => $lineSubtotal,
                'line_discount_pct' => 0,
                'line_discount_amt' => 0,
                'line_subtotal' => $lineSubtotal,
                'vat_rate' => 15,
                'vat_amount' => $vat,
                'ait_rate' => 0,
                'ait_amount' => 0,
                'weighted_avg_cost' => $product->weighted_avg_cost,
                'cogs' => $cost,
                'gross_profit' => round($lineSubtotal - $cost, 4),
                'discount_amount' => 0,
                'tax_amount' => $vat,
                'line_total' => round($lineSubtotal + $vat, 2),
                'description' => 'Large demo invoice line',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('sales_invoice_items', $items, false);
        $this->command?->line("Sales invoices ready: {$count}");
    }

    private function seedPurchaseBills(int $count, array $helpers): void
    {
        $vendorIds = DB::table('vendors')->where('company_id', $this->companyId)->where('vendor_number', 'like', "{$this->prefix}-VEND-%")->pluck('id')->values()->all();
        $products = DB::table('products')->where('company_id', $this->companyId)->where('sku', 'like', "{$this->prefix}-SKU-%")->get(['id', 'costing_price'])->values();
        $productUomByProduct = DB::table('product_uoms')->whereIn('product_id', $products->pluck('id'))->pluck('id', 'product_id')->all();

        $bills = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $subtotal = 1200 + ($i % 200) * 90;
            $discount = $i % 8 === 0 ? 150 : 0;
            $tax = round(($subtotal - $discount) * 0.15, 4);
            $total = $subtotal - $discount + $tax;
            $bills[] = [
                'company_id' => $this->companyId,
                'vendor_id' => $vendorIds[$i % count($vendorIds)],
                'bill_no' => "{$this->prefix}-BILL-{$n}",
                'supplier_ref_no' => "{$this->prefix}-SUP-{$n}",
                'bill_date' => $this->dateFor($i),
                'due_date' => Carbon::parse($this->dateFor($i))->addDays(20)->toDateString(),
                'warehouse_id' => $helpers['warehouse_id'],
                'notes' => 'Large demo purchase bill',
                'vat_mode' => 'exclusive',
                'subtotal' => $subtotal,
                'trade_discount_amt' => 0,
                'line_discount_amt' => 0,
                'taxable_amount' => $subtotal - $discount,
                'vat_amount' => $tax,
                'ait_amount' => 0,
                'bill_discount_amt' => $discount,
                'discount_total' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'payment_status' => $i % 3 === 0 ? 'paid' : ($i % 3 === 1 ? 'partial' : 'unpaid'),
                'status' => 'confirmed',
                'created_by' => $this->userId ?? 1,
                'updated_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('purchase_bills', $bills);

        $billIdsWithItems = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->where('purchase_bills.bill_no', 'like', "{$this->prefix}-BILL-%")
            ->pluck('purchase_bill_id')
            ->unique()
            ->all();
        $billIdsWithItems = array_flip($billIdsWithItems);

        $items = [];
        $demoBills = DB::table('purchase_bills')->where('company_id', $this->companyId)->where('bill_no', 'like', "{$this->prefix}-BILL-%")->get(['id'])->values();
        foreach ($demoBills as $index => $bill) {
            if (isset($billIdsWithItems[$bill->id])) {
                continue;
            }
            $product = $products[$index % $products->count()];
            $qty = 1 + ($index % 8);
            $rate = (float) $product->costing_price;
            $subtotal = round($qty * $rate, 4);
            $vat = round($subtotal * 0.15, 4);
            $uomId = $productUomByProduct[$product->id] ?? $helpers['uom_id'];
            $items[] = [
                'company_id' => $this->companyId,
                'purchase_bill_id' => $bill->id,
                'product_id' => $product->id,
                'qty_unit_id' => $uomId,
                'qty' => $qty,
                'qty_base' => $qty,
                'rate_unit_id' => $uomId,
                'rate_per_unit' => $rate,
                'rate_per_base' => $rate,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'purchase_uom_id' => $uomId,
                'price_uom_id' => $uomId,
                'quantity_in_purchase_uom' => $qty,
                'quantity_in_base_uom' => $qty,
                'unit_price_original' => $rate,
                'trade_discount_pct' => 0,
                'trade_discount_amt' => 0,
                'net_unit_price' => $rate,
                'line_gross_amount' => $subtotal,
                'line_discount_pct' => 0,
                'line_discount_amt' => 0,
                'line_subtotal' => $subtotal,
                'vat_rate' => 15,
                'vat_amount' => $vat,
                'ait_rate' => 0,
                'ait_amount' => 0,
                'net_unit_cost' => $rate,
                'weighted_avg_cost_before' => $rate,
                'weighted_avg_cost_after' => $rate,
                'line_total' => round($subtotal + $vat, 4),
                'warehouse_id' => $helpers['warehouse_id'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('purchase_bill_items', $items, false);
        $this->command?->line("Purchase bills ready: {$count}");
    }

    private function seedSalesOrders(int $count): void
    {
        $customerIds = DB::table('customers')->where('company_id', $this->companyId)->where('customer_number', 'like', "{$this->prefix}-CUST-%")->pluck('id')->values()->all();
        $products = DB::table('products')->where('company_id', $this->companyId)->where('sku', 'like', "{$this->prefix}-SKU-%")->get(['id', 'sales_price'])->values();

        $orders = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $subtotal = 900 + ($i % 220) * 115;
            $discount = $i % 7 === 0 ? 200 : 0;
            $tax = round(($subtotal - $discount) * 0.15, 2);
            $orders[] = [
                'company_id' => $this->companyId,
                'customer_id' => $customerIds[$i % count($customerIds)],
                'order_no' => "{$this->prefix}-SO-{$n}",
                'order_date' => $this->dateFor($i + 100),
                'expected_delivery_date' => Carbon::parse($this->dateFor($i + 100))->addDays(14)->toDateString(),
                'notes' => 'Large demo sales order',
                'status' => ['draft', 'pending', 'confirmed', 'shipped', 'delivered'][$i % 5],
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $subtotal - $discount + $tax,
                'created_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('sales_orders', $orders);

        $existing = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_orders.order_no', 'like', "{$this->prefix}-SO-%")
            ->pluck('sales_order_id')->unique()->flip();
        $items = [];
        $demoOrders = DB::table('sales_orders')->where('company_id', $this->companyId)->where('order_no', 'like', "{$this->prefix}-SO-%")->get(['id'])->values();
        foreach ($demoOrders as $index => $order) {
            if (isset($existing[$order->id])) {
                continue;
            }
            $product = $products[$index % $products->count()];
            $qty = 1 + ($index % 6);
            $unitPrice = (float) $product->sales_price;
            $subtotal = round($qty * $unitPrice, 2);
            $tax = round($subtotal * 0.15, 2);
            $items[] = [
                'sales_order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'tax_amount' => $tax,
                'line_total' => $subtotal + $tax,
                'description' => 'Large demo sales order item',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('sales_order_items', $items, false);
        $this->command?->line("Sales orders ready: {$count}");
    }

    private function seedSalesReturns(int $count): void
    {
        $invoices = DB::table('sales_invoices')->where('company_id', $this->companyId)->where('invoice_no', 'like', "{$this->prefix}-INV-%")->get(['id', 'customer_id'])->values();
        $invoiceItems = DB::table('sales_invoice_items')->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->where('sales_invoices.invoice_no', 'like', "{$this->prefix}-INV-%")
            ->get(['sales_invoice_items.id', 'sales_invoice_items.product_id', 'sales_invoice_items.unit_price'])
            ->values();

        $returns = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $invoice = $invoices[$i % $invoices->count()];
            $subtotal = 500 + ($i % 80) * 50;
            $tax = round($subtotal * 0.15, 2);
            $returns[] = [
                'company_id' => $this->companyId,
                'customer_id' => $invoice->customer_id,
                'sales_invoice_id' => $invoice->id,
                'return_no' => "{$this->prefix}-SR-{$n}",
                'return_date' => $this->dateFor($i + 200),
                'reason' => ['Damaged goods', 'Wrong item', 'Customer claim'][$i % 3],
                'notes' => 'Large demo sales return',
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'tax_amount' => $tax,
                'total_amount' => $subtotal + $tax,
                'created_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('sales_returns', $returns);

        $existing = DB::table('sales_return_items')
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->where('sales_returns.return_no', 'like', "{$this->prefix}-SR-%")
            ->pluck('sales_return_id')->unique()->flip();
        $items = [];
        $demoReturns = DB::table('sales_returns')->where('company_id', $this->companyId)->where('return_no', 'like', "{$this->prefix}-SR-%")->get(['id'])->values();
        foreach ($demoReturns as $index => $return) {
            if (isset($existing[$return->id])) {
                continue;
            }
            $invoiceItem = $invoiceItems[$index % $invoiceItems->count()];
            $qty = 1;
            $unitPrice = (float) $invoiceItem->unit_price;
            $tax = round($unitPrice * 0.15, 2);
            $items[] = [
                'sales_return_id' => $return->id,
                'sales_invoice_item_id' => $invoiceItem->id,
                'product_id' => $invoiceItem->product_id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'tax_amount' => $tax,
                'line_total' => $unitPrice + $tax,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('sales_return_items', $items, false);
        $this->command?->line("Sales returns ready: {$count}");
    }

    private function seedPurchaseOrders(int $count, array $helpers): void
    {
        $vendorIds = DB::table('vendors')->where('company_id', $this->companyId)->where('vendor_number', 'like', "{$this->prefix}-VEND-%")->pluck('id')->values()->all();
        $products = DB::table('products')->where('company_id', $this->companyId)->where('sku', 'like', "{$this->prefix}-SKU-%")->get(['id', 'costing_price'])->values();
        $productUomByProduct = DB::table('product_uoms')->whereIn('product_id', $products->pluck('id'))->pluck('id', 'product_id')->all();

        $orders = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $subtotal = 1000 + ($i % 180) * 100;
            $tax = round($subtotal * 0.15, 2);
            $orders[] = [
                'company_id' => $this->companyId,
                'vendor_id' => $vendorIds[$i % count($vendorIds)],
                'order_no' => "{$this->prefix}-PO-{$n}",
                'order_date' => $this->dateFor($i + 300),
                'expected_delivery_date' => Carbon::parse($this->dateFor($i + 300))->addDays(12)->toDateString(),
                'notes' => 'Large demo purchase order',
                'status' => ['draft', 'confirmed', 'received', 'cancelled'][$i % 4],
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'tax_amount' => $tax,
                'total_amount' => $subtotal + $tax,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('purchase_orders', $orders);

        $existing = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.order_no', 'like', "{$this->prefix}-PO-%")
            ->pluck('purchase_order_id')->unique()->flip();
        $items = [];
        $demoOrders = DB::table('purchase_orders')->where('company_id', $this->companyId)->where('order_no', 'like', "{$this->prefix}-PO-%")->get(['id'])->values();
        foreach ($demoOrders as $index => $order) {
            if (isset($existing[$order->id])) {
                continue;
            }
            $product = $products[$index % $products->count()];
            $qty = 1 + ($index % 8);
            $rate = (float) $product->costing_price;
            $uomId = $productUomByProduct[$product->id] ?? $helpers['uom_id'];
            $subtotal = round($qty * $rate, 2);
            $items[] = [
                'purchase_order_id' => $order->id,
                'company_id' => $this->companyId,
                'product_id' => $product->id,
                'qty_unit_id' => $uomId,
                'qty' => $qty,
                'qty_base' => $qty,
                'rate_unit_id' => $uomId,
                'rate_per_unit' => $rate,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'line_subtotal' => $subtotal,
                'line_total' => $subtotal,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('purchase_order_items', $items, false);
        $this->command?->line("Purchase orders ready: {$count}");
    }

    private function seedPurchaseReturns(int $count, array $helpers): void
    {
        $vendorIds = DB::table('vendors')->where('company_id', $this->companyId)->where('vendor_number', 'like', "{$this->prefix}-VEND-%")->pluck('id')->values()->all();
        $products = DB::table('products')->where('company_id', $this->companyId)->where('sku', 'like', "{$this->prefix}-SKU-%")->get(['id', 'costing_price'])->values();
        $productUomByProduct = DB::table('product_uoms')->whereIn('product_id', $products->pluck('id'))->pluck('id', 'product_id')->all();

        $returns = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $subtotal = 600 + ($i % 90) * 60;
            $tax = round($subtotal * 0.15, 4);
            $returns[] = [
                'company_id' => $this->companyId,
                'vendor_id' => $vendorIds[$i % count($vendorIds)],
                'return_no' => "{$this->prefix}-PR-{$n}",
                'return_date' => $this->dateFor($i + 400),
                'warehouse_id' => $helpers['warehouse_id'],
                'notes' => 'Large demo purchase return',
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'tax_amount' => $tax,
                'total_amount' => $subtotal + $tax,
                'created_by' => $this->userId ?? 1,
                'updated_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('purchase_returns', $returns);

        $existing = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->where('purchase_returns.return_no', 'like', "{$this->prefix}-PR-%")
            ->pluck('purchase_return_id')->unique()->flip();
        $items = [];
        $demoReturns = DB::table('purchase_returns')->where('company_id', $this->companyId)->where('return_no', 'like', "{$this->prefix}-PR-%")->get(['id'])->values();
        foreach ($demoReturns as $index => $return) {
            if (isset($existing[$return->id])) {
                continue;
            }
            $product = $products[$index % $products->count()];
            $qty = 1 + ($index % 4);
            $rate = (float) $product->costing_price;
            $uomId = $productUomByProduct[$product->id] ?? $helpers['uom_id'];
            $subtotal = round($qty * $rate, 4);
            $items[] = [
                'company_id' => $this->companyId,
                'purchase_return_id' => $return->id,
                'product_id' => $product->id,
                'qty_unit_id' => $uomId,
                'qty' => $qty,
                'qty_base' => $qty,
                'rate_unit_id' => $uomId,
                'rate_per_unit' => $rate,
                'rate_per_base' => $rate,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'line_subtotal' => $subtotal,
                'line_total' => $subtotal,
                'warehouse_id' => $helpers['warehouse_id'],
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('purchase_return_items', $items, false);
        $this->command?->line("Purchase returns ready: {$count}");
    }

    private function seedFixedAssets(int $count): void
    {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $amount = 15000 + ($i % 200) * 500;
            $rows[] = [
                'company_id' => $this->companyId,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'name' => "Load Test Asset {$n}",
                'category' => ['Computer', 'Furniture', 'Machinery', 'Vehicle'][$i % 4],
                'purchase_date' => $this->dateFor($i),
                'amount' => $amount,
                'vendor_name' => "Load Test Vendor " . str_pad((string) (($i % 1500) + 1), 5, '0', STR_PAD_LEFT),
                'purchase_mode' => $i % 3 === 0 ? 'On Credit' : ($i % 3 === 1 ? 'Cash Purchase' : 'Bank Purchase'),
                'payment_mode' => $i % 3 === 0 ? null : ($i % 3 === 1 ? 'Cash in Hand' : 'Bank Account'),
                'useful_life' => 3 + ($i % 7),
                'salvage_value' => round($amount * 0.1, 2),
                'depreciation_method' => 'Straight Line',
                'frequency' => $i % 2 === 0 ? 'Monthly' : 'Yearly',
                'depreciation_rate' => 10 + ($i % 15),
                'asset_location' => ['Head Office', 'Factory', 'Warehouse'][$i % 3],
                'tag_serial_number' => "{$this->prefix}-ASSET-{$n}",
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        $this->insertChunks('fixed_assets', $rows);
        $this->command?->line("Fixed assets ready: {$count}");
    }

    private function seedPayroll(int $employeeCount, int $payrollRunCount): void
    {
        $employees = [];
        for ($i = 1; $i <= $employeeCount; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $employees[] = [
                'company_id' => $this->companyId,
                'employee_number' => "{$this->prefix}-EMP-{$n}",
                'first_name' => 'Employee',
                'last_name' => $n,
                'email' => "employee{$n}@load.test",
                'phone' => '019' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'date_of_birth' => Carbon::now()->subYears(22 + ($i % 25))->toDateString(),
                'gender' => ['male', 'female', 'other'][$i % 3],
                'nid_number' => 'NID-E-' . $n,
                'address' => "Employee Address {$i}",
                'department' => ['Accounts', 'Sales', 'Purchase', 'Warehouse', 'HR'][$i % 5],
                'designation' => ['Officer', 'Executive', 'Manager', 'Assistant'][$i % 4],
                'date_of_joining' => Carbon::now()->subMonths($i % 60)->toDateString(),
                'employment_type' => ['permanent', 'contract', 'temporary', 'casual'][$i % 4],
                'status' => $i % 20 === 0 ? 'inactive' : 'active',
                'bank_account_number' => 'BANK-' . $n,
                'bank_name' => 'Demo Bank',
                'ifsc_code' => 'DEMO0001',
                'notes' => 'Large demo employee',
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('employees', $employees);

        $employeeRows = DB::table('employees')
            ->where('company_id', $this->companyId)
            ->where('employee_number', 'like', "{$this->prefix}-EMP-%")
            ->get(['id', 'employee_number'])
            ->values();

        $existingSalaryEmployeeIds = DB::table('salary_setups')
            ->whereIn('employee_id', $employeeRows->pluck('id'))
            ->pluck('employee_id')
            ->all();
        $existingSalaryEmployeeIds = array_flip($existingSalaryEmployeeIds);

        $salarySetups = [];
        foreach ($employeeRows as $index => $employee) {
            if (isset($existingSalaryEmployeeIds[$employee->id])) {
                continue;
            }
            $basic = 25000 + ($index % 100) * 500;
            $hra = round($basic * 0.4, 2);
            $medical = 1500;
            $conveyance = 2000;
            $gross = $basic + $hra + $medical + $conveyance;
            $pf = round($basic * 0.05, 2);
            $tax = round($gross * 0.03, 2);
            $net = $gross - $pf - $tax;
            $salarySetups[] = [
                'company_id' => $this->companyId,
                'employee_id' => $employee->id,
                'basic_salary' => $basic,
                'house_rent_allowance' => $hra,
                'medical_allowance' => $medical,
                'conveyance_allowance' => $conveyance,
                'other_allowances' => 0,
                'gross_salary' => $gross,
                'provident_fund_percent' => 5,
                'provident_fund_amount' => $pf,
                'income_tax_percent' => 3,
                'income_tax_amount' => $tax,
                'other_deductions' => 0,
                'net_salary' => $net,
                'effective_date' => Carbon::now()->startOfYear()->toDateString(),
                'status' => 'active',
                'notes' => 'Large demo salary setup',
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('salary_setups', $salarySetups, false);

        $runs = [];
        for ($i = 1; $i <= $payrollRunCount; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $month = (($i - 1) % 12) + 1;
            $year = 2024 + intdiv($i - 1, 12);
            $runs[] = [
                'company_id' => $this->companyId,
                'payroll_run_number' => "{$this->prefix}-PAY-{$n}",
                'payroll_month' => $month,
                'payroll_year' => $year,
                'start_date' => Carbon::create($year, $month, 1)->toDateString(),
                'end_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                'payment_date' => Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
                'total_employees' => min(25, $employeeRows->count()),
                'total_gross_salary' => 25 * 45000,
                'total_deductions' => 25 * 3500,
                'total_net_salary' => 25 * 41500,
                'status' => $i % 3 === 0 ? 'pending' : 'processed',
                'processing_notes' => 'Large demo payroll run',
                'created_by' => $this->userId,
                'processed_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('payroll_runs', $runs);

        $payrollRuns = DB::table('payroll_runs')
            ->where('company_id', $this->companyId)
            ->where('payroll_run_number', 'like', "{$this->prefix}-PAY-%")
            ->get(['id', 'payroll_run_number', 'payroll_month', 'payroll_year'])
            ->values();
        $salaryByEmployee = DB::table('salary_setups')
            ->whereIn('employee_id', $employeeRows->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $pivots = [];
        $payslips = [];
        foreach ($payrollRuns as $runIndex => $run) {
            $slice = $employeeRows->slice(($runIndex * 4) % max(1, $employeeRows->count()), 25)->values();
            foreach ($slice as $employee) {
                $setup = $salaryByEmployee->get($employee->id);
                if (! $setup) {
                    continue;
                }
                $deductions = (float) $setup->provident_fund_amount + (float) $setup->income_tax_amount + (float) $setup->other_deductions;
                $pivots[] = [
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'gross_salary' => $setup->gross_salary,
                    'deductions' => $deductions,
                    'net_salary' => $setup->net_salary,
                    'status' => 'processed',
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];
                $payslips[] = [
                    'company_id' => $this->companyId,
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'payslip_number' => "{$this->prefix}-SLIP-{$run->id}-{$employee->id}",
                    'payslip_date' => Carbon::create($run->payroll_year, $run->payroll_month, 1)->endOfMonth()->toDateString(),
                    'month' => $run->payroll_month,
                    'year' => $run->payroll_year,
                    'basic_salary' => $setup->basic_salary,
                    'house_rent_allowance' => $setup->house_rent_allowance,
                    'medical_allowance' => $setup->medical_allowance,
                    'conveyance_allowance' => $setup->conveyance_allowance,
                    'other_allowances' => $setup->other_allowances,
                    'gross_salary' => $setup->gross_salary,
                    'provident_fund' => $setup->provident_fund_amount,
                    'income_tax' => $setup->income_tax_amount,
                    'other_deductions' => $setup->other_deductions,
                    'total_deductions' => $deductions,
                    'net_salary' => $setup->net_salary,
                    'status' => 'processed',
                    'created_by' => $this->userId,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];
            }
        }

        $this->insertChunks('payroll_run_employees', $pivots);
        $this->insertChunks('payslips', $payslips);
        $this->command?->line("Payroll ready: {$employeeCount} employees, {$payrollRunCount} runs");
    }

    private function seedTransactions(int $countPerType): void
    {
        $customerIds = DB::table('customers')->where('company_id', $this->companyId)->where('customer_number', 'like', "{$this->prefix}-CUST-%")->pluck('id')->values()->all();
        $vendorIds = DB::table('vendors')->where('company_id', $this->companyId)->where('vendor_number', 'like', "{$this->prefix}-VEND-%")->pluck('id')->values()->all();
        $accountIds = DB::table('chart_accounts')
            ->where('company_id', $this->companyId)
            ->where('type', 'ledger')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        if (count($accountIds) < 2) {
            $this->command?->warn('Skipping transaction demo rows because at least two ledger accounts are required.');
            return;
        }

        $cashAccountId = $accountIds[0];
        $bankAccountId = $accountIds[1];
        $expenseAccountId = $accountIds[2] ?? $accountIds[0];
        $incomeAccountId = $accountIds[3] ?? $accountIds[1];

        $receipts = [];
        $payments = [];
        $contras = [];
        $debitNotes = [];
        $creditNotes = [];
        $manualJournals = [];
        $recurring = [];
        $transfers = [];

        for ($i = 1; $i <= $countPerType; $i++) {
            $n = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $amount = 500 + ($i % 300) * 75;
            $date = $this->dateFor($i + 500);

            $receipts[] = [
                'company_id' => $this->companyId,
                'customer_id' => $customerIds[$i % count($customerIds)] ?? null,
                'credit_account_id' => $cashAccountId,
                'receipt_number' => "{$this->prefix}-RCT-{$n}",
                'receipt_date' => $date,
                'amount_received' => $amount,
                'payment_mode' => ['cash', 'bank', 'online', 'cheque'][$i % 4],
                'reference_number' => "{$this->prefix}-RCT-REF-{$n}",
                'description' => 'Large demo receipt',
                'status' => 'completed',
                'recorded_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $payments[] = [
                'company_id' => $this->companyId,
                'vendor_id' => $vendorIds[$i % count($vendorIds)] ?? null,
                'debit_account_id' => $expenseAccountId,
                'payment_number' => "{$this->prefix}-PMT-{$n}",
                'payment_date' => $date,
                'amount_paid' => $amount + 125,
                'payment_mode' => ['cash', 'bank', 'online', 'cheque'][$i % 4],
                'invoice_reference' => "{$this->prefix}-BILL-" . str_pad((string) (($i % 15000) + 1), 5, '0', STR_PAD_LEFT),
                'cheque_number' => $i % 4 === 3 ? "CHQ-{$n}" : null,
                'description' => 'Large demo payment',
                'status' => 'completed',
                'recorded_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $contras[] = [
                'company_id' => $this->companyId,
                'from_account_id' => $cashAccountId,
                'to_account_id' => $bankAccountId,
                'contra_number' => "{$this->prefix}-CONTRA-{$n}",
                'contra_date' => $date,
                'amount' => $amount,
                'debit_amount' => $amount,
                'credit_amount' => $amount,
                'reference_number' => "{$this->prefix}-CONTRA-REF-{$n}",
                'description' => 'Large demo contra',
                'status' => $i % 8 === 0 ? 'draft' : 'completed',
                'reconciled' => $i % 5 === 0,
                'recorded_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $debitNotes[] = [
                'company_id' => $this->companyId,
                'vendor_id' => $vendorIds[$i % count($vendorIds)] ?? null,
                'credit_account_id' => $incomeAccountId,
                'debit_note_number' => "{$this->prefix}-DN-{$n}",
                'invoice_reference' => "{$this->prefix}-BILL-" . str_pad((string) (($i % 15000) + 1), 5, '0', STR_PAD_LEFT),
                'note_date' => $date,
                'reason' => ['Price adjustment', 'Quantity short', 'Quality issue'][$i % 3],
                'quantity' => 1 + ($i % 10),
                'amount' => $amount,
                'quality_issue' => $i % 3 === 2,
                'price_adjustment' => $i % 3 === 0 ? 50 : 0,
                'damage_amount' => $i % 3 === 2 ? 100 : 0,
                'description' => 'Large demo debit note',
                'status' => 'completed',
                'approved_by' => $this->userId,
                'approval_date' => $date,
                'recorded_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $creditNotes[] = [
                'company_id' => $this->companyId,
                'customer_id' => $customerIds[$i % count($customerIds)] ?? null,
                'debit_account_id' => $expenseAccountId,
                'credit_note_number' => "{$this->prefix}-CN-{$n}",
                'invoice_reference' => "{$this->prefix}-INV-" . str_pad((string) (($i % 20000) + 1), 5, '0', STR_PAD_LEFT),
                'note_date' => $date,
                'reason' => ['Sales return', 'Discount claim', 'Price correction'][$i % 3],
                'quantity_returned' => 1 + ($i % 8),
                'amount' => $amount,
                'discount_amount' => $i % 4 === 0 ? 100 : 0,
                'return_reason' => 'Large demo return reason',
                'claim_amount' => $i % 5 === 0 ? 250 : 0,
                'description' => 'Large demo credit note',
                'status' => 'completed',
                'approved_by' => $this->userId,
                'approval_date' => $date,
                'recorded_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $manualEntries = [
                ['account_id' => $expenseAccountId, 'type' => 'debit', 'amount' => $amount],
                ['account_id' => $incomeAccountId, 'type' => 'credit', 'amount' => $amount],
            ];
            $manualJournals[] = [
                'company_id' => $this->companyId,
                'journal_number' => "{$this->prefix}-JNL-{$n}",
                'journal_date' => $date,
                'debit_account_id' => $expenseAccountId,
                'credit_account_id' => $incomeAccountId,
                'debit_amount' => $amount,
                'credit_amount' => $amount,
                'entries' => json_encode($manualEntries),
                'description' => 'Large demo manual journal',
                'reference_number' => "{$this->prefix}-JNL-REF-{$n}",
                'narration' => 'Large demo balanced entry',
                'status' => $i % 6 === 0 ? 'draft' : 'posted',
                'entry_type' => 'normal',
                'posted_by' => $this->userId,
                'posted_date' => $date,
                'recorded_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $recurring[] = [
                'company_id' => $this->companyId,
                'transaction_number' => "{$this->prefix}-REC-{$n}",
                'type' => $i % 4 === 0 ? 'adjustment' : 'recurring',
                'frequency' => ['monthly', 'quarterly', 'annual'][$i % 3],
                'from_account_id' => $expenseAccountId,
                'to_account_id' => $incomeAccountId,
                'amount' => $amount,
                'description' => 'Large demo recurring or adjustment',
                'start_date' => $date,
                'end_date' => Carbon::parse($date)->addYear()->toDateString(),
                'next_date' => Carbon::parse($date)->addMonth()->toDateString(),
                'status' => $i % 9 === 0 ? 'inactive' : 'active',
                'is_active' => $i % 9 !== 0,
                'recorded_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $transfers[] = [
                'company_id' => $this->companyId,
                'transfer_number' => "{$this->prefix}-TRF-{$n}",
                'from_account_id' => $cashAccountId,
                'to_account_id' => $bankAccountId,
                'amount' => $amount,
                'transfer_date' => $date,
                'reference_number' => "{$this->prefix}-TRF-REF-{$n}",
                'description' => 'Large demo transaction transfer',
                'notes' => 'Large demo transfer notes',
                'status' => $i % 7 === 0 ? 'draft' : 'completed',
                'recorded_by' => $this->userId,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        $this->insertChunks('receipts', $receipts);
        $this->insertChunks('payments', $payments);
        $this->insertChunks('contras', $contras);
        $this->insertChunks('debit_notes', $debitNotes);
        $this->insertChunks('credit_notes', $creditNotes);
        $this->insertChunks('manual_journals', $manualJournals);
        $this->insertChunks('recurring_transactions', $recurring);
        $this->insertChunks('transaction_transfers', $transfers);

        $this->command?->line("Transaction module rows ready: {$countPerType} per type");
    }

    private function seedAccountingPeriods(): void
    {
        $years = [];
        for ($year = 2022; $year <= 2027; $year++) {
            $years[] = [
                'company_id' => $this->companyId,
                'name' => "{$this->prefix} FY {$year}",
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'status' => $year < 2026 ? 'closed' : 'open',
                'created_by' => $this->userId,
                'closed_by' => $year < 2026 ? $this->userId : null,
                'closed_at' => $year < 2026 ? Carbon::create($year, 12, 31, 23, 59, 59) : null,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }
        $this->insertChunks('fiscal_years', $years);

        $periods = [];
        $fiscalYears = DB::table('fiscal_years')->where('company_id', $this->companyId)->where('name', 'like', "{$this->prefix} FY %")->get(['id', 'name', 'start_date'])->values();
        foreach ($fiscalYears as $fy) {
            $year = (int) Carbon::parse($fy->start_date)->format('Y');
            for ($month = 1; $month <= 12; $month++) {
                $start = Carbon::create($year, $month, 1);
                $periods[] = [
                    'company_id' => $this->companyId,
                    'fiscal_year_id' => $fy->id,
                    'name' => "{$this->prefix} {$start->format('F Y')}",
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->endOfMonth()->toDateString(),
                    'status' => $year < 2026 ? 'closed' : 'open',
                    'closed_by' => $year < 2026 ? $this->userId : null,
                    'closed_at' => $year < 2026 ? $start->copy()->endOfMonth()->endOfDay() : null,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];
            }
        }
        $this->insertChunks('accounting_periods', $periods);
        $this->command?->line('Accounting periods ready: fiscal years and monthly periods');
    }

    private function dateFor(int $i): string
    {
        return $this->dateTimeFor($i)->toDateString();
    }

    private function dateTimeFor(int $i): Carbon
    {
        $end = Carbon::today();
        $start = Carbon::create($end->year - 1, 11, 1)->startOfDay();
        $days = max(1, $start->diffInDays($end));

        return $start
            ->copy()
            ->addDays($i % ($days + 1))
            ->addSeconds(($i * 997) % 86400);
    }

    private function redistributeDemoDates(): void
    {
        $this->command?->line('Redistributing demo dates from previous November to today...');

        $specs = [
            ['customers', 'customer_number', "{$this->prefix}-CUST-%", ['opening_balance_date']],
            ['vendors', 'vendor_number', "{$this->prefix}-VEND-%", ['opening_balance_date']],
            ['products', 'sku', "{$this->prefix}-SKU-%", []],
            ['sales_invoices', 'invoice_no', "{$this->prefix}-INV-%", ['invoice_date'], ['due_date' => 30]],
            ['sales_orders', 'order_no', "{$this->prefix}-SO-%", ['order_date'], ['expected_delivery_date' => 14]],
            ['sales_returns', 'return_no', "{$this->prefix}-SR-%", ['return_date']],
            ['purchase_bills', 'bill_no', "{$this->prefix}-BILL-%", ['bill_date'], ['due_date' => 20]],
            ['purchase_orders', 'order_no', "{$this->prefix}-PO-%", ['order_date'], ['expected_delivery_date' => 12]],
            ['purchase_returns', 'return_no', "{$this->prefix}-PR-%", ['return_date']],
            ['fixed_assets', 'tag_serial_number', "{$this->prefix}-ASSET-%", ['purchase_date']],
            ['employees', 'employee_number', "{$this->prefix}-EMP-%", ['date_of_joining']],
            ['payroll_runs', 'payroll_run_number', "{$this->prefix}-PAY-%", ['start_date'], ['end_date' => 27, 'payment_date' => 30]],
            ['payslips', 'payslip_number', "{$this->prefix}-SLIP-%", ['payslip_date']],
            ['receipts', 'receipt_number', "{$this->prefix}-RCT-%", ['receipt_date']],
            ['payments', 'payment_number', "{$this->prefix}-PMT-%", ['payment_date']],
            ['contras', 'contra_number', "{$this->prefix}-CONTRA-%", ['contra_date']],
            ['debit_notes', 'debit_note_number', "{$this->prefix}-DN-%", ['note_date'], ['approval_date' => 0]],
            ['credit_notes', 'credit_note_number', "{$this->prefix}-CN-%", ['note_date'], ['approval_date' => 0]],
            ['manual_journals', 'journal_number', "{$this->prefix}-JNL-%", ['journal_date'], ['posted_date' => 0]],
            ['recurring_transactions', 'transaction_number', "{$this->prefix}-REC-%", ['start_date'], ['next_date' => 30, 'end_date' => 365]],
            ['transaction_transfers', 'transfer_number', "{$this->prefix}-TRF-%", ['transfer_date']],
        ];

        foreach ($specs as $spec) {
            [$table, $column, $pattern, $dateColumns] = $spec;
            $offsetColumns = $spec[4] ?? [];

            DB::table($table)
                ->where($column, 'like', $pattern)
                ->orderBy('id')
                ->select('id')
                ->chunkById(1000, function ($rows) use ($table, $dateColumns, $offsetColumns) {
                    foreach ($rows as $row) {
                        $dateTime = $this->dateTimeFor((int) $row->id);
                        $values = [
                            'created_at' => $dateTime,
                            'updated_at' => $dateTime->copy()->addMinutes(5),
                        ];

                        foreach ($dateColumns as $dateColumn) {
                            $values[$dateColumn] = $dateTime->toDateString();
                        }

                        foreach ($offsetColumns as $dateColumn => $daysToAdd) {
                            $values[$dateColumn] = $dateTime->copy()->addDays($daysToAdd)->toDateString();
                        }

                        DB::table($table)->where('id', $row->id)->update($values);
                    }
                });
        }

        $this->command?->line('Demo date redistribution complete.');
    }

    private function insertChunks(string $table, array $rows, bool $ignore = true): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            if ($ignore) {
                DB::table($table)->insertOrIgnore($chunk);
            } else {
                DB::table($table)->insert($chunk);
            }
        }
    }
}
