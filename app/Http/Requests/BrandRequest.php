<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $brand = $this->route('brand');
        $id    = is_object($brand) ? $brand->id : (is_numeric($brand) ? (int)$brand : null);

        return [
            'name'        => [
                'required','string','max:150',
                Rule::unique('brands','name')
                    ->ignore($id)
                    ->where(fn($q)=>$q->where('company_id', auth()->user()->company_id)),
            ],
            'slug'        => ['nullable','string','max:180'],
            'description' => ['nullable','string'],
            'status'      => ['nullable', Rule::in(['active','inactive'])],
        ];
    }
}
