<?php

namespace Tests\Unit;

use App\Http\Responses\ErreurApi;
use Tests\TestCase;

class ErreurApiTest extends TestCase
{
    public function test_errors_vaut_un_objet_vide_par_defaut(): void
    {
        // Doit serialiser en {} et non en [] pour garder une forme stable.
        $r = ErreurApi::reponse('Ressource introuvable', 404);

        $this->assertSame(404, $r->getStatusCode());
        $this->assertSame('{"message":"Ressource introuvable","errors":{}}', $r->getContent());
    }

    public function test_errors_est_transmis_tel_quel(): void
    {
        $r = ErreurApi::reponse('Donnees invalides', 422, ['nom' => ['requis']]);

        $this->assertSame(['nom' => ['requis']], $r->getData(true)['errors']);
    }
}
