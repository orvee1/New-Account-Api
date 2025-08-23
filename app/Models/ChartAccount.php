<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id','account_no','name','type','detail_type',
        'parent_id','is_header','is_active','balance','created_by','updated_by'
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'is_active' => 'boolean',
        'balance'   => 'decimal:2',
    ];

    public function company()   { return $this->belongsTo(Company::class); }
    public function parent()    { return $this->belongsTo(ChartAccount::class, 'parent_id'); }
    public function children()  { return $this->hasMany(ChartAccount::class, 'parent_id'); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function updater()   { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopeSearch($q, ?string $term) {
        if (!$term) return $q;
        return $q->where(function($qq) use ($term){
            $qq->where('name','like',"%{$term}%")
               ->orWhere('account_no','like',"%{$term}%")
               ->orWhere('detail_type','like',"%{$term}%");
        });
    }

    public function scopeCompany($q, $companyId) {
        return $q->where('company_id', $companyId);
    }
}
