<?php

namespace Tests\Feature;

use App\Models\Livraison;
use App\Models\Producteur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProducteurApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_liste_vide(): void
    {
        $this->getJson('/api/producteurs')->assertOk()->assertExactJson([]);
    }

    public function test_liste_des_producteurs(): void
    {
        Producteur::factory()->count(3)->create();

        $this->getJson('/api/producteurs')->assertOk()->assertJsonCount(3)
            ->assertJsonStructure([['id', 'nom', 'village']]);
    }

    public function test_creation_retourne_201(): void
    {
        $this->postJson('/api/producteurs', ['nom' => 'Awa', 'village' => 'Orodara'])
            ->assertCreated()
            ->assertJsonFragment(['nom' => 'Awa', 'village' => 'Orodara']);

        $this->assertDatabaseHas('producteurs', ['nom' => 'Awa']);
    }

    public function test_creation_sans_village_est_permise(): void
    {
        $this->postJson('/api/producteurs', ['nom' => 'Awa'])->assertCreated()->assertJson(['village' => null]);
    }

    public function test_creation_nom_absent_422(): void
    {
        $this->postJson('/api/producteurs', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['nom']])
            ->assertJson(['message' => 'Donnees invalides']);
    }

    public function test_creation_nom_trop_court_422(): void
    {
        $this->postJson('/api/producteurs', ['nom' => 'A'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['nom']]);
    }

    public function test_creation_nom_trop_long_422(): void
    {
        $this->postJson('/api/producteurs', ['nom' => str_repeat('a', 101)])->assertStatus(422);
    }

    public function test_creation_nom_deja_pris_422_et_pas_500(): void
    {
        Producteur::factory()->create(['nom' => 'Awa']);

        $this->postJson('/api/producteurs', ['nom' => 'Awa'])->assertStatus(422);
        $this->assertDatabaseCount('producteurs', 1);
    }

    public function test_affichage(): void
    {
        $p = Producteur::factory()->create(['nom' => 'Awa', 'village' => 'Orodara']);

        $this->getJson("/api/producteurs/{$p->id}")->assertOk()
            ->assertExactJson(['id' => $p->id, 'nom' => 'Awa', 'village' => 'Orodara']);
    }

    public function test_affichage_inconnu_404_au_format_du_contrat(): void
    {
        $this->getJson('/api/producteurs/999')->assertNotFound()
            ->assertExactJson(['message' => 'Ressource introuvable', 'errors' => []]);
    }

    public function test_modification(): void
    {
        $p = Producteur::factory()->create(['nom' => 'Awa']);

        $this->putJson("/api/producteurs/{$p->id}", ['nom' => 'Fatou', 'village' => 'Bobo'])
            ->assertOk()->assertJson(['nom' => 'Fatou', 'village' => 'Bobo']);

        $this->assertDatabaseHas('producteurs', ['id' => $p->id, 'nom' => 'Fatou']);
    }

    public function test_modification_en_gardant_son_propre_nom_est_permise(): void
    {
        $p = Producteur::factory()->create(['nom' => 'Awa']);

        $this->putJson("/api/producteurs/{$p->id}", ['nom' => 'Awa', 'village' => 'Bobo'])->assertOk();
    }

    public function test_modification_avec_le_nom_d_un_autre_422(): void
    {
        Producteur::factory()->create(['nom' => 'Awa']);
        $b = Producteur::factory()->create(['nom' => 'Fatou']);

        $this->putJson("/api/producteurs/{$b->id}", ['nom' => 'Awa'])->assertStatus(422);
    }

    public function test_modification_inconnu_404(): void
    {
        $this->putJson('/api/producteurs/999', ['nom' => 'Awa'])->assertNotFound();
    }

    public function test_suppression_retourne_204(): void
    {
        $p = Producteur::factory()->create();

        $this->deleteJson("/api/producteurs/{$p->id}")->assertNoContent();

        $this->assertDatabaseMissing('producteurs', ['id' => $p->id]);
    }

    public function test_suppression_inconnu_404(): void
    {
        $this->deleteJson('/api/producteurs/999')->assertNotFound();
    }

    public function test_suppression_avec_livraisons_409(): void
    {
        $p = Producteur::factory()->create();
        Livraison::factory()->for($p)->create();

        $this->deleteJson("/api/producteurs/{$p->id}")
            ->assertStatus(409)
            ->assertJsonStructure(['message', 'errors']);

        $this->assertDatabaseHas('producteurs', ['id' => $p->id]);
    }

    public function test_stock(): void
    {
        $p = Producteur::factory()->create();
        Livraison::factory()->for($p)->create(['poids_kg' => 40]);
        Livraison::factory()->for($p)->sortie()->create(['poids_kg' => 15]);

        $this->getJson("/api/producteurs/{$p->id}/stock")->assertOk()
            ->assertExactJson(['producteur_id' => $p->id, 'stock_kg' => 25]);
    }

    public function test_stock_inconnu_404(): void
    {
        $this->getJson('/api/producteurs/999/stock')->assertNotFound();
    }

    public function test_methode_non_autorisee_405_au_format_du_contrat(): void
    {
        $this->patchJson('/api/producteurs')->assertStatus(405)->assertJsonStructure(['message', 'errors']);
    }

    public function test_route_inconnue_404_au_format_du_contrat(): void
    {
        $this->getJson('/api/inconnu')->assertNotFound()->assertJson(['message' => 'Ressource introuvable']);
    }

    public function test_reponse_json_meme_sans_header_accept(): void
    {
        $this->get('/api/producteurs/999')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }
}
