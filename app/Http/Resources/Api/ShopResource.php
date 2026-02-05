<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'phone' => $this->phone,
            'email' => $this->email,
            'gst_number' => $this->gst_number,
            'invoice_prefix' => $this->invoice_prefix,
            'tax_rate' => $this->tax_rate,
            'currency' => $this->currency,
            'logo_url' => $this->logo_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
