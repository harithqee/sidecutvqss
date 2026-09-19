<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'trigger_event', 'message_body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }
}
