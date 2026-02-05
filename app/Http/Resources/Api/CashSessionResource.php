<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'opening_balance' => $this->opening_balance,
            'closing_balance' => $this->closing_balance,
            'expected_balance' => $this->expected_balance,
            'cash_in_total' => $this->cash_in_total,
            'cash_out_total' => $this->cash_out_total,
            'difference' => $this->difference,
            'status' => $this->status,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'notes' => $this->notes,
            'movements' => CashMovementResource::collection($this->whenLoaded('movements')),
            'created_at' => $this->created_at,
        ];
    }
}
