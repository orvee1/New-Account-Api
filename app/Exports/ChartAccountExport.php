<?php

namespace App\Exports;

use App\Models\ChartAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChartAccountExport implements FromCollection, WithHeadings, WithMapping
{
    protected $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection()
    {
        return ChartAccount::where('company_id', $this->companyId)
            ->orderBy('path')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Parent ID',
            'Code',
            'Name',
            'Type',
            'Base Type',
            'Normal Balance',
            'Is Active'
        ];
    }

    public function map($account): array
    {
        return [
            $account->id,
            $account->parent_id,
            $account->code,
            $account->name,
            $account->type,
            $account->base_type,
            $account->normal_balance,
            $account->is_active ? 'Yes' : 'No',
        ];
    }
}
