<?php

namespace Database\Factories;

use App\Models\Livraison;
use App\Models\Producteur;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Livraison> */
class LivraisonFactory extends Factory
{
    protected $model = Livraison::class;

    public function definition(): array
    {
        return [
            'producteur_id' => Producteur::factory(),
            'variete' => fake()->randomElement(['Kent', 'Amelie', 'Brooks']),
            'poids_kg' => fake()->randomFloat(2, 1, 100),
            'sens' => 'entree',
        ];
    }

    public function sortie(): static
    {
        return $this->state(['sens' => 'sortie']);
    }
}
