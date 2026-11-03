<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProducteurRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:2', 'max:100',
                      Rule::unique('producteurs', 'nom')->ignore($this->route('producteur'))],
            'village' => ['nullable', 'string', 'max:100'],
        ];
    }
}
