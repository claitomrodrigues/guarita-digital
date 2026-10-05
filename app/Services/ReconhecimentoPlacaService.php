<?php

namespace App\Services;

use App\Exceptions\ReconhecimentoPlacaException;
use App\Support\Placa;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class ReconhecimentoPlacaService
{
    public function reconhecer(string $caminhoImagem): string
    {
        $python = trim((string) config('guarita.python_executable', 'python'));
        $script = (string) config('guarita.placa_script', base_path('python/reconhecer_imagem.py'));

        $this->validarExecutavelConfigurado($python, 'Python');

        if (! is_file($script)) {
            throw new ReconhecimentoPlacaException("Script de reconhecimento não encontrado: {$script}");
        }

        if (! is_file($caminhoImagem)) {
            throw new ReconhecimentoPlacaException('A imagem temporária não foi encontrada para o reconhecimento.');
        }

        $ambienteSistema = getenv();
$ambiente = is_array($ambienteSistema) ? $ambienteSistema : [];

if (PHP_OS_FAMILY === 'Windows') {
    $systemRoot = getenv('SystemRoot')
        ?: getenv('windir')
        ?: 'C:\\Windows';

    $ambiente['SystemRoot'] = $systemRoot;
    $ambiente['windir'] = $systemRoot;
}

$ambiente['PYTHONIOENCODING'] = 'utf-8';
$ambiente['PYTHONUTF8'] = '1';

        try {
            $resultado = Process::timeout(max(5, (int) config('guarita.ocr_timeout_seconds', 180)))
                ->idleTimeout(max(5, (int) config('guarita.ocr_idle_timeout_seconds', 90)))
                ->env($ambiente)
                ->path(dirname($script))
                ->run([$python, $script, $caminhoImagem]);
        } catch (Throwable $excecao) {
            throw new ReconhecimentoPlacaException(
                'Não foi possível executar o Python: '.Str::limit($this->textoUtf8($excecao->getMessage()), 800),
                previous: $excecao,
            );
        }

        if ($resultado->failed()) {
            $erro = $this->textoUtf8($resultado->errorOutput() ?: $resultado->output());

            throw new ReconhecimentoPlacaException(
                Str::limit($erro ?: 'O reconhecimento da placa falhou.', 1000),
            );
        }

        return $this->extrairPlaca($resultado->output());
    }

    private function extrairPlaca(string $saida): string
    {
        $saida = $this->textoUtf8($saida);

        if ($saida === '') {
            throw new ReconhecimentoPlacaException('O Python não devolveu uma resposta.');
        }

        try {
            $resposta = json_decode($saida, true, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (JsonException $excecao) {
            throw new ReconhecimentoPlacaException(
                'O Python devolveu uma resposta inválida.',
                previous: $excecao,
            );
        }

        if (! is_array($resposta)) {
            throw new ReconhecimentoPlacaException('O Python devolveu uma resposta inválida.');
        }

        $placa = Placa::normalizar((string) ($resposta['placa'] ?? ''));

        if (Placa::valida($placa)) {
            return $placa;
        }

        $motivo = $this->textoUtf8((string) ($resposta['motivo'] ?? ''));

        throw new ReconhecimentoPlacaException(
            $motivo ?: 'Nenhuma placa brasileira válida foi reconhecida.',
        );
    }

    private function textoUtf8(string $texto): string
    {
        $texto = trim($texto);

        if ($texto === '' || mb_check_encoding($texto, 'UTF-8')) {
            return $texto;
        }

        return trim(mb_scrub($texto, 'UTF-8'));
    }

    private function validarExecutavelConfigurado(string $executavel, string $nome): void
    {
        if ($executavel === '') {
            throw new ReconhecimentoPlacaException("O executável do {$nome} não foi configurado.");
        }

        $pareceCaminho = str_contains($executavel, '/')
            || str_contains($executavel, '\\')
            || preg_match('/^[A-Z]:/i', $executavel) === 1;

        if ($pareceCaminho && ! is_file($executavel)) {
            throw new ReconhecimentoPlacaException("Executável do {$nome} não encontrado: {$executavel}");
        }
    }
}
