"""Registro opcional de evidências para calibração e análise de falhas."""

from __future__ import annotations

import json
import time
from pathlib import Path
from typing import Any

import cv2
import numpy as np

import config


class Diagnostico:
    def __init__(self) -> None:
        self.ativo = config.SALVAR_DIAGNOSTICO
        self.pasta: Path | None = None
        self.dados: dict[str, Any] = {"quadros": []}
        if self.ativo:
            carimbo = time.strftime("%Y%m%d_%H%M%S") + f"_{time.time_ns() % 1_000_000:06d}"
            self.pasta = Path(config.PASTA_DIAGNOSTICO) / carimbo
            self.pasta.mkdir(parents=True, exist_ok=True)

    def imagem(self, nome: str, imagem: np.ndarray) -> None:
        if self.ativo and self.pasta is not None and imagem is not None and imagem.size:
            cv2.imwrite(str(self.pasta / f"{nome}.jpg"), imagem)

    def evento(self, **dados: Any) -> None:
        self.dados.update(dados)

    def quadro(self, dados: dict[str, Any]) -> None:
        self.dados["quadros"].append(dados)

    def concluir(self) -> None:
        if self.ativo and self.pasta is not None:
            with (self.pasta / "resultado.json").open("w", encoding="utf-8") as arquivo:
                json.dump(self.dados, arquivo, ensure_ascii=False, indent=2, default=float)
