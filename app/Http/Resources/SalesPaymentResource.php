<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesPaymentResource extends JsonResource
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
            'sales_invoice_id'    => $this->sales_invoice_id,
            'sales_invoice'       => $this->when($this->relationLoaded('salesInvoice'), fn() => $this->salesInvoice),
            'payment_no'          => $this->payment_no,
            'payment_date'        => $this->payment_date,
            'amount'              => (float) $this->amount,
            'payment_method'      => $this->payment_method,
            'reference_no'        => $this->reference_no,
            'notes'               => $this->notes,
            'status'              => $this->status,
            'created_by'          => $this->created_by,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
