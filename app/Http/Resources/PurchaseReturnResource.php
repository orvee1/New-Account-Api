<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => (int) $this->id,
            'return_no'     => (string) $this->return_no,
            'return_date'   => $this->return_date?->toDateString(),
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

            'items'         => PurchaseReturnItemResource::collection($this->whenLoaded('items')),
        ];;
    }
}
