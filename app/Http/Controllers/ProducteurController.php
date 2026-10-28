<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProducteurRequest;
use App\Http\Resources\ProducteurResource;
use App\Models\Producteur;
use App\Services\LivraisonService;

class ProducteurController extends Controller
{
    public function index()
    {
        return ProducteurResource::collection(Producteur::all());
    }

    public function store(ProducteurRequest $request)
    {
        $producteur = Producteur::create($request->validated());

        return (new ProducteurResource($producteur))
            ->response()->setStatusCode(201);
    }

    public function show(Producteur $producteur)
    {
        return new ProducteurResource($producteur);
    }

    public function update(ProducteurRequest $request, Producteur $producteur)
    {
        $producteur->update($request->validated());

        return new ProducteurResource($producteur);
    }

    public function stock(Producteur $producteur, LivraisonService $livraisons)
    {
        return response()->json([
            'producteur_id' => $producteur->id,
            'stock_kg' => $livraisons->stock($producteur),
        ]);
    }

    public function destroy(Producteur $producteur)
    {
        if ($producteur->livraisons()->exists()) {
            return response()->json([
                'message' => 'Suppression impossible : ce producteur a des livraisons',
            ], 409);
        }

        $producteur->delete();

        return response()->noContent();
    }
}
