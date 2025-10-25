<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'return_no' => $this->return_no,
            'return_date' => $this->return_date?->toDateString(),
            'notes' => $this->notes,
            'terms' => $this->terms,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'total_discount' => $this->total_discount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'items' => SalesReturnItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
