<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'recurring_transactions';

    protected $fillable = [
        'company_id',
        'transaction_number',
        'type',
        'frequency',
        'from_account_id',
        'to_account_id',
        'amount',
        'description',
        'start_date',
        'end_date',
        'next_date',
        'status',
        'is_active',
        'recorded_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_date' => 'date',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
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
