<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'sale_type'   => ['required', Rule::in(['cash','credit'])],
            'customer_id' => [
                'nullable','integer','exists:customers,id',
                // credit হলে অবশ্যই লাগবে
            ],
            'invoice_no'   => ['nullable','string','max:50'],
            'invoice_date' => ['required','date'],
            'due_date'     => ['nullable','date','after_or_equal:invoice_date'],
            'notes'        => ['nullable','string'],
            'terms'        => ['nullable','string'],
            'shipping_amount' => ['nullable','numeric','min:0'],

            // Line items
            'line_items' => ['required','array','min:1'],
            'line_items.*.product_id' => ['required','integer','exists:products,id'],
            'line_items.*.quantity_input' => ['required','numeric','min:0.000001'],
            'line_items.*.quantity_unit_id' => ['nullable','integer','exists:product_units,id'],
            'line_items.*.billing_unit_id'  => ['nullable','integer','exists:product_units,id'],
            'line_items.*.rate_for_billing_unit' => ['required','numeric','min:0'],
            'line_items.*.discount_percent' => ['nullable','numeric','min:0','max:100'],
            'line_items.*.discount_amount'  => ['nullable','numeric','min:0'],
            'line_items.*.vat_percent'      => ['nullable','numeric','min:0','max:100'],
        ];
    }

    public function withValidator($v)
    {
        $v->after(function($validator){
            // credit হলে customer_id লাগবে; cash হলে লাগবে না
            if ($this->input('sale_type') === 'credit' && !$this->filled('customer_id')) {
                $validator->errors()->add('customer_id', 'customer_id is required for credit sale.');
            }

            // discount% এবং amount — একসাথে >0 হওয়া যাবে না
            foreach ((array)$this->input('line_items', []) as $idx => $li) {
                $p = (float)($li['discount_percent'] ?? 0);
                $a = (float)($li['discount_amount']  ?? 0);
                if ($p > 0 && $a > 0) {
                    $validator->errors()->add("line_items.$idx.discount_amount", 'Use either discount_percent or discount_amount, not both.');
                }
            }
        });
    }
}
