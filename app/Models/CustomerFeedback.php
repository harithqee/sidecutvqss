<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedback extends Model
{
    use HasFactory;

    protected $fillable = ['queue_ticket_id', 'rating', 'comment'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class, 'queue_ticket_id');
    }
}
