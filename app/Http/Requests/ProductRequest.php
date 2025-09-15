<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest {
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array {
        $id = $this->route('product')?->id;

        return [
            'product_type' => ['required', Rule::in(['Stock','Non-stock','Service','Combo'])],
            'name'         => ['required','string','max:255'],
            'code'         => [
                'nullable','string','max:100',
                Rule::unique('products')->ignore($id)->where(fn($q) =>
                    $q->where('company_id', auth()->user()->company_id)
                ),
            ],
            'description'  => ['nullable','string'],
            'category'     => ['nullable','string','max:100'],

            'has_warranty'  => ['boolean'],
            'warranty_days' => ['nullable','integer','min:0'],

            'costing_price' => ['nullable','numeric','min:0'],
            'sales_price'   => ['nullable','numeric','min:0'],

            // openingQuantity only accepted on create for Stock
            'opening_quantity' => ['nullable','numeric','min:0'],

            // Units
            'units'                  => ['array'],
            'units.*.name'           => ['required_with:units','string','max:50'],
            'units.*.factor'         => ['required_with:units','numeric','min:0.000001'],
            'units.*.is_base'        => ['required_with:units','boolean'],

            // Combo items
            'combo_items'            => ['array'],
            'combo_items.*.id'       => ['required_with:combo_items','integer','exists:products,id'],
            'combo_items.*.quantity' => ['required_with:combo_items','numeric','min:0.000001'],

            // Batch defaults
            'batch_no'               => ['nullable','string','max:100'],
            'manufactured_at'        => ['nullable','date'],
            'expired_at'             => ['nullable','date','after_or_equal:manufactured_at'],
            'warehouse_id'           => ['nullable','integer','exists:warehouses,id'], // for opening stock
        ];
    }

    public function messages(): array {
        return [
            'units.required_with' => 'Units are required for Stock/Non-stock.',
        ];
    }
}
