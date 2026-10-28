<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Livraison extends Model
{
    public $timestamps = false;

    protected $fillable = ['producteur_id', 'variete', 'poids_kg', 'sens'];

    public function producteur(): BelongsTo
    {
        return $this->belongsTo(Producteur::class);
    }
}
