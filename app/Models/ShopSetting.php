<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id',
        'key',
        'value',
    ];
}
