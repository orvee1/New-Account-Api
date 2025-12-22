<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'customer_id'              => ['required', 'integer', 'exists:customers,id'],
            'sales_invoice_id'         => ['nullable', 'integer', 'exists:sales_invoices,id'],
            'return_no'                => ['nullable', 'string', 'max:100'],
            'return_date'              => ['required', 'date'],
            'reason'                   => ['nullable', 'string'],
            'notes'                    => ['nullable', 'string'],

            'items'                    => ['required', 'array', 'min:1'],
            'items.*.sales_invoice_item_id' => ['required', 'integer', 'exists:sales_invoice_items,id'],
            'items.*.product_id'       => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'         => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price'       => ['required', 'numeric', 'gte:0'],
            'items.*.discount_amount'  => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount'       => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $map = [
            'customerId' => 'customer_id',
            'salesInvoiceId' => 'sales_invoice_id',
            'returnNo' => 'return_no',
            'returnDate' => 'return_date',
        ];
        foreach ($map as $camel => $snake) {
            if ($this->has($camel) && !$this->has($snake)) {
                $this->merge([$snake => $this->input($camel)]);
            }
        }

        if ($this->has('items') && is_array($this->input('items'))) {
            $items = array_map(function ($item) {
                if (!is_array($item)) return $item;
                $itemMap = [
                    'salesInvoiceItemId' => 'sales_invoice_item_id',
                    'productId' => 'product_id',
                    'unitPrice' => 'unit_price',
                    'discountAmount' => 'discount_amount',
                    'taxAmount' => 'tax_amount',
                ];
                foreach ($itemMap as $camel => $snake) {
                    if (array_key_exists($camel, $item) && !array_key_exists($snake, $item)) {
                        $item[$snake] = $item[$camel];
                    }
                }
                return $item;
            }, $this->input('items'));
            $this->merge(['items' => $items]);
        }
    }
}
