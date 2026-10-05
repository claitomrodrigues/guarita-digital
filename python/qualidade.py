"""Avaliação de qualidade dos quadros antes de executar redes neurais."""

from __future__ import annotations

from dataclasses import dataclass

import config
import cv2
import numpy as np


@dataclass(frozen=True)
class Qualidade:
    nitidez: float
    brilho: float
    contraste: float
    estourado: float
    nota: float
    aceitavel: bool
    motivo: str = ""


def avaliar(imagem: np.ndarray) -> Qualidade:
    if imagem is None or imagem.size == 0:
        return Qualidade(0, 0, 0, 1, 0, False, "captura vazia")
    cinza = cv2.cvtColor(imagem, cv2.COLOR_BGR2GRAY)
    nitidez = float(cv2.Laplacian(cinza, cv2.CV_64F).var())
    brilho = float(cinza.mean())
    contraste = float(cinza.std())
    estourado = float(np.mean((cinza <= 5) | (cinza >= 250)))

    motivos: list[str] = []
    if nitidez < config.NITIDEZ_MINIMA:
        motivos.append("imagem desfocada")
    if brilho < config.BRILHO_MINIMO:
        motivos.append("imagem muito escura")
    elif brilho > config.BRILHO_MAXIMO:
        motivos.append("imagem muito clara")
    if contraste < config.CONTRASTE_MINIMO:
        motivos.append("baixo contraste")
    if estourado > 0.38:
        motivos.append("reflexo ou sombras excessivas")

    nota_nitidez = min(nitidez / 180.0, 1.0)
    nota_brilho = max(0.0, 1.0 - abs(brilho - 130.0) / 130.0)
    nota_contraste = min(contraste / 60.0, 1.0)
    nota = 0.55 * nota_nitidez + 0.20 * nota_brilho + 0.25 * nota_contraste
    return Qualidade(
        nitidez,
        brilho,
        contraste,
        estourado,
        nota,
        not motivos,
        "; ".join(motivos),
    )


def selecionar_melhores(
    imagens: list[np.ndarray],
) -> list[tuple[np.ndarray, Qualidade, int]]:
    avaliadas = [
        (imagem, avaliar(imagem), indice) for indice, imagem in enumerate(imagens)
    ]
    aceitaveis = [item for item in avaliadas if item[1].aceitavel]
    base = aceitaveis if aceitaveis else avaliadas
    base.sort(key=lambda item: item[1].nota, reverse=True)
    return base[: config.MELHORES_QUADROS]
