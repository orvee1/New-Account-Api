<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'company_id'           => $this->company_id,
            'customer_id'          => $this->customer_id,
            'customer'             => $this->when($this->relationLoaded('customer'), fn() => $this->customer),
            'sales_invoice_id'     => $this->sales_invoice_id,
            'return_no'            => $this->return_no,
            'return_date'          => $this->return_date,
            'reason'               => $this->reason,
            'notes'                => $this->notes,
            'subtotal'             => (float) $this->subtotal,
            'discount_total'       => (float) $this->discount_total,
            'tax_amount'           => (float) $this->tax_amount,
            'total_amount'         => (float) $this->total_amount,
            'items'                => $this->when($this->relationLoaded('items'), fn() => $this->items),
            'created_by'           => $this->created_by,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
