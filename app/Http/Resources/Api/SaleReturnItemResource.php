<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_item_id' => $this->sale_item_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'refund_amount' => $this->refund_amount,
            'restock' => $this->restock,
        ];
    }
}
