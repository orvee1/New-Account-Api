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
        $name = $data['name'];
        $parentId = $parent?->id;

        // Check if exists
        $node = ChartAccount::where('company_id', $companyId)
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->first();

        if (!$node) {
            $node = ChartAccount::create([
                'company_id' => $companyId,
                'parent_id'  => $parentId,
                'type'       => $type,
                'code'       => $data['code'] ?? null,
                'name'       => $name,
                'slug'       => Str::slug($name),
                'path'       => '', // fill after id known
                'depth'      => $parent ? ($parent->depth + 1) : 0,
                'sort_order' => $sort,
                'is_active'  => true,
            ]);

            // set materialized path: /parentPath/id or /id
            $node->path = $parent ? rtrim($parent->path, '/').'/'.$node->id : '/'.$node->id;
            $node->save();
        }

        // If group, do children
        if ($type === 'group' && !empty($data['children'])) {
            foreach ($data['children'] as $k => $child) {
                $this->createNodeRecursive($companyId, $child, $node, $k);
            }
        }

        return $node;
    }
}

