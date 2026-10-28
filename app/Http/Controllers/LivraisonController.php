<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuffisant;
use App\Http\Requests\LivraisonRequest;
use App\Http\Resources\LivraisonResource;
use App\Models\Livraison;
use App\Models\Producteur;
use App\Services\LivraisonService;

class LivraisonController extends Controller
{
    public function __construct(private LivraisonService $livraisons)
    {
    }

    public function index()
    {
        return LivraisonResource::collection(Livraison::all());
    }

    public function store(LivraisonRequest $request)
    {
        $data = $request->validated();

        try {
            $livraison = $this->livraisons->enregistrer(
                Producteur::findOrFail($data['producteur']),
                $data['variete'],
                (float) $data['poids_kg'],
                $data['sens'],
            );
        } catch (StockInsuffisant $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return (new LivraisonResource($livraison))->response()->setStatusCode(201);
    }

    public function show(Livraison $livraison)
    {
        return new LivraisonResource($livraison);
    }
}
