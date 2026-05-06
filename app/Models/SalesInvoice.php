<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'sales_order_id',
        'invoice_no',
        'invoice_date',
        'due_date',
        'warehouse_id',
        'notes',
        'status',
        'subtotal',
        'trade_discount_amt',
        'line_discount_amt',
        'taxable_amount',
        'vat_amount',
        'ait_amount',
        'invoice_discount_amt',
        'invoice_discount_account_id',
        'grand_total',
        'vat_mode',
        'discount_total',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'created_by'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'trade_discount_amt' => 'decimal:2',
        'line_discount_amt' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'ait_amount' => 'decimal:2',
        'invoice_discount_amt' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    // Relationships
    public function journalEntries()
    {
        return $this->hasMany(SalesJournalEntry::class, 'invoice_id');
    }

    public function invoiceDiscountAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'invoice_discount_account_id');
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function payments()
    {
        return $this->hasMany(SalesPayment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
