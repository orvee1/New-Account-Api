<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'status' => 'string',
    ];

    protected $appends = [
        'logo_url',
        'is_active',
    ];

    /* ----------------------------- Accessors ----------------------------- */

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /* ------------------------------ Scopes -------------------------------- */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /* --------------------------- Relationships --------------------------- */

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function users()
    {
        return $this->hasMany(CompanyUser::class, 'company_id');
    }
}
