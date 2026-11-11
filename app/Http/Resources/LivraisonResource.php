<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LivraisonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producteur_id' => $this->producteur_id,
            'variete' => $this->variete,
            'poids_kg' => (float) $this->poids_kg,
            'sens' => $this->sens,
        ];
    }
}
