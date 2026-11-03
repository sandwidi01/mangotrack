<?php

namespace Database\Factories;

use App\Models\Producteur;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Producteur> */
class ProducteurFactory extends Factory
{
    protected $model = Producteur::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->unique()->name(),
            'village' => fake()->city(),
        ];
    }
}
