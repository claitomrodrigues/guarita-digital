"""Entrada de linha de comando usada pelo Laravel para reconhecer uma imagem.

O stdout contém sempre uma única linha JSON. Mensagens das bibliotecas são
redirecionadas para stderr para não invalidar a resposta consumida pelo PHP.
"""

from __future__ import annotations

import json
import os
import sys
from contextlib import redirect_stdout
from pathlib import Path

# Mantém as mensagens dos modelos e do script em UTF-8 quando o processo é
# iniciado pelo PHP no Windows/Laragon.
os.environ.setdefault("PYTHONIOENCODING", "utf-8")
os.environ.setdefault("PYTHONUTF8", "1")


def configurar_fluxos_utf8() -> None:
    """Evita bytes inválidos ao devolver a resposta para o Laravel."""

    for fluxo in (sys.stdout, sys.stderr):
        reconfigurar = getattr(fluxo, "reconfigure", None)
        if callable(reconfigurar):
            try:
                reconfigurar(encoding="utf-8", errors="replace")
            except (OSError, ValueError):
                pass


configurar_fluxos_utf8()


def escrever_json(**dados: object) -> None:
    """Mantém o stdout em JSON ASCII, independentemente do idioma da mensagem."""

    print(json.dumps(dados, ensure_ascii=True, separators=(",", ":")), flush=True)


def main() -> int:
    if len(sys.argv) != 2:
        escrever_json(
            placa=None,
            motivo="Informe o caminho da imagem capturada.",
        )
        return 0

    caminho = Path(sys.argv[1])
    if not caminho.is_file():
        escrever_json(
            placa=None,
            motivo=f"Imagem não encontrada: {caminho}",
        )
        return 0

    try:
        import cv2
        from reconhecimento import reconhecer_placa
    except Exception as excecao:  # noqa: BLE001 - fronteira entre Python e PHP
        escrever_json(
            placa=None,
            motivo=f"Dependências do reconhecimento indisponíveis: {excecao}",
        )
        return 0

    # imdecode também abre caminhos com acentos no Windows, caso em que o
    # cv2.imread pode falhar dependendo da versão do OpenCV.
    try:
        import numpy as np

        imagem = cv2.imdecode(np.fromfile(caminho, dtype=np.uint8), cv2.IMREAD_COLOR)
    except (OSError, ValueError):
        imagem = None
    if imagem is None:
        escrever_json(
            placa=None,
            motivo="Não foi possível abrir a imagem capturada.",
        )
        return 0

    try:
        # Os modelos escrevem mensagens de carregamento. Elas vão para stderr para
        # que o stdout contenha apenas a string esperada pelo Laravel.
        with redirect_stdout(sys.stderr):
            leitura = reconhecer_placa(imagem)
    except Exception as excecao:  # noqa: BLE001 - devolve falhas externas em JSON
        escrever_json(
            placa=None,
            motivo=f"Falha no reconhecimento: {excecao}",
        )
        return 0

    if not leitura.placa:
        escrever_json(
            placa=None,
            motivo=leitura.motivo or "Nenhuma placa foi reconhecida.",
        )
        return 0

    escrever_json(
        placa=leitura.placa,
        confianca=leitura.confianca,
        confianca_yolo=leitura.confianca_yolo,
        modelo=leitura.modelo,
        quadros_confirmados=leitura.quadros_confirmados,
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
