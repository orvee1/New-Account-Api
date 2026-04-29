<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MergeAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'source_account_id' => 'required|exists:chart_accounts,id',
            'target_account_id' => 'required|exists:chart_accounts,id|different:source_account_id',
        ];
    }
}
