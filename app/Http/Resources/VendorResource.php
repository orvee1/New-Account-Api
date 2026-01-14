<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'company_id'           => $this->company_id,
            'name'                 => $this->name,
            'display_name'         => $this->display_name,
            'proprietor_name'      => $this->proprietor_name,
            'vendor_number'        => $this->vendor_number,
            'phone_number'         => $this->phone_number,
            'address'              => $this->address,
            'nid'                  => $this->nid,
            'email'                => $this->email,
            'bank_details'         => $this->bank_details,
            'credit_limit'         => (float) $this->credit_limit,
            'notes'                => $this->notes,
            'opening_balance'      => (float) $this->opening_balance,
            'opening_balance_date' => optional($this->opening_balance_date)->toDateString(),
            'chart_account_id'     => $this->chart_account_id,
            'custom_fields'        => $this->custom_fields ?? [],
            'created_by'           => $this->created_by,
            'updated_by'           => $this->updated_by,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
