<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstimateRequest extends FormRequest
{
    public function authorize(): bool
    {return auth()->check();}

    public function rules(): array
    {
        return [
            'customer_id'                        => ['required', 'integer', 'exists:customers,id'],
            'estimate_no'                        => ['nullable', 'string', 'max:50'],
            'estimate_date'                      => ['required', 'date'],
            'expiry_date'                        => ['nullable', 'date', 'after_or_equal:estimate_date'],
            'is_draft'                           => ['boolean'],
            'notes'                              => ['nullable', 'string'],

            'line_items'                         => ['required', 'array', 'min:1'],
            'line_items.*.product_id'            => ['required', 'integer', 'exists:products,id'],
            'line_items.*.quantity_input'        => ['required', 'numeric', 'min:0.000001'],
            'line_items.*.quantity_unit_id'      => ['nullable', 'integer', 'exists:product_units,id'],
            'line_items.*.billing_unit_id'       => ['nullable', 'integer', 'exists:product_units,id'],
            'line_items.*.rate_for_billing_unit' => ['required', 'numeric', 'min:0'],
            'line_items.*.discount_percent'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.discount_amount'       => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($v)
    {
        $v->after(function ($validator) {
            foreach ((array) $this->input('line_items', []) as $i => $li) {
                $p = (float) ($li['discount_percent'] ?? 0); $a = (float) ($li['discount_amount'] ?? 0);
                if ($p > 0 && $a > 0) {
                    $validator->errors()->add("line_items.$i.discount_amount", 'Use either discount_percent or discount_amount, not both.');
                }

            }
        });
    }
}
