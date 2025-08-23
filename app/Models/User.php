<?php

namespace App\Models;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail,CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'phone',
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users')
                    ->withPivot('role', 'status', 'invited_at', 'joined_at', 'last_login_at', 'is_primary', 'permissions', 'notes', 'created_by', 'updated_by')
                    ->withTimestamps();
    }

    public function ownedComapnies()
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    public function createdCompanies()
    {
        return $this->hasMany(Company::class, 'created_by');
    }

    public function updatedCompanies()
    {
        return $this->hasMany(Company::class, 'updated_by');
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function admin_devices()
    {
        return $this->hasMany(AdminDevice::class, 'user_id');
    }

    public function admin_device_requests()
    {
        return $this->hasMany(AdminDeviceRequest::class, 'user_id', 'id');
    }
}
