<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\ReviewResource;

class PlaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user('sanctum');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description, // Verify if description exists in model/migration
            'image' => $this->image,
            'accessibility_data' => $this->accessibility_data,
            'category_id' => $this->category_id,
            'organization_id' => $this->organization_id,
            'owner_id' => $this->owner_id,
            
            // Computed fields
            'average_rating' => $this->average_rating,
            'visitors_count' => $this->visitors_count,
            'rating_distribution' => $this->rating_distribution,
            'top_reviews' => ReviewResource::collection($this->top_reviews),
            'is_favorited' => $user ? $this->favoritedBy()->where('user_id', $user->id)->exists() : false,

            // Relations
            'category' => new CategoryResource($this->whenLoaded('category')),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            // 'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
