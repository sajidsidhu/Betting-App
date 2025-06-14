<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Traits\HandlesFileUploads;
use App\Http\Resources\BetResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'image_path' => HandlesFileUploads::getFileUrl($this->image_path),
            'source_language' => $this->source_language,
            'total_odd' => $this->total_odd,
            'total_price' => $this->total_price,
            'potential_win' => $this->potential_win,
            'bookmaker' => $this->is_readable ? $this->bookmaker : "Error in Processing",
            'ticket_date' => $this->ticket_date,
            'status' => $this->is_readable ? $this->status : 'lost',
            'is_favorite' => $this->is_favorite,
            'error_message' => $this->error_message,
            'is_readable' => $this->is_readable,
            'created_at' => $this->created_at,
        ];
    }
}
