<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class LivraisonRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'producteur' => ['required', 'integer', Rule::exists('producteurs', 'id')],
            'variete' => ['required', 'string', 'max:100'],
            'poids_kg' => ['required', 'numeric', 'gt:0'],
            'sens' => ['required', Rule::in(['entree', 'sortie'])],
        ];
    }
}
