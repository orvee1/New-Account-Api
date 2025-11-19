<?php

// app/Models/ChartAccount.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartAccount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ---------------- Relations ---------------- */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartAccount::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /* ---------------- Scopes ---------------- */
    public function scopeRoot($q)
    {
        return $q->whereNull('parent_id');
    }

    public function scopeGroups($q)
    {
        return $q->where('type', 'group');
    }

    public function scopeLedgers($q)
    {
        return $q->where('type', 'ledger');
    }

    /* ---------------- Accessors ---------------- */
    public function getIsGroupAttribute(): bool
    {
        return $this->type === 'group';
    }

    public function getIsLedgerAttribute(): bool
    {
        return $this->type === 'ledger';
    }
}
