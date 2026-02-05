<?php

namespace App\Services;

use App\Models\Sale;

class InvoiceNumberService
{
    public function generate(int $shopId, string $prefix): string
    {
        $lastSale = Sale::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastSale ? ((int) substr($lastSale->invoice_number, -6)) + 1 : 1;

        return $prefix . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
