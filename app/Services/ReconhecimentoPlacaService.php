<?php

namespace App\Services;

use App\Exceptions\ReconhecimentoPlacaException;
use App\Support\Placa;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use JsonException;

class ReconhecimentoPlacaService
{
    public function reconhecer(string $caminhoImagem): string
    {
        $python = trim((string) config('guarita.python_executable', 'python'));
        $script = (string) config('guarita.placa_script', base_path('python/placa.py'));
        $tesseract = trim((string) config('guarita.tesseract_executable', ''));

        $this->validarExecutavelConfigurado($python, 'Python');

        if (! is_file($script)) {
            throw new ReconhecimentoPlacaException("Script de reconhecimento não encontrado: {$script}");
        }

        if (! is_file($caminhoImagem)) {
            throw new ReconhecimentoPlacaException('A imagem temporária não foi encontrada para o reconhecimento.');
        }

        if ($tesseract !== '') {
            $this->validarExecutavelConfigurado($tesseract, 'Tesseract');
        }

        $ambiente = array_filter([
            'TESSERACT_CMD' => $tesseract !== '' ? $tesseract : null,
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONUTF8' => '1',
        ], static fn (?string $valor): bool => $valor !== null && $valor !== '');

        $resultado = Process::timeout(max(5, (int) config('guarita.ocr_timeout_seconds', 90)))
            ->idleTimeout(max(5, (int) config('guarita.ocr_idle_timeout_seconds', 30)))
            ->env($ambiente)
            ->path(dirname($script))
            ->run([$python, $script, $caminhoImagem]);

        if ($resultado->failed()) {
            $erro = trim($resultado->errorOutput()) ?: trim($resultado->output());

            throw new ReconhecimentoPlacaException(
                Str::limit($erro ?: 'O reconhecimento da placa falhou.', 1000),
            );
        }

        $placa = $this->extrairPlaca($resultado->output());

        if ($placa === null) {
            throw new ReconhecimentoPlacaException('Nenhuma placa brasileira válida foi reconhecida.');
        }

        return $placa;
    }

    private function extrairPlaca(string $saida): ?string
    {
        $saida = trim($saida);

        if ($saida === '') {
            return null;
        }

        try {
            $json = json_decode($saida, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($json) && isset($json['placa'])) {
                $placa = Placa::normalizar((string) $json['placa']);

                return Placa::valida($placa) ? $placa : null;
            }
        } catch (JsonException) {
            // O script atual retorna texto simples; JSON também é aceito para evolução futura.
        }

        $linhas = array_reverse(array_filter(array_map(
            'trim',
            preg_split('/\R/', $saida) ?: [],
        )));

        foreach ($linhas as $linha) {
            $placa = Placa::normalizar($linha);

            if (Placa::valida($placa)) {
                return $placa;
            }
        }

        return null;
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
