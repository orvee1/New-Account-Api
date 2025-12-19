<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'sales_invoice_id',
        'payment_no',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'notes',
        'status',
        'created_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
