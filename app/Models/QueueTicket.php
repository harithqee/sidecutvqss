<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QueueTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_session_id', 'barber_id', 'service_id',
        'customer_name', 'customer_phone', 'queue_number',
        'status', 'joined_at', 'served_at', 'finished_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'served_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(QueueSession::class, 'queue_session_id');
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(CustomerFeedback::class);
    }

    // Matches the frontend's "30 min" waiting time display
    public function getWaitingTimeAttribute(): ?string
{
    if (!$this->joined_at || !$this->served_at) {
        return null;
    }
    return round($this->joined_at->diffInMinutes($this->served_at)) . ' min';
}

    public function getFormattedQueueNumberAttribute(): string
    {
        return '#' . $this->queue_number;
    }

    protected $appends = ['waiting_time', 'formatted_queue_number'];
}
