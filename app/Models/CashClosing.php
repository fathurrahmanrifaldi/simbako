<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashClosing extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_date',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'diff',
        'note',
        'closed_by',
        'closed_at',
        'reopened_by',
        'reopened_at',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opening_cash' => 'integer',
        'expected_cash' => 'integer',
        'actual_cash' => 'integer',
        'diff' => 'integer',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}
