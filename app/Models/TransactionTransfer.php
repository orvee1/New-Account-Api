<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionTransfer extends Model
{
    use SoftDeletes;

    protected $table = 'transaction_transfers';

    protected $fillable = [
        'company_id',
        'transfer_number',
        'from_account_id',
        'to_account_id',
        'amount',
        'transfer_date',
        'reference_number',
        'description',
        'notes',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
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
