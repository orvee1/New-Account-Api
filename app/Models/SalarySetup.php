<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalarySetup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'basic_salary',
        'house_rent_allowance',
        'medical_allowance',
        'conveyance_allowance',
        'other_allowances',
        'gross_salary',
        'provident_fund_percent',
        'provident_fund_amount',
        'income_tax_percent',
        'income_tax_amount',
        'other_deductions',
        'net_salary',
        'effective_date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'basic_salary' => 'decimal:2',
        'house_rent_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'conveyance_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'provident_fund_percent' => 'decimal:2',
        'provident_fund_amount' => 'decimal:2',
        'income_tax_percent' => 'decimal:2',
        'income_tax_amount' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Methods
    public function calculateGrossSalary()
    {
        return $this->basic_salary
            + $this->house_rent_allowance
            + $this->medical_allowance
            + $this->conveyance_allowance
            + $this->other_allowances;
    }

    public function calculateNetSalary()
    {
        return $this->gross_salary
            - $this->provident_fund_amount
            - $this->income_tax_amount
            - $this->other_deductions;
    }
}
