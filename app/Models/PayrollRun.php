<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'payroll_run_number',
        'payroll_month',
        'payroll_year',
        'start_date',
        'end_date',
        'payment_date',
        'total_employees',
        'total_gross_salary',
        'total_deductions',
        'total_net_salary',
        'status',
        'processing_notes',
        'created_by',
        'processed_by',
        'undo_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'total_gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net_salary' => 'decimal:2',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'payroll_run_employees')
            ->withPivot('gross_salary', 'deductions', 'net_salary', 'status')
            ->withTimestamps();
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function undoBy()
    {
        return $this->belongsTo(User::class, 'undo_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeUndone($query)
    {
        return $query->where('status', 'undone');
    }

    // Methods
    public function canProcess()
    {
        return $this->status === 'pending' && $this->total_employees > 0;
    }

    public function canUndo()
    {
        return $this->status === 'processed';
    }
}
