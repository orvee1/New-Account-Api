<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseBillResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                       => (int) $this->id,
            'bill_no'                  => (string) $this->bill_no,
            'bill_date'                => $this->bill_date instanceof \DateTimeInterface ? $this->bill_date->format('Y-m-d') : $this->bill_date,
            'due_date'                 => $this->due_date instanceof \DateTimeInterface ? $this->due_date->format('Y-m-d') : $this->due_date,
            'supplier_ref_no'          => (string) $this->supplier_ref_no,
            'vendor'                   => [
                'id' => (int) $this->vendor_id,
                'name' => (string) ($this->vendor->name ?? ''),
            ],
            'warehouse_id'             => $this->warehouse_id,
            'notes'                    => (string) ($this->notes ?? ''),
            'vat_mode'                 => (string) $this->vat_mode,

            'subtotal'                 => (float) $this->subtotal,
            'trade_discount_amt'       => (float) $this->trade_discount_amt,
            'line_discount_amt'        => (float) $this->line_discount_amt,
            'taxable_amount'           => (float) $this->taxable_amount,
            'vat_amount'               => (float) $this->vat_amount,
            'ait_amount'               => (float) $this->ait_amount,
            'bill_discount_amt'        => (float) $this->bill_discount_amt,
            'bill_discount_account_id' => $this->bill_discount_account_id,
            'total_amount'             => (float) $this->total_amount,
            'payment_status'           => (string) $this->payment_status,
            'status'                   => (string) $this->status,
            'items'                    => PurchaseBillItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
