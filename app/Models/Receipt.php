<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'receipt_number',
        'customer_id',
        'receipt_date',
        'amount_received',
        'payment_mode',
        'reference_number',
        'description',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount_received' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
