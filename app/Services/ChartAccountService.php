<?php
// app/Services/ChartAccountService.php
namespace App\Services;

use App\Models\ChartAccount;
use Illuminate\Support\Str;

class ChartAccountService
{
    public function seedDefaultForCompany(int $companyId, ?array $tree = null): void
    {
        $tree ??= config('coa');

        foreach ($tree as $i => $node) {
            $this->createNodeRecursive(
                companyId: $companyId,
                data: $node,
                parent: null,
                sort: $i
            );
        }
    }

    protected function createNodeRecursive(int $companyId, array $data, ?ChartAccount $parent, int $sort = 0): ChartAccount
    {
        $type = $data['type'] ?? (empty($data['children']) ? 'ledger' : 'group');

        $node = ChartAccount::create([
            'company_id' => $companyId,
            'parent_id'  => $parent?->id,
            'type'       => $type,
            'code'       => $data['code'] ?? null,
            'name'       => $data['name'],
            'slug'       => Str::slug($data['name']),
            'path'       => '', // fill after id known
            'depth'      => $parent ? ($parent->depth + 1) : 0,
            'sort_order' => $sort,
            'is_active'  => true,
        ]);

        // set materialized path: /parentPath/id or /id
        $node->path = $parent ? rtrim($parent->path, '/').'/'.$node->id : '/'.$node->id;
        $node->save();

        // If group, do children
        if ($type === 'group' && !empty($data['children'])) {
            foreach ($data['children'] as $k => $child) {
                $this->createNodeRecursive($companyId, $child, $node, $k);
            }
        }

        return $node;
    }
}

