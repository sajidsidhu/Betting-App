<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'price',
        'expiry_duration',
        'features'
    ];

    protected $casts = [
        'features' => 'array',
        'status' => 'boolean',
        'price' => 'decimal:2'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscriptionLogs(): HasMany
    {
        return $this->hasMany(SubscriptionLog::class);
    }
}
