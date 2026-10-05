"""Confere a versão do Python e as dependências sem baixar os modelos."""

from __future__ import annotations

import importlib
import sys

DEPENDENCIAS = {
    "numpy": "NumPy",
    "cv2": "OpenCV",
    "ultralytics": "Ultralytics/YOLO",
    "huggingface_hub": "Hugging Face Hub",
    "paddle": "PaddlePaddle",
    "paddleocr": "PaddleOCR",
}


def configurar_utf8() -> None:
    for fluxo in (sys.stdout, sys.stderr):
        reconfigurar = getattr(fluxo, "reconfigure", None)
        if callable(reconfigurar):
            reconfigurar(encoding="utf-8", errors="replace")


def main() -> int:
    configurar_utf8()
    print(f"Executável: {sys.executable}")
    print(
        f"Versão: Python {sys.version_info.major}.{sys.version_info.minor}.{sys.version_info.micro}"
    )

    erros: list[str] = []
    if sys.version_info[:2] != (3, 11):
        erros.append("use exatamente o Python 3.11")

    for modulo, nome in DEPENDENCIAS.items():
        try:
            importlib.import_module(modulo)
        except Exception as excecao:  # noqa: BLE001 - diagnóstico de módulos nativos
            erros.append(f"{nome}: {excecao}")
        else:
            print(f"OK: {nome}")

    if erros:
        print("\nPendências encontradas:", file=sys.stderr)
        for erro in erros:
            print(f"- {erro}", file=sys.stderr)
        return 1

    print("\nAmbiente pronto para o reconhecimento.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
