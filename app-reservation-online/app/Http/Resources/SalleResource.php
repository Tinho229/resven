<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalleResource extends JsonResource
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
            'nom' => $this->nom,
            'description' => $this->description,
            'capacite' => (int) $this->capacite,
            'status' => $this->status,
            'localisation' => $this->localisation,
            'prix' => (float) $this->prix,
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn() => $this->images->map(fn($img) => (new ImageResource($img))->toArray($request))->values()->toArray()
            ),
            'reservations_count' => $this->whenCounted('reservations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
