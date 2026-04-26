<?php

namespace App\Services;

use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Support\Str;

class PartyAccountService
{
    public function createVendorAccount(Vendor $vendor): ?ChartAccount
    {
        $parent = $this->getVendorPayableGroup($vendor->company_id);
        if (!$parent) {
            return null;
        }

        $label = $vendor->display_name ?: $vendor->name;
        $suffix = $vendor->vendor_number ? " ({$vendor->vendor_number})" : '';
        $name = "Vendor - {$label}{$suffix}";

        return ChartAccount::firstOrCreate(
            [
                'company_id' => $vendor->company_id,
                'parent_id' => $parent->id,
                'name' => $name,
            ],
            [
                'type' => 'ledger',
                'slug' => Str::slug($name),
                'is_active' => true,
            ]
        );
    }

    public function createCustomerAccount(Customer $customer): ?ChartAccount
    {
        $parent = $this->getCustomerReceivableGroup($customer->company_id);
        if (!$parent) {
            return null;
        }

        $label = $customer->display_name ?: $customer->name;
        $suffix = $customer->customer_number ? " ({$customer->customer_number})" : '';
        $name = "Customer - {$label}{$suffix}";

        return ChartAccount::firstOrCreate(
            [
                'company_id' => $customer->company_id,
                'parent_id' => $parent->id,
                'name' => $name,
            ],
            [
                'type' => 'ledger',
                'slug' => Str::slug($name),
                'is_active' => true,
            ]
        );
    }

    private function getVendorPayableGroup(int $companyId): ?ChartAccount
    {
        $controlAccount = $this->findAccountsPayableGroup($companyId);
        if (!$controlAccount) return null;

        // Try to find by name first to avoid duplicate entries with different codes
        $group = ChartAccount::where('company_id', $companyId)
            ->where('parent_id', $controlAccount->id)
            ->where(function($q) {
                $q->where('name', 'Vendor Payable')
                  ->orWhere('name', 'Vendor payable');
            })
            ->first();

        if ($group) return $group;

        // If not found by name, check if the specific code is already taken
        $code = $controlAccount->code ? $controlAccount->code . '.1' : null;
        if ($code) {
            $existingByCode = ChartAccount::where('company_id', $companyId)->where('code', $code)->first();
            if ($existingByCode) {
                // If code is taken by something else, let the system generate a new one
                $code = null; 
            }
        }

        return ChartAccount::create([
            'company_id' => $companyId,
            'parent_id' => $controlAccount->id,
            'name' => 'Vendor Payable',
            'type' => 'group',
            'slug' => 'vendor-payable',
            'is_active' => true,
            'code' => $code,
        ]);
    }

    private function getCustomerReceivableGroup(int $companyId): ?ChartAccount
    {
        $controlAccount = $this->findAccountsReceivableGroup($companyId);
        if (!$controlAccount) return null;

        $group = ChartAccount::where('company_id', $companyId)
            ->where('parent_id', $controlAccount->id)
            ->where(function($q) {
                $q->where('name', 'Customer Receivable')
                  ->orWhere('name', 'Customer receivable');
            })
            ->first();

        if ($group) return $group;

        $code = $controlAccount->code ? $controlAccount->code . '.1' : null;
        if ($code) {
            $existingByCode = ChartAccount::where('company_id', $companyId)->where('code', $code)->first();
            if ($existingByCode) {
                $code = null;
            }
        }

        return ChartAccount::create([
            'company_id' => $companyId,
            'parent_id' => $controlAccount->id,
            'name' => 'Customer Receivable',
            'type' => 'group',
            'slug' => 'customer-receivable',
            'is_active' => true,
            'code' => $code,
        ]);
    }

    private function findAccountsPayableGroup(int $companyId): ?ChartAccount
    {
        $query = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('type', 'group');

        $parent = (clone $query)
            ->whereIn('slug', ['ac-payable', 'a-c-payable'])
            ->first();

        if ($parent) return $parent;

        $parent = (clone $query)
            ->whereIn('name', ['A/C Payable', 'AC Payable', 'Accounts Payable'])
            ->first();

        if ($parent) return $parent;

        return (clone $query)
            ->where('code', 'like', '2.1.1%')
            ->first();
    }

    private function findAccountsReceivableGroup(int $companyId): ?ChartAccount
    {
        $query = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('type', 'group');

        $parent = (clone $query)
            ->where('slug', 'account-receivable')
            ->first();

        if ($parent) return $parent;

        $parent = (clone $query)
            ->whereIn('name', ['Account Receivable', 'Accounts Receivable'])
            ->first();

        if ($parent) return $parent;

        return (clone $query)
            ->where('code', 'like', '1.1.4%')
            ->first();
    }
}
