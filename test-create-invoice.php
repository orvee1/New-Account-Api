<?php

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;

require_once 'bootstrap/app.php';

$app = app();

$invoice = SalesInvoice::create([
    'company_id' => 1,
    'customer_id' => 21,
    'invoice_no' => 'INV-001',
    'invoice_date' => '2025-12-23',
    'subtotal' => 55000,
    'total_amount' => 55000,
    'status' => 'draft',
    'created_by' => 1,
]);

SalesInvoiceItem::create([
    'sales_invoice_id' => $invoice->id,
    'product_id' => 1,
    'quantity' => 1,
    'unit_price' => 55000,
    'line_total' => 55000,
]);

echo "Invoice created: {$invoice->invoice_no} (ID: {$invoice->id})\n";
