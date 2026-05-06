<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseFormConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'is_vat_registered' => 'true',
            'purchase_show_price_uom' => 'true',
            'purchase_show_trade_discount' => 'true',
            'purchase_show_line_discount' => 'true',
            'purchase_show_vat' => 'true',
            'purchase_show_ait' => 'true',
        ];

        foreach ($settings as $key => $value) {
            DB::table('company_settings')->updateOrInsert(
                ['company_id' => 1, 'key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
