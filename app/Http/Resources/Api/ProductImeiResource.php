<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImeiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'imei_1' => $this->imei_1,
            'imei_2' => $this->imei_2,
            'status' => $this->status,
            'purchase_date' => $this->purchase_date,
            'sold_date' => $this->sold_date,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
