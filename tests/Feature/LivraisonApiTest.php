<?php

namespace Tests\Feature;

use App\Models\Livraison;
use App\Models\Producteur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LivraisonApiTest extends TestCase
{
    use RefreshDatabase;

    private function corps(Producteur $p, array $override = []): array
    {
        return array_merge(['producteur' => $p->id, 'variete' => 'Kent', 'poids_kg' => 40, 'sens' => 'entree'], $override);
    }

    // ---- POST /api/livraisons

    public function test_entree_retourne_201(): void
    {
        $p = Producteur::factory()->create();

        $this->postJson('/api/livraisons', $this->corps($p))
            ->assertCreated()
            ->assertJson(['producteur_id' => $p->id, 'variete' => 'Kent', 'poids_kg' => 40, 'sens' => 'entree']);

        $this->assertDatabaseCount('livraisons', 1);
    }

    public function test_sortie_inferieure_au_stock_201(): void
    {
        $p = Producteur::factory()->create();
        Livraison::factory()->for($p)->create(['poids_kg' => 40]);

        $this->postJson('/api/livraisons', $this->corps($p, ['poids_kg' => 10, 'sens' => 'sortie']))->assertCreated();
    }

    public function test_sortie_egale_au_stock_201_et_stock_tombe_a_zero(): void
    {
        $p = Producteur::factory()->create();
        Livraison::factory()->for($p)->create(['poids_kg' => 40]);

        $this->postJson('/api/livraisons', $this->corps($p, ['sens' => 'sortie']))->assertCreated();
        $this->getJson("/api/producteurs/{$p->id}/stock")->assertJson(['stock_kg' => 0]);
    }

    public function test_sortie_superieure_au_stock_409(): void
    {
        $p = Producteur::factory()->create();
        Livraison::factory()->for($p)->create(['poids_kg' => 40]);

        $this->postJson('/api/livraisons', $this->corps($p, ['poids_kg' => 50, 'sens' => 'sortie']))
            ->assertStatus(409)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonFragment(['message' => 'Stock insuffisant : sortie de 50 kg refusee']);

        $this->assertDatabaseCount('livraisons', 1);
    }

    public function test_sortie_sans_stock_409(): void
    {
        $p = Producteur::factory()->create();

        $this->postJson('/api/livraisons', $this->corps($p, ['sens' => 'sortie']))->assertStatus(409);
    }

    public function test_le_stock_d_un_producteur_ne_couvre_pas_un_autre(): void
    {
        $a = Producteur::factory()->create();
        $b = Producteur::factory()->create();
        Livraison::factory()->for($a)->create(['poids_kg' => 100]);

        $this->postJson('/api/livraisons', $this->corps($b, ['sens' => 'sortie']))->assertStatus(409);
    }

    #[DataProvider('corpsInvalides')]
    public function test_corps_invalide_422(array $override, string $champ): void
    {
        $p = Producteur::factory()->create();

        $this->postJson('/api/livraisons', $this->corps($p, $override))
            ->assertStatus(422)
            ->assertJson(['message' => 'Donnees invalides'])
            ->assertJsonStructure(['errors' => [$champ]]);

        $this->assertDatabaseCount('livraisons', 0);
    }

    public static function corpsInvalides(): array
    {
        return [
            'poids zero' => [['poids_kg' => 0], 'poids_kg'],
            'poids negatif' => [['poids_kg' => -5], 'poids_kg'],
            'poids texte' => [['poids_kg' => 'beaucoup'], 'poids_kg'],
            'poids absent' => [['poids_kg' => null], 'poids_kg'],
            'sens inconnu' => [['sens' => 'vente'], 'sens'],
            'sens absent' => [['sens' => null], 'sens'],
            'variete absente' => [['variete' => null], 'variete'],
            'variete trop longue' => [['variete' => str_repeat('a', 101)], 'variete'],
            'producteur inconnu' => [['producteur' => 9999], 'producteur'],
            'producteur absent' => [['producteur' => null], 'producteur'],
        ];
    }

    // ---- GET /api/livraisons/{id}

    public function test_affichage(): void
    {
        $l = Livraison::factory()->create(['variete' => 'Kent', 'poids_kg' => 12.5]);

        $this->getJson("/api/livraisons/{$l->id}")->assertOk()
            ->assertExactJson([
                'id' => $l->id, 'producteur_id' => $l->producteur_id,
                'variete' => 'Kent', 'poids_kg' => 12.5, 'sens' => 'entree',
            ]);
    }

    public function test_affichage_inconnu_404(): void
    {
        $this->getJson('/api/livraisons/999')->assertNotFound()
            ->assertExactJson(['message' => 'Ressource introuvable', 'errors' => []]);
    }

    public function test_modification_et_suppression_non_exposees(): void
    {
        $l = Livraison::factory()->create();

        $this->putJson("/api/livraisons/{$l->id}", [])->assertStatus(405);
        $this->deleteJson("/api/livraisons/{$l->id}")->assertStatus(405);
    }

    // ---- GET /api/livraisons (pagination, filtre)

    public function test_liste_vide_avec_total_zero(): void
    {
        $this->getJson('/api/livraisons')->assertOk()->assertExactJson([])->assertHeader('X-Total-Count', 0);
    }

    public function test_liste_par_defaut(): void
    {
        Livraison::factory()->count(3)->create();

        $this->getJson('/api/livraisons')->assertOk()->assertJsonCount(3)
            ->assertHeader('X-Total-Count', 3)
            ->assertJsonStructure([['id', 'producteur_id', 'variete', 'poids_kg', 'sens']]);
    }

    public function test_pagination(): void
    {
        Livraison::factory()->count(5)->create();

        $all = collect($this->getJson('/api/livraisons?per_page=100')->json())->pluck('id')->all();
        $page2 = $this->getJson('/api/livraisons?page=2&per_page=2');

        $page2->assertOk()->assertJsonCount(2)->assertHeader('X-Total-Count', 5);
        $this->assertSame(array_slice($all, 2, 2), collect($page2->json())->pluck('id')->all());

        $this->getJson('/api/livraisons?page=3&per_page=2')->assertJsonCount(1);
        $this->getJson('/api/livraisons?page=4&per_page=2')->assertOk()->assertJsonCount(0);
    }

    public function test_per_page_plafonne_a_100(): void
    {
        Livraison::factory()->count(101)->create();

        $this->getJson('/api/livraisons?per_page=1000')->assertJsonCount(100)->assertHeader('X-Total-Count', 101);
    }

    public function test_filtre_par_producteur_et_total_apres_filtre(): void
    {
        $a = Producteur::factory()->create();
        $b = Producteur::factory()->create();
        Livraison::factory()->count(3)->for($a)->create();
        Livraison::factory()->count(2)->for($b)->create();

        $r = $this->getJson("/api/livraisons?producteur_id={$a->id}&per_page=2");

        $r->assertOk()->assertJsonCount(2)->assertHeader('X-Total-Count', 3);
        $this->assertSame([$a->id], collect($r->json())->pluck('producteur_id')->unique()->values()->all());
    }

    #[DataProvider('parametresListeInvalides')]
    public function test_parametres_de_liste_invalides_422(string $query, string $champ): void
    {
        $this->getJson("/api/livraisons?{$query}")
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => [$champ]]);
    }

    public static function parametresListeInvalides(): array
    {
        return [
            'page 0' => ['page=0', 'page'],
            'page texte' => ['page=abc', 'page'],
            'per_page 0' => ['per_page=0', 'per_page'],
            'producteur_id negatif' => ['producteur_id=-1', 'producteur_id'],
            'producteur_id texte' => ['producteur_id=abc', 'producteur_id'],
        ];
    }
}
