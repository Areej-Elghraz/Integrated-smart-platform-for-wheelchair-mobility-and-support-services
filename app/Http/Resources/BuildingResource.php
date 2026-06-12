<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'organization_id' => $this->organization_id,
            'image'           => $this->image,
            'latitude'        => $this->latitude,
            'longitude'       => $this->longitude,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'organization'    => $this->whenLoaded('organization'),
            'floors'          => $this->whenLoaded('floors'),
            'places'          => $this->whenLoaded('places'),
        ];
    }
}
