<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'date_time' => $this->date_time,
            'team_1' => $this->team_1,
            'team_2' => $this->team_2,
            'bet_type' => $this->bet_type,
            'odds' => $this->odds,
            'prediction' => $this->prediction,
            'game_type' => $this->game_type,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
