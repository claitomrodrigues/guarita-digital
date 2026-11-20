<?php
namespace App\Services;

use App\Exceptions\ReconhecimentoPlacaException;
use App\Support\Placa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class ReconhecimentoPlacaService
{
    public function reconhecer(string $caminhoImagem): string
    {
        if (! is_file($caminhoImagem)) {
            throw new ReconhecimentoPlacaException(
                'A imagem temporária não foi encontrada para o reconhecimento.'
            );
        }

        $modo = strtolower(trim((string) config('guarita.ocr_mode', 'local')));

        if ($modo === 'api') {
            return $this->reconhecerViaApi($caminhoImagem);
        }

        if ($modo === 'local') {
            return $this->reconhecerLocalmente($caminhoImagem);
        }

        throw new ReconhecimentoPlacaException(
            'Modo de reconhecimento inválido.'
        );
    }

    private function reconhecerViaApi(string $caminhoImagem): string
    {
        $url = rtrim((string) config('guarita.ocr_api_url', ''), '/');
        $token = (string) config('guarita.ocr_api_token', '');

        if ($url === '' || $token === '') {
            throw new ReconhecimentoPlacaException(
                'A API de reconhecimento não foi configurada.'
            );
        }

        if (! str_starts_with($url, 'https://')) {
            throw new ReconhecimentoPlacaException(
                'A API de reconhecimento deve utilizar HTTPS.'
            );
        }

        $arquivo = @fopen($caminhoImagem, 'rb');

        if ($arquivo === false) {
            throw new ReconhecimentoPlacaException(
                'Não foi possível abrir a imagem para envio.'
            );
        }

        try {
            $resposta = Http::withToken($token)
                ->acceptJson()
                ->connectTimeout(15)
                ->timeout(max(30, (int) config('guarita.ocr_timeout_seconds', 180)))
                ->attach('arquivo', $arquivo, basename($caminhoImagem))
                ->post($url.'/reconhecer');

            if (! $resposta->successful()) {
                throw new ReconhecimentoPlacaException(
                    'A API de reconhecimento retornou erro HTTP '.$resposta->status().'.'
                );
            }

            return $this->extrairPlaca($resposta->body());

        } catch (ReconhecimentoPlacaException $excecao) {
            throw $excecao;

        } catch (Throwable $excecao) {
            throw new ReconhecimentoPlacaException(
                'Falha na comunicação com a API: '.
                Str::limit($this->textoUtf8($excecao->getMessage()), 500),
                previous: $excecao,
            );

        } finally {
            fclose($arquivo);
        }
    }

    private function reconhecerLocalmente(string $caminhoImagem): string
    {
        $python = trim((string) config('guarita.python_executable', 'python'));
        $script = (string) config(
            'guarita.placa_script',
            base_path('python/reconhecer_imagem.py')
        );

        $this->validarExecutavelConfigurado($python, 'Python');

        if (! is_file($script)) {
            throw new ReconhecimentoPlacaException(
                "Script de reconhecimento não encontrado: {$script}"
            );
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
            $resultado = Process::timeout(
                max(5, (int) config('guarita.ocr_timeout_seconds', 180))
            )
                ->idleTimeout(
                    max(5, (int) config('guarita.ocr_idle_timeout_seconds', 90))
                )
                ->env($ambiente)
                ->path(dirname($script))
                ->run([$python, $script, $caminhoImagem]);

        } catch (Throwable $excecao) {
            throw new ReconhecimentoPlacaException(
                'Não foi possível executar o Python: '.
                Str::limit($this->textoUtf8($excecao->getMessage()), 800),
                previous: $excecao,
            );
        }

        if ($resultado->failed()) {
            $erro = $this->textoUtf8(
                $resultado->errorOutput() ?: $resultado->output()
            );

            throw new ReconhecimentoPlacaException(
                Str::limit($erro ?: 'O reconhecimento da placa falhou.', 1000)
            );
        }

        return $this->extrairPlaca($resultado->output());
    }

    private function extrairPlaca(string $saida): string
    {
        $saida = $this->textoUtf8($saida);

        if ($saida === '') {
            throw new ReconhecimentoPlacaException(
                'O reconhecimento não devolveu uma resposta.'
            );
        }

        try {
            $resposta = json_decode(
                $saida,
                true,
                512,
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (JsonException $excecao) {
            throw new ReconhecimentoPlacaException(
                'O reconhecimento devolveu uma resposta inválida.',
                previous: $excecao,
            );
        }

        if (! is_array($resposta)) {
            throw new ReconhecimentoPlacaException(
                'O reconhecimento devolveu uma resposta inválida.'
            );
        }

        $placa = Placa::normalizar((string) ($resposta['placa'] ?? ''));

        if (Placa::valida($placa)) {
            return $placa;
        }

        $motivo = $this->textoUtf8((string) ($resposta['motivo'] ?? ''));

        throw new ReconhecimentoPlacaException(
            $motivo ?: 'Nenhuma placa brasileira válida foi reconhecida.'
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

    private function validarExecutavelConfigurado(
        string $executavel,
        string $nome
    ): void {
        if ($executavel === '') {
            throw new ReconhecimentoPlacaException(
                "O executável do {$nome} não foi configurado."
            );
        }

        $pareceCaminho = str_contains($executavel, '/')
            || str_contains($executavel, '\\')
            || preg_match('/^[A-Z]:/i', $executavel) === 1;

        if ($pareceCaminho && ! is_file($executavel)) {
            throw new ReconhecimentoPlacaException(
                "Executável do {$nome} não encontrado: {$executavel}"
            );
        }
    }
}
