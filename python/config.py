"""Parâmetros calibráveis do Guarita Digital."""

from __future__ import annotations

import os
from pathlib import Path

PASTA_PYTHON = Path(__file__).resolve().parent

MELHORES_QUADROS = 4

NITIDEZ_MINIMA = 45.0
BRILHO_MINIMO = 35.0
BRILHO_MAXIMO = 225.0
CONTRASTE_MINIMO = 18.0
ALTURA_MINIMA_PLACA = 32

CONFIANCA_YOLO_MINIMA = 0.28
CONFIANCA_OCR_MINIMA = 0.45
CONFIANCA_OCR_CORRIGIDA = 0.62
QUADROS_PARA_CONFIRMAR = 2
MARGEM_EMPATE = 0.10
INTERVALO_DUPLICIDADE_SEGUNDOS = 20.0

SALVAR_DIAGNOSTICO = False
PASTA_DIAGNOSTICO = PASTA_PYTHON / "diagnosticos"

REPOSITORIO_MODELO = os.getenv(
    "GUARITA_REPOSITORIO_YOLO",
    "yasirfaizahmed/license-plate-object-detection",
)
ARQUIVO_MODELO = os.getenv("GUARITA_ARQUIVO_YOLO", "best.pt")
CAMINHO_MODELO = os.getenv("GUARITA_MODELO_YOLO", "").strip()
MODELO_OCR = os.getenv("GUARITA_MODELO_OCR", "en_PP-OCRv5_mobile_rec")
DISPOSITIVO = os.getenv("GUARITA_DISPOSITIVO", "cpu").strip() or "cpu"
