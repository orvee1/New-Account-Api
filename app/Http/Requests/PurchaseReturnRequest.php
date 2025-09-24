<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id'   => ['required','integer','exists:vendors,id'],
            'return_no'   => ['required','string','max:100'],
            'return_date' => ['required','date'],
            'warehouse_id'=> ['nullable','integer','exists:warehouses,id'],
            'notes'       => ['nullable','string'],

            'tax_amount'  => ['nullable','numeric','min:0'],

            'items'                       => ['required','array','min:1'],
            'items.*.product_id'         => ['required','integer','exists:products,id'],
            'items.*.qty_unit_id'        => ['required','integer','exists:product_units,id'],
            'items.*.qty'                => ['required','numeric','gt:0'],
            'items.*.rate_unit_id'       => ['required','integer','exists:product_units,id'],
            'items.*.rate_per_unit'      => ['required','numeric','gte:0'],

            'items.*.discount_percent'   => ['nullable','numeric','min:0'],
            'items.*.discount_amount'    => ['nullable','numeric','min:0'],

            'items.*.warehouse_id'       => ['nullable','integer','exists:warehouses,id'],
            'items.*.batch_no'           => ['nullable','string','max:100'],
            'items.*.manufactured_at'    => ['nullable','date'],
            'items.*.expired_at'         => ['nullable','date','after_or_equal:items.*.manufactured_at'],
        ];
    }
}
