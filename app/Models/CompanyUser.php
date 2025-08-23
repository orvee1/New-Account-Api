<?php

namespace App\Models;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class CompanyUser extends Authenticatable implements MustVerifyEmail,CanResetPassword
{
    use HasFactory, SoftDeletes, HasApiTokens;

    protected $table = 'company_users';

    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'status',
        'invited_at',
        'joined_at',
        'last_login_at',
        'is_primary',
        'permissions',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_primary' => 'boolean',
        'permissions' => 'json',
    ];

    public $incrementing = true;

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
