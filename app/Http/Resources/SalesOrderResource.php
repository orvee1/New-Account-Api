<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'order_no' => $this->order_no,
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'subtotal' => $this->subtotal,
            'total_discount' => $this->total_discount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'items' => SalesOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
