<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'email',
        'gst_number',
        'invoice_prefix',
        'tax_rate',
        'currency',
        'logo_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
