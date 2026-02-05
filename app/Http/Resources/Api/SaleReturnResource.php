<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->return_number,
            'return_date' => $this->return_date,
            'total_refund' => $this->total_refund,
            'refund_method' => $this->refund_method,
            'reason' => $this->reason,
            'sale' => new SaleResource($this->whenLoaded('sale')),
            'user' => new UserResource($this->whenLoaded('user')),
            'items' => SaleReturnItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
