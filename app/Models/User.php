<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name',
        'role',
        'email',
        'password_hash',
        'pin_hash',
        'is_active',
        'failed_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password_hash',
        'pin_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isLocked(): bool
    {
        if ($this->locked_until && $this->locked_until->isFuture()) {
            return true;
        }
        return false;
    }
}
