<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesJournalEntry extends Model
{
    protected $fillable = [
        'invoice_id',
        'account_id',
        'dr_cr',
        'amount',
        'narration',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartAccount::class, 'account_id');
    }
}
