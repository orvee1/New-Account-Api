<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'company_id'      => $this->company_id,
            'customer_id'     => $this->customer_id,
            'sale_type'       => $this->sale_type,
            'invoice_no'      => $this->invoice_no,
            'invoice_date'    => $this->invoice_date?->toDateString(),
            'due_date'        => $this->due_date?->toDateString(),
            'notes'           => $this->notes,
            'terms'           => $this->terms,
            'subtotal'        => $this->subtotal,
            'total_discount'  => $this->total_discount,
            'total_vat'       => $this->total_vat,
            'shipping_amount' => $this->shipping_amount,
            'grand_total'     => $this->grand_total,
            'status'          => $this->status,
            'items'           => SalesInvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
