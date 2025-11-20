<?php

// app/Observers/CompanyObserver.php
namespace App\Observers;

use App\Models\Company;
use App\Services\ChartAccountService;

class CompanyObserver
{
    public function __construct(protected ChartAccountService $svc) {}

    public function created(Company $company): void
    {
        // নতুন কোম্পানি হলে ডিফল্ট COA বসাও
        $this->svc->seedDefaultForCompany($company->id);
    }
}
