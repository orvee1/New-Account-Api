<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\ChartAccountService;

class DefaultChartOfAccountsSeeder extends Seeder
{
    public function __construct(protected ChartAccountService $svc) {}

    public function run(): void
    {
        // Demo: seed to company_id = 1
        $this->svc->seedDefaultForCompany(1);
    }
}
