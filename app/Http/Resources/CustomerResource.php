<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'displayName' => $this->display_name,
            'proprietorName' => $this->proprietor_name,
            'customerNumber' => $this->customer_number,
            'phoneNumber' => $this->phone_number,
            'email' => $this->email,
            'address' => $this->address,
            'nid' => $this->nid,
            'bankDetails' => $this->bank_details,
            'notes' => $this->notes,
            'creditLimit' => (float) $this->credit_limit,
            'openingBalance' => (float) $this->opening_balance,
            'openingBalanceDate' => optional($this->opening_balance_date)->toDateString(),
            'chartAccountId' => $this->chart_account_id,
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'deletedAt' => optional($this->deleted_at)?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
