<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'name' => $this->name,
            'image' => $this->image,
            'average_rating' => $this->average_rating,
            'visitors_count' => $this->visitors_count,
            'description' => $this->description,
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'owner_id' => $this->owner_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations
            'country' => new CountryResource($this->whenLoaded('country')),
            'city' => new CityResource($this->whenLoaded('city')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'buildings' => buildingResource::collection($this->whenLoaded('buildings')),
            // 'floors' => FloorResource::collection($this->whenLoaded('floors')),
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->owner->email,
                ];
            }),
        ];
    }
}
