<?php

namespace App\Services;

use App\Exceptions\StockInsuffisant;
use App\Models\Livraison;
use App\Models\Producteur;
use Illuminate\Support\Facades\DB;

class LivraisonService
{
    public function stock(Producteur $producteur): float
    {
        $entrees = $producteur->livraisons()->where('sens', 'entree')->sum('poids_kg');
        $sorties = $producteur->livraisons()->where('sens', 'sortie')->sum('poids_kg');

        return (float) $entrees - (float) $sorties;
    }

    public function enregistrer(Producteur $producteur, string $variete, float $poids, string $sens): Livraison
    {
        // Transaction + verrou sur le producteur : deux sorties simultanees ne peuvent pas depasser le stock.
        return DB::transaction(function () use ($producteur, $variete, $poids, $sens) {
            Producteur::whereKey($producteur->id)->lockForUpdate()->first();

            if ($sens === 'sortie' && $poids > $this->stock($producteur)) {
                throw new StockInsuffisant('Stock insuffisant : sortie de '.$poids.' kg refusee');
            }

            return Livraison::create([
                'producteur_id' => $producteur->id,
                'variete' => $variete,
                'poids_kg' => $poids,
                'sens' => $sens,
            ]);
        });
    }
}
