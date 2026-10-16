<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'category_id',
        'base_unit',
        'sell_price',
        'last_buy_price',
        'min_stock',
        'lead_time_days',
        'safety_stock',
        'has_expiry',
        'is_favorite',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sell_price' => 'integer',
            'last_buy_price' => 'integer',
            'min_stock' => 'decimal:3',
            'lead_time_days' => 'integer',
            'safety_stock' => 'decimal:3',
            'has_expiry' => 'boolean',
            'is_favorite' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getEffectiveLeadTimeAttribute(): int
    {
        return $this->lead_time_days ?? $this->category->lead_time_days ?? 1;
    }

    public function getEffectiveSafetyStockAttribute(): float
    {
        return (float) ($this->safety_stock ?? $this->category->safety_stock ?? 0);
    }
}
