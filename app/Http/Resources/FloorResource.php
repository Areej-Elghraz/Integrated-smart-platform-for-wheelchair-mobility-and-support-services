<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FloorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'number'      => $this->number,
            'building_id' => $this->building_id,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,

            // Relations
            'building'    => new BuildingResource($this->whenLoaded('building')),
            'map'         => $this->whenLoaded('map'),
            'places'      => PlaceResource::collection($this->whenLoaded('places')),
            // 'map'         => new MapResource($this->whenLoaded('map')), // Assuming MapResource exists or will exist
        ];
    }
}
