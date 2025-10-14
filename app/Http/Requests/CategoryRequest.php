<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $cat = $this->route('category');
        $id  = is_object($cat) ? $cat->id : (is_numeric($cat) ? (int)$cat : null);

        return [
            'name'        => [
                'required','string','max:150',
                Rule::unique('categories','name')
                    ->ignore($id)
                    ->where(fn($q)=>$q->where('company_id', auth()->user()->company_id)),
            ],
            'slug'        => ['nullable','string','max:180'],
            'description' => ['nullable','string'],
            'status'      => ['nullable', Rule::in(['active','inactive'])],
            'parent_id'   => ['nullable','integer','exists:categories,id'],
        ];
    }
}
