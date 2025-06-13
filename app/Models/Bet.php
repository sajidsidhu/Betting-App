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
        'team_1',
        'team_2',
        'bet_type',
        'odds',
        'prediction',
        'game_type',
        'raw_text',
        'parsed_data',
        'status',
        'verified_at'
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'odds' => 'decimal:2',
        'parsed_data' => 'array',
        'verified_at' => 'datetime'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
