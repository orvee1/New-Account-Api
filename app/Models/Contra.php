<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contra extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'contra_number',
        'from_account_id',
        'to_account_id',
        'from_bank_id',
        'to_bank_id',
        'contra_date',
        'amount',
        'debit_amount',
        'credit_amount',
        'reference_number',
        'description',
        'status',
        'reconciled',
        'recorded_by',
    ];

    protected $casts = [
        'contra_date' => 'date',
        'amount' => 'decimal:2',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'reconciled' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function fromAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'to_account_id');
    }
}
