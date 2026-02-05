<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function adjustStock(
        Product $product,
        int $quantity,
        string $type,
        int $userId,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $type, $userId, $referenceType, $referenceId, $notes) {
            $beforeQuantity = $product->stock_quantity;
            $afterQuantity = $beforeQuantity + $quantity;

            $product->update(['stock_quantity' => $afterQuantity]);

            return StockMovement::create([
                'shop_id' => $product->shop_id,
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => $type,
                'quantity' => $quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
            ]);
        });
    }
}
