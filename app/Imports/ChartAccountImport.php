<?php

namespace App\Imports;

use App\Models\ChartAccount;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ChartAccountImport implements ToModel, WithHeadingRow
{
    protected $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function model(array $row)
    {
        // Skip if name is missing
        if (empty($row['name'])) {
            return null;
        }

        return new ChartAccount([
            'company_id'     => $this->companyId,
            'name'           => $row['name'],
            'type'           => $row['type'] ?? 'ledger',
            'code'           => $row['code'] ?? null,
            'base_type'      => $row['base_type'] ?? null,
            'normal_balance' => $row['normal_balance'] ?? null,
            'parent_id'      => $row['parent_id'] ?? null,
            'slug'           => Str::slug($row['name']),
            'is_active'      => true,
        ]);
    }
}
