<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'company_id'              => $this->company_id,
            'customer_id'             => $this->customer_id,
            'customer'                => $this->when($this->relationLoaded('customer'), fn() => $this->customer),
            'order_no'                => $this->order_no,
            'order_date'              => $this->order_date,
            'expected_delivery_date'  => $this->expected_delivery_date,
            'notes'                   => $this->notes,
            'status'                  => $this->status,
            'subtotal'                => (float) $this->subtotal,
            'discount_total'          => (float) $this->discount_total,
            'tax_amount'              => (float) $this->tax_amount,
            'total_amount'            => (float) $this->total_amount,
            'items'                   => $this->when($this->relationLoaded('items'), fn() => $this->items),
            'invoices'                => $this->when($this->relationLoaded('invoices'), fn() => $this->invoices),
            'created_by'              => $this->created_by,
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,
        ];
    }
}
