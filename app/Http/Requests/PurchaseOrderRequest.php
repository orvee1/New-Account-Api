<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',

            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_unit_id' => 'required|integer',
            'items.*.qty' => 'required|numeric|min:0.01',

            'items.*.rate_unit_id' => 'required|integer',
            'items.*.rate_per_unit' => 'required|numeric|min:0',
        ];
    }
}
