<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id',
        'sale_return_id',
        'sale_item_id',
        'product_id',
        'product_imei_id',
        'quantity',
        'refund_amount',
        'restock',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'restock' => 'boolean',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
