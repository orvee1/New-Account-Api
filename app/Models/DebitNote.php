<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNote extends Model
{
    use SoftDeletes;

    protected $table = 'debit_notes';

    protected $fillable = [
        'company_id',
        'debit_note_number',
        'vendor_id',
        'invoice_reference',
        'note_date',
        'reason',
        'quantity',
        'amount',
        'quality_issue',
        'price_adjustment',
        'damage_amount',
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
        'quality_issue' => 'boolean',
        'price_adjustment' => 'decimal:2',
        'damage_amount' => 'decimal:2',
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
