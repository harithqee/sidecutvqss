<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueSession extends Model
{
    use HasFactory;

    protected $fillable = ['session_date', 'opened_at', 'closed_at', 'status'];

    protected $casts = [
        'session_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    public static function today(): self
    {
        return static::firstOrCreate(
            ['session_date' => now()->toDateString()],
            ['status' => 'open', 'opened_at' => now()]
        );
    }
}
