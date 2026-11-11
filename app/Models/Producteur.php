<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producteur extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['nom', 'village'];

    public function livraisons(): HasMany
    {
        return $this->hasMany(Livraison::class);
    }
}
