<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'customer_id'                 => ['required', 'integer', 'exists:customers,id'],
            'invoice_no'                  => ['nullable', 'string', 'max:100'],
            'invoice_date'                => ['required', 'date'],
            'due_date'                    => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'warehouse_id'                => ['nullable', 'integer', 'exists:warehouses,id'],
            'notes'                       => ['nullable', 'string'],
            'vat_mode'                    => ['required', 'in:exclusive,inclusive'],
            'invoice_discount_amt'        => ['nullable', 'numeric', 'min:0'],
            'invoice_discount_account_id' => ['nullable', 'integer', 'exists:chart_accounts,id'],

            'items'                       => ['required', 'array', 'min:1'],
            'items.*.product_id'          => ['required', 'integer', 'exists:products,id'],
            'items.*.sale_uom_id'         => ['required', 'integer', 'exists:product_uoms,id'],
            'items.*.price_uom_id'        => ['required', 'integer', 'exists:product_uoms,id'],
            'items.*.quantity'            => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price'          => ['required', 'numeric', 'gte:0'],
            'items.*.trade_discount_pct'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.line_discount_pct'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.line_discount_amt'   => ['nullable', 'numeric', 'min:0'],
            'items.*.vat_rate'            => ['nullable', 'numeric', 'min:0'],
            'items.*.ait_rate'            => ['nullable', 'numeric', 'min:0'],
            'items.*.description'         => ['nullable', 'string'],
        ];
    }
}
