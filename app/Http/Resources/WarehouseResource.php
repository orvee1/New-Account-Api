<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => (string) $this->id,
            'name'       => $this->name,
            'is_default' => (bool) $this->is_default,
            'company_id' => (int) $this->company_id,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
