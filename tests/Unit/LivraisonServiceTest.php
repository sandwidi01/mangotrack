<?php

namespace Tests\Unit;

use App\Exceptions\StockInsuffisant;
use App\Models\Livraison;
use App\Models\Producteur;
use App\Services\LivraisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivraisonServiceTest extends TestCase
{
    use RefreshDatabase;

    private LivraisonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LivraisonService();
    }

    public function test_stock_vaut_zero_sans_livraison(): void
    {
        $this->assertSame(0.0, $this->service->stock(Producteur::factory()->create()));
    }

    public function test_stock_est_entrees_moins_sorties(): void
    {
        $p = Producteur::factory()->create();
        Livraison::factory()->for($p)->create(['poids_kg' => 40]);
        Livraison::factory()->for($p)->create(['poids_kg' => 10.5]);
        Livraison::factory()->for($p)->sortie()->create(['poids_kg' => 20]);

        $this->assertSame(30.5, $this->service->stock($p));
    }

    public function test_stock_ignore_les_livraisons_des_autres_producteurs(): void
    {
        $p = Producteur::factory()->create();
        Livraison::factory()->create(['poids_kg' => 99]);

        $this->assertSame(0.0, $this->service->stock($p));
    }

    public function test_enregistrer_une_entree_cree_la_livraison(): void
    {
        $p = Producteur::factory()->create();

        $l = $this->service->enregistrer($p, 'Kent', 40, 'entree');

        $this->assertSame($p->id, $l->producteur_id);
        $this->assertDatabaseHas('livraisons', ['id' => $l->id, 'sens' => 'entree', 'variete' => 'Kent']);
    }

    public function test_sortie_egale_au_stock_est_permise(): void
    {
        $p = Producteur::factory()->create();
        $this->service->enregistrer($p, 'Kent', 40, 'entree');

        $this->service->enregistrer($p, 'Kent', 40, 'sortie');

        $this->assertSame(0.0, $this->service->stock($p));
    }

    public function test_sortie_superieure_au_stock_leve_une_exception_et_n_ecrit_rien(): void
    {
        $p = Producteur::factory()->create();
        $this->service->enregistrer($p, 'Kent', 40, 'entree');

        try {
            $this->service->enregistrer($p, 'Kent', 50, 'sortie');
            $this->fail('StockInsuffisant attendue');
        } catch (StockInsuffisant $e) {
            $this->assertStringContainsString('Stock insuffisant', $e->getMessage());
        }

        $this->assertDatabaseCount('livraisons', 1);
        $this->assertSame(40.0, $this->service->stock($p));
    }

    public function test_sortie_sans_aucun_stock_est_refusee(): void
    {
        $this->expectException(StockInsuffisant::class);

        $this->service->enregistrer(Producteur::factory()->create(), 'Kent', 1, 'sortie');
    }
}
