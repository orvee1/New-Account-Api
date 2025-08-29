<?php

namespace App\Models;

use App\Models\Company;
use App\Models\User; // for created_by / updated_by / deleted_by (main users table)
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CompanyUser extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $table = 'company_users';

    /**
     * If you use a custom auth guard (recommended), set it in config/auth.php
     * and reference it where needed. No change needed here.
     */

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'permissions'   => 'array',
        'is_primary'    => 'boolean',
        'invited_at'    => 'datetime',
        'joined_at'     => 'datetime',
        'last_login_at' => 'datetime',
        'status'        => 'string',
        'role'          => 'string',
    ];

    protected $appends = [
        'photo_url',
        'is_active',
    ];

    /* ----------------------------------------------------------------------
     |  Accessors
     * -------------------------------------------------------------------- */

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /* ----------------------------------------------------------------------
     |  Mutators
     * -------------------------------------------------------------------- */

    public function setPasswordAttribute($value): void
    {
        if (! $value) {
            $this->attributes['password'] = $value;
            return;
        }

        // Avoid double-hashing; hash only if needed
        $this->attributes['password'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

    /* ----------------------------------------------------------------------
     |  Relationships
     * -------------------------------------------------------------------- */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Audit relationships to main users table
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

    /* ----------------------------------------------------------------------
     |  Scopes
     * -------------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRole($query, $roles)
    {
        $roles = is_array($roles) ? $roles : [$roles];
        return $query->whereIn('role', $roles);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone_number', 'like', "%{$term}%");
        });
    }

    /* ----------------------------------------------------------------------
     |  Helpers
     * -------------------------------------------------------------------- */

    public function isOwner(): bool       { return $this->role === 'owner'; }
    public function isAdmin(): bool       { return $this->role === 'admin'; }
    public function isAccountant(): bool  { return $this->role === 'accountant'; }
    public function isViewer(): bool      { return $this->role === 'viewer'; }

    public function hasPermission(string $key, bool $defaultForAdmins = true): bool
    {
        // Owners/Admins pass by default (configurable)
        if ($this->isOwner() || ($defaultForAdmins && $this->isAdmin())) {
            return true;
        }

        $perms = $this->permissions ?? [];
        if (!is_array($perms)) return false;

        // Support both flat boolean map and nested keys (e.g. "invoices.create")
        $val = data_get($perms, $key);

        // Treat truthy values as allowed
        return $val === true || $val === 1 || $val === '1' || $val === 'true';
    }
}
