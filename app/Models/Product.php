<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id',
        'category_id',
        'supplier_id',
        'name',
        'brand',
        'network_type',
        'variant',
        'sku',
        'barcode',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'min_stock_alert',
        'has_imei',
        'image_url',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'has_imei' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function imeis(): HasMany
    {
        return $this->hasMany(ProductImei::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function availableImeis(): HasMany
    {
        return $this->hasMany(ProductImei::class)->where('status', 'in_stock');
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_alert;
    }
}
