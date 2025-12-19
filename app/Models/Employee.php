<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'nid_number',
        'address',
        'department',
        'designation',
        'date_of_joining',
        'employment_type',
        'status',
        'bank_account_number',
        'bank_name',
        'ifsc_code',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function salarySetup()
    {
        return $this->hasOne(SalarySetup::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function payrollRuns()
    {
        return $this->belongsToMany(PayrollRun::class, 'payroll_run_employees')
            ->withPivot('gross_salary', 'deductions', 'net_salary', 'status')
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
