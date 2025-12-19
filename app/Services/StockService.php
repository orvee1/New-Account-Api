<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addOpeningStock(Product $product, int $warehouseId, float $qty, float $unitCost, ?string $date = null): ProductStock
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Opening quantity must be greater than 0.');
        }

        if ($unitCost <= 0) {
            throw new \InvalidArgumentException('Opening unit cost must be greater than 0.');
        }

        $companyId = (int) $product->company_id;
        $occurredAt = $date ? Carbon::parse($date) : Carbon::today();

        return DB::transaction(function () use ($companyId, $product, $warehouseId, $qty, $unitCost, $occurredAt) {

            $stock = ProductStock::query()
                ->where('company_id', $companyId)
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = ProductStock::create([
                    'company_id' => $companyId,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'quantity_on_hand' => 0,
                    'avg_cost' => null,
                ]);
            }

            // weighted avg cost update (optional but useful)
            $oldQty = (float) $stock->quantity_on_hand;
            $oldAvg = (float) ($stock->avg_cost ?? 0);

            $newQty = $oldQty + $qty;
            $newAvg = $oldQty <= 0
                ? $unitCost
                : (($oldQty * $oldAvg) + ($qty * $unitCost)) / max($newQty, 0.0001);

            $stock->quantity_on_hand = $newQty;
            $stock->avg_cost = round($newAvg, 4);
            $stock->save();

            StockMovement::create([
                'company_id' => $companyId,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'movement_type' => 'opening',
                'qty_in' => $qty,
                'qty_out' => 0,
                'unit_cost' => $unitCost,
                'total_cost' => round($qty * $unitCost, 4),
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'occurred_at' => $occurredAt,
                'created_by' => $product->created_by ?? auth()->id(),
                'notes' => 'Opening stock',
            ]);

            return $stock;
        });
    }
}
