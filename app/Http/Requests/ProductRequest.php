<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $id = is_object($product) ? $product->id : (is_numeric($product) ? (int)$product : null);

        return [
            // Core
            'product_type'   => ['required', Rule::in(['Stock','Non-stock','Service','Combo'])],
            'name'           => ['required','string','max:255'],

            // New identifiers
            'sku'            => [
                'nullable','string','max:100',
                Rule::unique('products','sku')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('company_id', auth()->user()->company_id)),
            ],
            'barcode'        => [
                'nullable','string','max:255',
                Rule::unique('products','barcode')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('company_id', auth()->user()->company_id)),
            ],

            'description'    => ['nullable','string'],

            // FKs
            'category_id'    => ['nullable','integer','exists:categories,id'],
            'brand_id'       => ['nullable','integer','exists:brands,id'],
            'warehouse_id'   => ['nullable','integer','exists:warehouses,id'],

            // Pricing
            'costing_price'  => ['nullable','numeric','min:0'],
            'sales_price'    => ['nullable','numeric','min:0'],
            'tax_percent'    => ['nullable','numeric','min:0','max:100'],

            // Warranty
            'has_warranty'   => ['boolean'],
            'warranty_days'  => ['nullable','integer','min:0'],

            // Units (optional for all; if provided must be valid and single base unit)
            'units'                  => ['array'],
            'units.*.name'           => ['required_with:units','string','max:50'],
            'units.*.factor'         => ['required_with:units','numeric','min:0.000001'],
            'units.*.is_base'        => ['required_with:units','boolean'],

            // Opening stock (allowed only for Stock)
            'opening_quantity'       => ['nullable','numeric','min:0'],
            'manufactured_at'        => ['nullable','date'],
            'expired_at'             => ['nullable','date','after_or_equal:manufactured_at'],

            // Combo items (requiredfor Combo)
            'combo_items'                => ['array'],
            'combo_items.*.product_id'   => ['required_with:combo_items','integer','exists:products,id'],
            'combo_items.*.quantity'     => ['required_with:combo_items','numeric','min:0.000001'],

            // Misc
            'barcode'        => ['nullable','string','max:255'],
            'unit'           => ['nullable','string','max:50'],
            'status'         => ['nullable', Rule::in(['active','inactive'])],
            'meta'           => ['nullable','array'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $t = $this->input('product_type');

            $hasOpening = $this->filled('opening_quantity')
                        || $this->filled('warehouse_id')
                        || $this->filled('batch_no')
                        || $this->filled('manufactured_at')
                        || $this->filled('expired_at');

            // Type-based constraints
            if ($t === 'Stock') {
                if ($this->filled('opening_quantity') > 0 && !$this->filled('warehouse_id')) {
                    $v->errors()->add('warehouse_id', 'warehouse_id is required when opening_quantity is provided for Stock products.');
                }
            } elseif (in_array($t, ['Non-stock','Service'], true)) {
                if ($hasOpening) {
                    $v->errors()->add('opening_quantity', 'Opening/batch fields are not allowed for Non-stock/Service products.');
                }
            } elseif ($t === 'Combo') {
                if ($hasOpening) {
                    $v->errors()->add('opening_quantity', 'Opening/batch fields are not allowed for Combo products.');
                }
                $items = $this->input('combo_items', []);
                if (!is_array($items) || count($items) === 0) {
                    $v->errors()->add('combo_items', 'combo_items is required and must contain at least one item for Combo products.');
                }
            }

            // Units: if provided, enforce exactly one base unit
            if ($this->filled('units') && is_array($this->input('units'))) {
                $bases = collect($this->input('units'))->where('is_base', true)->count();
                if ($bases === 0) {
                    $v->errors()->add('units', 'At least one unit must have is_base=true.');
                } elseif ($bases > 1) {
                    $v->errors()->add('units', 'Only one unit may have is_base=true.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'sku.unique'      => 'This SKU is already used within your company.',
            'barcode.unique'  => 'This barcode is already used within your company.',
            'units.*.name.required_with'    => 'Each provided unit must have a name.',
            'units.*.factor.required_with'  => 'Each provided unit must have a factor.',
            'units.*.is_base.required_with' => 'Each provided unit must specify is_base.',
        ];
    }
}
