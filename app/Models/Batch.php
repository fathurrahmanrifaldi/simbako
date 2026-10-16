<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'received_on',
        'expiry_date',
        'initial_qty',
        'unit_cost',
        'source',
        'purchase_item_id',
    ];

    protected $casts = [
        'received_on' => 'date',
        'expiry_date' => 'date',
        'initial_qty' => 'decimal:3',
        'unit_cost' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
