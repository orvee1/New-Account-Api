<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'customer_id'            => ['required', 'integer', 'exists:customers,id'],
            'order_no'               => ['nullable', 'string', 'max:100'],
            'order_date'             => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes'                  => ['nullable', 'string'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price'     => ['required', 'numeric', 'gte:0'],
            'items.*.discount_amount'=> ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount'     => ['nullable', 'numeric', 'min:0'],
            'items.*.description'    => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $map = [
            'customerId' => 'customer_id',
            'orderNo' => 'order_no',
            'orderDate' => 'order_date',
            'expectedDeliveryDate' => 'expected_delivery_date',
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
