<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'sales_invoice_id',
        'return_no',
        'return_date',
        'reason',
        'notes',
        'subtotal',
        'discount_total',
        'tax_amount',
        'total_amount',
        'created_by'
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function items()
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
