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
        if ($qty < 0) {
            throw new \InvalidArgumentException('Opening quantity cannot be negative.');
        }

        if ($unitCost < 0) {
            throw new \InvalidArgumentException('Opening unit cost cannot be negative.');
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

            $oldQty = (float) $stock->quantity_on_hand;
            $oldAvg = (float) ($stock->avg_cost ?? 0);

            $newQty = $oldQty + $qty;
            $newAvg = $oldAvg;

            if ($qty > 0) {
                $newAvg = $oldQty <= 0
                    ? $unitCost
                    : (($oldQty * $oldAvg) + ($qty * $unitCost)) / max($newQty, 0.0001);
            } elseif ($stock->avg_cost === null) {
                $newAvg = $unitCost;
            }

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

    public function addPurchaseStock(Product $product, int $warehouseId, float $qty, float $unitCost, object $reference, ?string $date = null, ?int $userId = null): ProductStock
    {
        return $this->moveStock($product, $warehouseId, $qty, 0, $unitCost, 'purchase_in', $reference, $date, $userId, 'Purchase stock in');
    }

    public function removePurchaseStock(Product $product, int $warehouseId, float $qty, float $unitCost, object $reference, ?string $date = null, ?int $userId = null): ProductStock
    {
        return $this->moveStock($product, $warehouseId, 0, $qty, $unitCost, 'purchase_reversal', $reference, $date, $userId, 'Purchase stock reversal');
    }

    public function addSalesReturnStock(Product $product, int $warehouseId, float $qty, float $unitCost, object $reference, ?string $date = null, ?int $userId = null): ProductStock
    {
        return $this->moveStock($product, $warehouseId, $qty, 0, $unitCost, 'sales_return_in', $reference, $date, $userId, 'Sales return stock in');
    }

    public function removeSaleStock(Product $product, int $warehouseId, float $qty, float $unitCost, object $reference, ?string $date = null, ?int $userId = null): ProductStock
    {
        return $this->moveStock($product, $warehouseId, 0, $qty, $unitCost, 'sale_out', $reference, $date, $userId, 'Sales stock out');
    }

    public function restoreSaleStock(Product $product, int $warehouseId, float $qty, float $unitCost, object $reference, ?string $date = null, ?int $userId = null): ProductStock
    {
        return $this->moveStock($product, $warehouseId, $qty, 0, $unitCost, 'sale_reversal', $reference, $date, $userId, 'Sales stock reversal');
    }

    public function removePurchaseReturnStock(Product $product, int $warehouseId, float $qty, float $unitCost, object $reference, ?string $date = null, ?int $userId = null): ProductStock
    {
        return $this->moveStock($product, $warehouseId, 0, $qty, $unitCost, 'purchase_return_out', $reference, $date, $userId, 'Purchase return stock out');
    }

    public function restorePurchaseReturnStock(Product $product, int $warehouseId, float $qty, float $unitCost, object $reference, ?string $date = null, ?int $userId = null): ProductStock
    {
        return $this->moveStock($product, $warehouseId, $qty, 0, $unitCost, 'purchase_return_reversal', $reference, $date, $userId, 'Purchase return reversal');
    }

    public function removeSalesReturnStock(Product $product, int $warehouseId, float $qty, float $unitCost, object $reference, ?string $date = null, ?int $userId = null): ProductStock
    {
        return $this->moveStock($product, $warehouseId, 0, $qty, $unitCost, 'sales_return_reversal', $reference, $date, $userId, 'Sales return reversal');
    }

    private function moveStock(Product $product, int $warehouseId, float $qtyIn, float $qtyOut, float $unitCost, string $movementType, object $reference, ?string $date, ?int $userId, string $notes): ProductStock
    {
        if ($qtyIn < 0 || $qtyOut < 0) {
            throw new \InvalidArgumentException('Stock movement quantity cannot be negative.');
        }

        if ($unitCost < 0) {
            throw new \InvalidArgumentException('Stock movement unit cost cannot be negative.');
        }

        $companyId = (int) $product->company_id;
        $occurredAt = $date ? Carbon::parse($date) : Carbon::today();

        return DB::transaction(function () use ($companyId, $product, $warehouseId, $qtyIn, $qtyOut, $unitCost, $movementType, $reference, $occurredAt, $userId, $notes) {
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
                    'avg_cost' => $unitCost,
                ]);
            }

            $oldQty = (float) $stock->quantity_on_hand;
            $oldAvg = (float) ($stock->avg_cost ?? 0);
            $newQty = $oldQty + $qtyIn - $qtyOut;
            $newAvg = $oldAvg;

            if ($qtyIn > 0 && $newQty > 0) {
                $newAvg = (($oldQty * $oldAvg) + ($qtyIn * $unitCost)) / max($oldQty + $qtyIn, 0.0001);
            } elseif ($newQty == 0) {
                $newAvg = 0;
            }

            $stock->quantity_on_hand = $newQty;
            $stock->avg_cost = round($newAvg, 4);
            $stock->save();

            StockMovement::create([
                'company_id' => $companyId,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'movement_type' => $movementType,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'unit_cost' => $unitCost,
                'total_cost' => round(($qtyIn > 0 ? $qtyIn : $qtyOut) * $unitCost, 4),
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
                'occurred_at' => $occurredAt,
                'created_by' => $userId ?? $product->created_by ?? auth()->id(),
                'notes' => $notes,
            ]);

            return $stock;
        });
    }
}
