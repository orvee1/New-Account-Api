<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ধরা হচ্ছে logged-in user এর company_id দিয়েই কাজ হবে
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id ?? request('company_id');
        $id = $this->route('warehouse');

        return [
            'name' => [
                'required', 'string', 'max:191',
                // unique per company
                "unique:warehouses,name,{$id},id,company_id,{$companyId}",
            ],
            'is_default' => ['sometimes','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The warehouse name is required.',
            'name.unique'   => 'This warehouse name already exists for your company.',
        ];
    }
}
