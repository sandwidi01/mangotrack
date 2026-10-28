<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producteur extends Model
{
    public $timestamps = false;

    protected $fillable = ['nom', 'village'];

    public function livraisons(): HasMany
    {
        return $this->hasMany(Livraison::class);
    }
}
