<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index composite : le calcul du stock filtre par producteur ET par sens.
     * (MySQL indexe deja producteur_id seul via la cle etrangere.)
     * Compromis : lecture du stock plus rapide, chaque ecriture met a jour un index de plus.
     */
    public function up(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->index(['producteur_id', 'sens'], 'livraisons_producteur_sens_index');
        });
    }

    public function down(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->dropIndex('livraisons_producteur_sens_index');
        });
    }
};
