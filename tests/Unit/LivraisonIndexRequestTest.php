<?php

namespace Tests\Unit;

use App\Http\Requests\LivraisonIndexRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LivraisonIndexRequestTest extends TestCase
{
    public function test_per_page_par_defaut(): void
    {
        $this->assertSame(50, LivraisonIndexRequest::create('/x', 'GET')->perPage());
    }

    public function test_per_page_est_plafonne_a_100(): void
    {
        $this->assertSame(100, LivraisonIndexRequest::create('/x', 'GET', ['per_page' => 5000])->perPage());
    }

    public function test_per_page_valide_est_conserve(): void
    {
        $this->assertSame(10, LivraisonIndexRequest::create('/x', 'GET', ['per_page' => 10])->perPage());
    }

    #[DataProvider('parametresInvalides')]
    public function test_parametres_invalides(array $params): void
    {
        $v = Validator::make($params, (new LivraisonIndexRequest())->rules());

        $this->assertTrue($v->fails());
    }

    public static function parametresInvalides(): array
    {
        return [
            'page zero' => [['page' => 0]],
            'page texte' => [['page' => 'abc']],
            'per_page zero' => [['per_page' => 0]],
            'producteur_id negatif' => [['producteur_id' => -1]],
        ];
    }

    public function test_parametres_valides(): void
    {
        $v = Validator::make(
            ['page' => 2, 'per_page' => 10, 'producteur_id' => 3],
            (new LivraisonIndexRequest())->rules()
        );

        $this->assertTrue($v->passes());
    }
}
