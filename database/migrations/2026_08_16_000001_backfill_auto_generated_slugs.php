<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['brands' => 'brand', 'categories' => 'category', 'chart_accounts' => 'account'] as $table => $fallback) {
            if (! DB::getSchemaBuilder()->hasTable($table) || ! DB::getSchemaBuilder()->hasColumn($table, 'slug')) {
                continue;
            }

            DB::table($table)
                ->where(function ($query) {
                    $query->whereNull('slug')->orWhere('slug', '');
                })
                ->orderBy('id')
                ->get(['id', 'company_id', 'name'])
                ->each(function ($row) use ($table, $fallback) {
                    $base = Str::slug($row->name ?: $fallback) ?: $fallback;
                    $slug = $base;
                    $counter = 2;

                    while (DB::table($table)
                        ->where('company_id', $row->company_id)
                        ->where('slug', $slug)
                        ->where('id', '!=', $row->id)
                        ->exists()) {
                        $slug = "{$base}-{$counter}";
                        $counter++;
                    }

                    DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
                });
        }
    }

    public function down(): void
    {
        // Data backfill only.
    }
};
