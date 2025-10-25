<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EstimateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id, 'company_id'             => $this->company_id, 'customer_id'                     => $this->customer_id,
            'estimate_no' => $this->estimate_no, 'estimate_date' => $this->estimate_date?->toDateString(), 'expiry_date' => $this->expiry_date?->toDateString(),
            'is_draft'    => $this->is_draft, 'notes'            => $this->notes,
            'subtotal'    => $this->subtotal, 'total_discount'   => $this->total_discount, 'grand_total'                 => $this->grand_total,
            'items'       => EstimateItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
