<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
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
            'date_heure_debut' => $this->date_heure_debut ? $this->date_heure_debut->format('Y-m-d H:i:s') : null,
            'date_heure_fin' => $this->date_heure_fin ? $this->date_heure_fin->format('Y-m-d H:i:s') : null,
            'nombre_personnes' => (int) $this->nombre_personnes,
            'status' => $this->status,
            'terminee_at' => $this->terminee_at ? $this->terminee_at->format('Y-m-d H:i:s') : null,
            'nom_client' => $this->nom_client,
            'telephone_client' => $this->telephone_client,
            'nom_affiche' => $this->nom_affiche,
            'telephone_affiche' => $this->telephone_affiche,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'salle_id' => (int) $this->salle_id,
            'salle' => new SalleResource($this->whenLoaded('salle')),
            'cree_par_id' => $this->cree_par_id ? (int) $this->cree_par_id : null,
            'createur' => new UserResource($this->whenLoaded('createur')),
            'equipements' => $this->whenLoaded('equipements', function () {
                return $this->equipements->map(function ($equipement) {
                    return [
                        'id' => $equipement->id,
                        'nom' => $equipement->nom,
                        'description' => $equipement->description,
                        'image_url' => $equipement->image_url,
                        'quantity' => (int) ($equipement->pivot->quantity ?? 1),
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
