<?php

namespace App\Http\Controllers;

use App\Enums\OrigemAcesso;
use App\Enums\PerfilUsuario;
use App\Enums\SentidoPontoAcesso;
use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Enums\TipoVeiculo;
use App\Enums\TipoVinculo;
use App\Enums\StatusTriagem;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'perfis' => PerfilUsuario::opcoes(),
                'tipos_vinculo' => TipoVinculo::opcoes(),
                'tipos_veiculo' => TipoVeiculo::opcoes(),
                'tipos_acesso' => TipoAcesso::opcoes(),
                'status_acesso' => StatusAcesso::opcoes(),
                'status_triagem' => StatusTriagem::opcoes(),
                'origens_acesso' => OrigemAcesso::opcoes(),
                'sentidos_ponto_acesso' => SentidoPontoAcesso::opcoes(),
            ],
        ]);
    }
}
