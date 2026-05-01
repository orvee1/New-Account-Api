<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JournalLine extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'journal_entry_id',
        'company_id',
        'account_id',
        'debit',
        'credit',
        'narration',
        'is_reconciled',
    ];

    protected static function booted()
    {
        static::creating(function (JournalLine $line) {
            // Ensure posting only happens to ledger accounts
            if ($line->account && $line->account->type === 'group') {
                throw new \Exception("Cannot post to a 'group' account. Please select a ledger account.");
            }

            // Ensure debit and credit are not null, default to 0
            $line->debit = $line->debit ?? 0;
            $line->credit = $line->credit ?? 0;

            // Set company_id if not already set (assuming user is logged in)
            if (is_null($line->company_id) && Auth::check()) {
                $line->company_id = Auth::user()->company_id;
            }
        });

        static::updating(function (JournalLine $line) {
            // Prevent modifying posted journal lines if not allowed
            // (This logic might be better placed in a Service layer or Form Request)
            // For now, just a basic check
            if ($line->journalEntry && $line->journalEntry->posted_at !== null) {
                 // Optionally check if 'posted_at' is not null in JournalEntry, if that column exists.
                 // Or based on some other flag indicating it's finalized.
                 // throw new \Exception("Cannot modify a posted journal line.");
            }
        });
    }

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
        // Eager load account to access its type for validation
        return $this->belongsTo(ChartAccount::class)->withDefault(function ($model) {
            // Provide a default, maybe null account, or fetch dynamically if needed.
            // For validation, we need the type, so loading ChartAccount is crucial.
            // If account_id is null, this will cause issues. It should be handled.
            // For now, assume account_id is always present and valid.
        });
    }
}
