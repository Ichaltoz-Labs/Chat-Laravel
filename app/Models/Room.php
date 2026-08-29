<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected $fillable = ['code', 'expired_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
        ];
    }

    /**
     * Room sudah expired?
     */
    public function isExpired(): bool
    {
        return $this->expired_at === null || $this->expired_at->isPast();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
