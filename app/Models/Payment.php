<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'payment_number',
        'vendor_id',
        'payment_date',
        'amount_paid',
        'payment_mode',
        'invoice_reference',
        'cheque_number',
        'description',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount_paid' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
