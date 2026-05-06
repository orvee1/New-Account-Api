<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseBillRequest extends FormRequest
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
            'vendor_id'                => ['required','integer','exists:vendors,id'],
            'bill_no'                  => ['required','string','max:100'],
            'bill_date'                => ['required','date'],
            'due_date'                 => ['nullable','date'],
            'supplier_ref_no'          => ['nullable','string','max:100'],
            'vat_mode'                 => ['required','string','in:exclusive,inclusive'],
            'bill_discount_amt'        => ['nullable','numeric','min:0'],
            'bill_discount_account_id' => ['nullable','integer','exists:chart_accounts,id'],
            'notes'                    => ['nullable','string'],

            'items'                    => ['required','array','min:1'],
            'items.*.product_id'       => ['required','integer','exists:products,id'],
            'items.*.purchase_uom_id'  => ['required','integer','exists:product_uoms,id'],
            'items.*.price_uom_id'     => ['required','integer','exists:product_uoms,id'],
            'items.*.quantity'         => ['required','numeric','gt:0'],
            'items.*.unit_price'       => ['required','numeric','gte:0'],
            'items.*.trade_discount_pct' => ['nullable','numeric','min:0','max:100'],
            'items.*.line_discount_pct'  => ['nullable','numeric','min:0','max:100'],
            'items.*.line_discount_amt'  => ['nullable','numeric','min:0'],
            'items.*.vat_rate'           => ['nullable','numeric','min:0'],
            'items.*.ait_rate'           => ['nullable','numeric','min:0'],
        ];
    }
}
