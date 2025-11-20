<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FixedAssetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        $base = [
            'name'                => ['required','string','max:255'],
            'category'            => ['nullable','string','max:255'],
            'purchase_date'       => ['nullable','date'],
            'amount'              => ['required','numeric','min:0'],
            'vendor_name'         => ['nullable','string','max:255'],
            'purchase_mode'       => ['nullable','string','in:Cash Purchase,Bank Purchase,On Credit'],
            'payment_mode'        => ['nullable','string','max:255'],
            'useful_life'         => ['nullable','integer','min:0'],
            'salvage_value'       => ['nullable','numeric','min:0'],
            'depreciation_method' => ['required','string','in:Straight Line,Reducing Balance'],
            'frequency'           => ['nullable','string','in:Monthly,Yearly'],
            'depreciation_rate'   => ['nullable','numeric','min:0','max:1000'],
            'asset_location'      => ['nullable','string','max:255'],
            'tag_serial_number'   => ['nullable','string','max:255'],
        ];

        // payment_mode required unless On Credit
        $base['payment_mode'][] = 'required_unless:purchase_mode,On Credit';

        // per-company unique tag_serial_number (fixed_assets টেবিলে)
        $companyId = Auth::user()->company_id ?? 0;

        if ($isCreate) {
            $base['tag_serial_number'][] = 'unique:fixed_assets,tag_serial_number,NULL,id,company_id,' . $companyId;
        } else {
            $routeParam = $this->route('asset');
            $assetId = is_object($routeParam) ? ($routeParam->id ?? null) : $routeParam;
            $base['tag_serial_number'][] = 'unique:fixed_assets,tag_serial_number,' . $assetId . ',id,company_id,' . $companyId;
        }

        return $base;
    }

    public function messages(): array
    {
        return [
            'payment_mode.required_unless' => 'Payment source is required unless purchase mode is "On Credit".',
        ];
    }
}
