<?php

namespace App\Http\Resources;

use App\Traits\HandlesFileUploads;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use HandlesFileUploads;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_picture' => $this::getFileUrl($this->profile_picture),
            'subscription' => [
                'plan_id' => $this->plan_id,
                'subscribed_at' => $this->subscribed_at,
                'expires_at' => $this->subscription_expires_at,
                'is_active' => $this->hasActiveSubscription(),
            ]
        ];
        return $data;
    }
}
