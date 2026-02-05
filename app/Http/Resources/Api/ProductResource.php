<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'supplier_id' => $this->supplier_id,
            'name' => $this->name,
            'brand' => $this->brand,
            'network_type' => $this->network_type,
            'variant' => $this->variant,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'purchase_price' => $this->purchase_price,
            'selling_price' => $this->selling_price,
            'stock_quantity' => $this->stock_quantity,
            'min_stock_alert' => $this->min_stock_alert,
            'has_imei' => $this->has_imei,
            'image_url' => $this->image_url,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_low_stock' => $this->isLowStock(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'imeis' => ProductImeiResource::collection($this->whenLoaded('imeis')),
            'available_imeis_count' => $this->whenCounted('availableImeis'),
            'created_at' => $this->created_at,
        ];
    }
}
