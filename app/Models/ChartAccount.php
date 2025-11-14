<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active'       => 'boolean',
        'opening_balance' => 'decimal:2',
        'opening_date'    => 'date',
    ];

    public function company()
    {return $this->belongsTo(Company::class);}
    public function parent()
    {return $this->belongsTo(ChartAccount::class, 'parent_account_id');}
    public function children()
    {return $this->hasMany(ChartAccount::class, 'parent_account_id');}
    public function creator()
    {return $this->belongsTo(User::class, 'created_by');}
    public function updater()
    {return $this->belongsTo(User::class, 'updated_by');}

    public function scopeSearch($q, ?string $term)
    {
        if (! $term) {
            return $q;
        }

        return $q->where(function ($qq) use ($term) {
            $qq->where('account_name', 'like', "%{$term}%")
                ->orWhere('account_no', 'like', "%{$term}%")
                ->orWhere('detail_type', 'like', "%{$term}%");
        });
    }

    public function isGroup(): bool
    {return $this->node_type === 'group';}
    public function isLedger(): bool
    {return $this->node_type === 'ledger';}

    public function scopeCompany($q, $companyId)
    {
        return $q->where('company_id', $companyId);
    }

    public static function buildPath(?self $parent, int $id = 0): string
    {
        $prefix = $parent?->path ?? '/';
        return rtrim($prefix, '/') . '/' . ($id ?: '{id}') . '/';
    }
}
