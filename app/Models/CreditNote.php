<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use SoftDeletes;

    protected $table = 'credit_notes';

    protected $fillable = [
        'company_id',
        'credit_note_number',
        'customer_id',
        'invoice_reference',
        'note_date',
        'reason',
        'quantity_returned',
        'amount',
        'discount_amount',
        'return_reason',
        'claim_amount',
        'description',
        'status',
        'approved_by',
        'approval_date',
        'recorded_by',
    ];

    protected $casts = [
        'note_date' => 'date',
        'approval_date' => 'date',
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'claim_amount' => 'decimal:2',
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
