<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_imei_id' => $this->product_imei_id,
            'product_name' => $this->product_name,
            'product_brand' => $this->product_brand,
            'product_variant' => $this->product_variant,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'purchase_price' => $this->purchase_price,
            'discount' => $this->discount,
            'total_price' => $this->total_price,
        ];
    }
}
