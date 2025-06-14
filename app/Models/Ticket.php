<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'image_path',
        'source_language',
        'translation_json',
        'total_odd',
        'total_price',
        'potential_win',
        'status',
        'is_favorite',
        'bookmaker',
        'ticket_date',
        'error_message',
        'is_readable',
        'errors'
    ];

    protected $casts = [
        'translation_json' => 'array',
        'total_odd' => 'decimal:2',
        'total_price' => 'decimal:2',
        'potential_win' => 'decimal:2',
        'is_favorite' => 'boolean',
        'is_readable' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    public function updateStatusBasedOnBets(): void
    {
        $betsStatus = $this->bets()->pluck('status')->toArray();
        
        if (in_array('lost', $betsStatus)) {
            $this->status = 'lost';
        } elseif (count(array_filter($betsStatus, fn($status) => $status === 'won')) === count($betsStatus)) {
            $this->status = 'all_won';
        } elseif (in_array('won', $betsStatus) && in_array('pending', $betsStatus)) {
            $this->status = 'partial_won';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }
}
