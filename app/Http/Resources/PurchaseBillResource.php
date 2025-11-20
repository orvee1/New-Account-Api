<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseBillResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => (int) $this->id,
            'bill_no'       => (string) $this->bill_no,
            'bill_date'     => $this->bill_date?->toDateString(),
            'due_date'      => $this->due_date?->toDateString(),
            'vendor'        => [
                'id' => (int) $this->vendor_id,
                'name' => (string) ($this->vendor->name ?? ''),
            ],
            'warehouse_id'  => $this->warehouse_id,
            'notes'         => (string) ($this->notes ?? ''),

            'subtotal'      => (float) $this->subtotal,
            'discount_total'=> (float) $this->discount_total,
            'tax_amount'    => (float) $this->tax_amount,
            'total_amount'  => (float) $this->total_amount,

            'items'         => PurchaseBillItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
