<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetDepreciationResource extends JsonResource
{
    public function toArray($request)
    {
        // নিশ্চিত হও controller এ with('asset:id,name,tag_serial_number') করা হবে
        return [
            'id'         => $this->id,
            'assetId'    => $this->fixed_asset_id,
            'assetName'  => $this->asset?->name,
            'tagNo'      => $this->asset?->tag_serial_number,
            'method'     => $this->method,
            'frequency'  => $this->frequency,
            'timeOfEntry'=> $this->time_of_entry,
            'amount'     => (float) $this->amount,
            'debitAcc'   => $this->debit_acc_name,
            'creditAcc'  => $this->credit_acc_name,
            'startDate'  => optional($this->start_date)->format('Y-m-d'),
            'endDate'    => optional($this->end_date)->format('Y-m-d'),
            'isActive'   => (bool) $this->is_active,

            'companyId'  => $this->company_id,
            'createdAt'  => $this->created_at?->toISOString(),
            'updatedAt'  => $this->updated_at?->toISOString(),
        ];
    }
}
