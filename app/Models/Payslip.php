<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payslip extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'payroll_run_id',
        'employee_id',
        'payslip_number',
        'payslip_date',
        'month',
        'year',
        'basic_salary',
        'house_rent_allowance',
        'medical_allowance',
        'conveyance_allowance',
        'other_allowances',
        'gross_salary',
        'provident_fund',
        'income_tax',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'status',
        'created_by',
    ];

    protected $casts = [
        'payslip_date' => 'date',
        'basic_salary' => 'decimal:2',
        'house_rent_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'conveyance_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'provident_fund' => 'decimal:2',
        'income_tax' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
