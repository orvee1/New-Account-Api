<?php

namespace App\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class CompanyUser extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $table = 'company_users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'permissions'   => 'array',
        'is_primary'    => 'boolean',
        'invited_at'    => 'datetime',
        'joined_at'     => 'datetime',
        'last_login_at' => 'datetime',
        'status'        => 'string',
        'role'          => 'string',
    ];

    protected $appends = ['photo_url', 'is_active'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $uid = Auth::id();
            if ($uid) {
                if (is_null($model->created_by)) $model->created_by = $uid;
                if (is_null($model->updated_by)) $model->updated_by = $uid;
            }
        });

        static::updating(function (self $model) {
            $uid = Auth::id();
            if ($uid) $model->updated_by = $uid;
        });
    }

    /* ---------------- Accessors ---------------- */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /* ---------------- Mutators ---------------- */
    public function setPasswordAttribute($value): void
    {
        // null/empty হলে সেট করবো না
        if ($value === null || $value === '') {
            $this->attributes['password'] = $value;
            return;
        }

        $this->attributes['password'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

    public function setEmailAttribute($value): void
    {
        // খালি string এলে null করি; lowercase করি
        $this->attributes['email'] = $value ? strtolower(trim($value)) : null;
    }

    public function setPhoneNumberAttribute($value): void
    {
        $this->attributes['phone_number'] = $value ? trim($value) : $value;
    }

    public function setPermissionsAttribute($value): void
    {
        // সবসময় array হিসেবে স্টোর—null এলে null-ই থাকবে (migration অনুযায়ী)
        if (is_null($value)) {
            $this->attributes['permissions'] = null;
            return;
        }
        $this->attributes['permissions'] = is_array($value) ? json_encode($value) : $value;
    }

    /* ---------------- Relationships ---------------- */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

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

    /* ---------------- Scopes ---------------- */
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeForCompany($q, $companyId)
    {
        return $q->where('company_id', $companyId);
    }

    public function scopeRole($q, $roles)
    {
        $r = is_array($roles) ? $roles : [$roles];
        return $q->whereIn('role', $r);
    }

    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;

        return $q->where(function ($x) use ($term) {
            $x->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone_number', 'like', "%{$term}%");
        });
    }

    public function scopePrimary($q)
    {
        return $q->where('is_primary', true);
    }

    /* ---------------- Helpers ---------------- */
    public function isOwner(): bool      { return $this->role === 'owner'; }
    public function isAdmin(): bool      { return $this->role === 'admin'; }
    public function isAccountant(): bool { return $this->role === 'accountant'; }
    public function isViewer(): bool     { return $this->role === 'viewer'; }

    public function hasPermission(string $key, bool $defaultForAdmins = true): bool
    {
        if ($this->isOwner() || ($defaultForAdmins && $this->isAdmin())) return true;

        $perms = $this->permissions ?? [];
        if (!is_array($perms)) return false;

        $val = data_get($perms, $key);
        return $val === true || $val === 1 || $val === '1' || $val === 'true';
    }

    public function markInvited(): void
    {
        $this->forceFill(['invited_at' => now()])->save();
    }

    public function markJoined(): void
    {
        $this->forceFill(['joined_at' => now(), 'status' => 'active'])->save();
    }

    public function markLoggedIn(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
    }
}
