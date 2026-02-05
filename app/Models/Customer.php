<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToShop, HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'email',
        'address',
        'total_purchases',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_purchases' => 'decimal:2',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
