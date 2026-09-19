<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barber extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'role', 'image', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}
