<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => (int) $this->id,
            'product'        => [
                'id'   => (int) $this->product_id,
                'name' => (string) ($this->product->name ?? ''),
                'base_unit_name' => (string) ($this->product->base_unit_name ?? ''),
            ],
            'qty'            => (float) $this->qty,
            'qty_unit_id'    => (int) $this->qty_unit_id,
            'qty_base'       => (float) $this->qty_base,
            'rate_per_unit'  => (float) $this->rate_per_unit,
            'rate_unit_id'   => (int) $this->rate_unit_id,
            'rate_per_base'  => (float) $this->rate_per_base,
            'discount_percent'=> (float) $this->discount_percent,
            'discount_amount' => (float) $this->discount_amount,
            'line_subtotal'   => (float) $this->line_subtotal,
            'line_total'      => (float) $this->line_total,
            'warehouse_id'    => $this->warehouse_id,
            'batch'           => [
                'id' => $this->batch_id,
                'no' => $this->batch_no,
                'manufactured_at' => optional($this->manufactured_at)->toDateString(),
                'expired_at'      => optional($this->expired_at)->toDateString(),
            ],
        ];
    }
}
