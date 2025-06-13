<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'date_time',
        'raw_text',
        'parsed_data',
        'status',
        'verified_at'
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'parsed_data' => 'array',
        'verified_at' => 'datetime'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
