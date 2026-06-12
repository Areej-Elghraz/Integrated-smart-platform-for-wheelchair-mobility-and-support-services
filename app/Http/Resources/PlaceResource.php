<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\FloorResource;

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
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'image'            => $this->image,
            'accessibility_data' => $this->accessibility_data,
            'category_id'      => $this->category_id,
            'owner_id'         => $this->owner_id,
            'floor_id'         => $this->floor_id,
            'map_id'           => $this->map_id,
            'points'           => $this->points,
            'x'                => $this->x,
            'y'                => $this->y,
            'z'                => $this->z,
            'rotation'         => $this->rotation,

            // Computed fields
            'average_rating'       => $this->average_rating,
            'rating'               => $this->rating,
            'visitors_count'       => $this->visitors_count,
            'visitorsCount'        => $this->visitorsCount,
            'rating_distribution'  => (object) $this->rating_distribution,
            'top_reviews'          => ReviewResource::collection($this->top_reviews),
            'is_favorited'         => $user ? $this->favoritedBy()->where('user_id', $user->id)->exists() : false,

            // Relations
            'categories'   => CategoryResource::collection($this->whenLoaded('categories')),
            'floor'        => new FloorResource($this->whenLoaded('floor')),
            'map'          => $this->whenLoaded('map'),
            'owner'        => $this->whenLoaded('owner', function () {
                return [
                    'id'    => $this->owner->id,
                    'name'  => $this->owner->name,
                    'email' => $this->owner->email,
                    'image' => $this->owner->image,
                ];
            }),
            // 'parent'       => new PlaceResource($this->whenLoaded('parent')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
