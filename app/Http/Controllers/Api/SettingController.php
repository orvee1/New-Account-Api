<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function getSalesFormConfig(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'User not associated with a company'], 403);
        }
        
        $companyId = $user->company_id;
        
        $settings = CompanySetting::where('company_id', $companyId)
            ->where('key', 'like', 'sales_show_%')
            ->pluck('value', 'key');

        // Default values if not set
        $defaults = [
            'sales_show_price_uom'      => true,
            'sales_show_trade_discount' => true,
            'sales_show_line_discount'  => true,
            'sales_show_vat'            => true,
            'sales_show_ait'            => true,
            'sales_show_cogs'           => false,
            'sales_show_gross_profit'   => false,
        ];

        $config = [];
        foreach ($defaults as $key => $defaultValue) {
            if ($settings->has($key)) {
                $config[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
            } else {
                $config[$key] = $defaultValue;
            }
        }

        return response()->json($config);
    }

    public function updateSalesFormConfig(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'User not associated with a company'], 403);
        }
        
        $companyId = $user->company_id;
        $data = $request->all();

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'sales_show_')) {
                CompanySetting::updateOrCreate(
                    ['company_id' => $companyId, 'key' => $key],
                    ['value' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false']
                );
            }
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function getPurchaseFormConfig(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'User not associated with a company'], 403);
        }
        
        $companyId = $user->company_id;
        
        $keys = [
            'purchase_show_price_uom',
            'purchase_show_trade_discount',
            'purchase_show_line_discount',
            'purchase_show_vat',
            'purchase_show_ait',
            'is_vat_registered'
        ];

        $settings = CompanySetting::where('company_id', $companyId)
            ->whereIn('key', $keys)
            ->pluck('value', 'key');

        // Default values if not set
        $defaults = [
            'purchase_show_price_uom'      => true,
            'purchase_show_trade_discount' => true,
            'purchase_show_line_discount'  => true,
            'purchase_show_vat'            => true,
            'purchase_show_ait'            => true,
            'is_vat_registered'            => true,
        ];

        $config = [];
        foreach ($defaults as $key => $defaultValue) {
            if ($settings->has($key)) {
                $config[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
            } else {
                $config[$key] = $defaultValue;
            }
        }

        return response()->json($config);
    }

    public function updatePurchaseFormConfig(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'User not associated with a company'], 403);
        }
        
        $companyId = $user->company_id;
        $data = $request->all();

        $allowedKeys = [
            'purchase_show_price_uom',
            'purchase_show_trade_discount',
            'purchase_show_line_discount',
            'purchase_show_vat',
            'purchase_show_ait',
            'is_vat_registered'
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                CompanySetting::updateOrCreate(
                    ['company_id' => $companyId, 'key' => $key],
                    ['value' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false']
                );
            }
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }
}
