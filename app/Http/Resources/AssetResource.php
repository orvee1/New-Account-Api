<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray($request)
    {
     return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'category'           => $this->category,
            'purchaseDate'       => optional($this->purchase_date)->format('Y-m-d'),
            'amount'             => (float) $this->amount,
            'vendorName'         => $this->vendor_name,
            'purchaseMode'       => $this->purchase_mode,
            'paymentMode'        => $this->payment_mode,
            'usefulLife'         => $this->useful_life,
            'salvageValue'       => (float) $this->salvage_value,
            'depreciationMethod' => $this->depreciation_method,
            'frequency'          => $this->frequency,
            'depreciationRate'   => $this->depreciation_rate !== null ? (float) $this->depreciation_rate : null,
            'assetLocation'      => $this->asset_location,
            'tagSerialNumber'    => $this->tag_serial_number,
            'status'             => $this->status,

            'companyId'          => $this->company_id,
            'createdAt'          => $this->created_at?->toISOString(),
            'updatedAt'          => $this->updated_at?->toISOString(),
        ];
    }
}
