<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'lead_time_days',
        'safety_stock',
        'expiry_warning_days',
        'overstock_days',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
            'safety_stock' => 'decimal:3',
            'expiry_warning_days' => 'integer',
            'overstock_days' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
