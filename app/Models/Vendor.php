<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','created_by','updated_by',
        'name','display_name','proprietor_name','vendor_number',
        'phone_number','address','nid','email','bank_details',
        'credit_limit','notes',
        'opening_balance','opening_balance_type','opening_balance_date',
        'custom_fields',
        'chart_account_id',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'opening_balance_type' => 'string',
        'opening_balance_date' => 'date',
        'custom_fields' => 'array',
    ];

    public function company()  { return $this->belongsTo(Company::class); }
    public function creator()  { return $this->belongsTo(CompanyUser::class, 'created_by'); }
    public function updater()  { return $this->belongsTo(CompanyUser::class, 'updated_by'); }

    protected static function booted()
    {
        static::creating(function (Vendor $vendor) {
            if ($user = Auth::user()) {
                $vendor->company_id = $vendor->company_id ?: ($user->company_id ?? null);
                $vendor->created_by = $user->id;
                $vendor->updated_by = $user->id;
            }

            if (blank($vendor->display_name)) {
                $vendor->display_name = $vendor->name;
            }

            if (blank($vendor->vendor_number)) {
                $vendor->vendor_number = static::generateVendorNumber($vendor->company_id);
            }
        });

        static::updating(function (Vendor $vendor) {
            if ($user = Auth::user()) {
                $vendor->updated_by = $user->id;
            }
        });
    }

    public static function generateVendorNumber(?int $companyId): string
    {
        $last = static::where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('vendor_number');

        $nextNum = 1;
        if ($last && preg_match('/\d+$/', $last, $m)) {
            $nextNum = (int)$m[0] + 1;
        }
        return 'V' . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
    }
}
