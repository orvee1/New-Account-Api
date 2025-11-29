<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalLine extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'journal_entry_id',
        'company_id',
        'account_id',
        'debit',
        'credit',
        'memo',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartAccount::class);
    }
}

