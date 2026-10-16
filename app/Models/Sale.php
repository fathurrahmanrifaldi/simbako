<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'sold_at',
        'payment_method',
        'total',
        'status',
        'is_summary',
        'summary_date',
        'void_reason',
        'voided_by',
        'voided_at',
        'created_by',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'total' => 'integer',
        'is_summary' => 'boolean',
        'summary_date' => 'date',
        'voided_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
