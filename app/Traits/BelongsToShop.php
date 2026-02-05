<?php

namespace App\Traits;

use App\Models\Shop;
use App\Scopes\ShopScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToShop
{
    protected static function bootBelongsToShop(): void
    {
        static::addGlobalScope(new ShopScope());

        static::creating(function ($model) {
            if (empty($model->shop_id) && app()->has('current_shop_id')) {
                $model->shop_id = app('current_shop_id');
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
