<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'qty_input' => $this->qty_input,
            'qty_unit_id' => $this->qty_unit_id,
            'qty_unit_factor' => $this->qty_unit_factor,
            'base_qty' => $this->base_qty,
            'billing_unit_id' => $this->billing_unit_id,
            'billing_unit_factor' => $this->billing_unit_factor,
            'rate_per_billing_unit' => $this->rate_per_billing_unit,
            'unit_price_base' => $this->unit_price_base,
            'line_subtotal' => $this->line_subtotal,
            'discount_percent' => $this->discount_percent,
            'discount_amount' => $this->discount_amount,
            'line_total' => $this->line_total,
        ];
    }
}
