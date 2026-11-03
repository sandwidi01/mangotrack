<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuffisant;
use App\Http\Requests\LivraisonIndexRequest;
use App\Http\Requests\LivraisonRequest;
use App\Http\Resources\LivraisonResource;
use App\Http\Responses\ErreurApi;
use App\Models\Livraison;
use App\Models\Producteur;
use App\Services\LivraisonService;

class LivraisonController extends Controller
{
    public function __construct(private LivraisonService $livraisons)
    {
    }

    public function index(LivraisonIndexRequest $request)
    {
        // Le filtre s'applique avant la pagination, sinon le total serait faux.
        $query = Livraison::query()->orderBy('id');

        if ($request->has('producteur_id')) {
            $query->where('producteur_id', $request->integer('producteur_id'));
        }

        $total = $query->count();
        $page = $request->integer('page', 1);
        $items = $query->forPage($page, $request->perPage())->get();

        return LivraisonResource::collection($items)
            ->response()
            ->header('X-Total-Count', $total);
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
            return ErreurApi::reponse($e->getMessage(), 409);
        }

        return (new LivraisonResource($livraison))->response()->setStatusCode(201);
    }

    public function show(Livraison $livraison)
    {
        return new LivraisonResource($livraison);
    }
}
