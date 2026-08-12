<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'entry_date',
        'reference_id',
        'reference_type',
        'description',
        'status',
        'entry_type',
        'posted_at',
        'posted_by',
        'reversed_entry_id',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}
