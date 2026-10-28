<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class LivraisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producteur' => ['required', 'integer', Rule::exists('producteurs', 'id')],
            'variete' => ['required', 'string', 'max:100'],
            'poids_kg' => ['required', 'numeric', 'gt:0'],
            'sens' => ['required', Rule::in(['entree', 'sortie'])],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Donnees invalides',
            'errors' => $validator->errors(),
        ], 422));
    }
}
