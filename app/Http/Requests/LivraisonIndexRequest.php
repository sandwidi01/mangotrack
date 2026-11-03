<?php

namespace App\Http\Requests;

class LivraisonIndexRequest extends ApiFormRequest
{
    public const PER_PAGE_DEFAUT = 50;
    public const PER_PAGE_MAX = 100;

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1'],
            'producteur_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return min((int) $this->query('per_page', self::PER_PAGE_DEFAUT), self::PER_PAGE_MAX);
    }
}
