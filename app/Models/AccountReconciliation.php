<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountReconciliation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_id',
        'user_id',
        'reconciliation_date',
        'beginning_balance',
        'ending_balance',
        'cleared_deposits',
        'cleared_payments',
        'difference',
        'cleared_transactions',
        'status',
        'notes',
    ];

    protected $casts = [
        'cleared_transactions' => 'array',
        'reconciliation_date' => 'datetime',
        'beginning_balance' => 'decimal:2',
        'ending_balance' => 'decimal:2',
        'cleared_deposits' => 'decimal:2',
        'cleared_payments' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    // Relations
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
