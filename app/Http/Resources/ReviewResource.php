<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'user_id' => $this->user_id,
            'place_id' => $this->place_id,
            // 'reviewable_type' => $this->reviewable_type,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at->toISOString(),
            'owner' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
                'image' => $this->user->image ?? null,
            ],
            'place' => new PlaceResource($this->whenLoaded('place')),
        ];
    }
}
