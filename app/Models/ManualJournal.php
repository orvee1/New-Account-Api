<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManualJournal extends Model
{
    use SoftDeletes;

    protected $table = 'manual_journals';

    protected $fillable = [
        'company_id',
        'journal_number',
        'journal_date',
        'debit_account_id',
        'credit_account_id',
        'debit_amount',
        'credit_amount',
        'description',
        'reference_number',
        'narration',
        'status',
        'posted_by',
        'posted_date',
        'recorded_by',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function debitAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'credit_account_id');
    }
}
