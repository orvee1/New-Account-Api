<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseBillItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                       => (int) $this->id,
            'product'                  => [
                'id'   => (int) $this->product_id,
                'name' => (string) ($this->product->name ?? ''),
            ],
            'purchase_uom_id'          => (int) $this->purchase_uom_id,
            'price_uom_id'             => (int) $this->price_uom_id,
            'quantity_in_purchase_uom' => (float) $this->quantity_in_purchase_uom,
            'quantity_in_base_uom'     => (float) $this->quantity_in_base_uom,
            'unit_price_original'      => (float) $this->unit_price_original,
            'trade_discount_pct'       => (float) $this->trade_discount_pct,
            'trade_discount_amt'       => (float) $this->trade_discount_amt,
            'net_unit_price'           => (float) $this->net_unit_price,
            'line_gross_amount'        => (float) $this->line_gross_amount,
            'line_discount_pct'        => (float) $this->line_discount_pct,
            'line_discount_amt'        => (float) $this->line_discount_amt,
            'line_subtotal'            => (float) $this->line_subtotal,
            'vat_rate'                 => (float) $this->vat_rate,
            'vat_amount'               => (float) $this->vat_amount,
            'ait_rate'                 => (float) $this->ait_rate,
            'ait_amount'               => (float) $this->ait_amount,
            'net_unit_cost'            => (float) $this->net_unit_cost,
            'weighted_avg_cost_before' => (float) $this->weighted_avg_cost_before,
            'weighted_avg_cost_after'  => (float) $this->weighted_avg_cost_after,
            'line_total'               => (float) $this->line_total,
        ];
    }
}
