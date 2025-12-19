<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'company_id'          => $this->company_id,
            'customer_id'         => $this->customer_id,
            'customer'            => $this->when($this->relationLoaded('customer'), fn() => $this->customer),
            'sales_order_id'      => $this->sales_order_id,
            'invoice_no'          => $this->invoice_no,
            'invoice_date'        => $this->invoice_date,
            'due_date'            => $this->due_date,
            'warehouse_id'        => $this->warehouse_id,
            'notes'               => $this->notes,
            'status'              => $this->status,
            'subtotal'            => (float) $this->subtotal,
            'discount_total'      => (float) $this->discount_total,
            'tax_amount'          => (float) $this->tax_amount,
            'total_amount'        => (float) $this->total_amount,
            'paid_amount'         => (float) $this->paid_amount,
            'items'               => $this->when($this->relationLoaded('items'), fn() => $this->items),
            'payments'            => $this->when($this->relationLoaded('payments'), fn() => $this->payments),
            'returns'             => $this->when($this->relationLoaded('returns'), fn() => $this->returns),
            'created_by'          => $this->created_by,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
