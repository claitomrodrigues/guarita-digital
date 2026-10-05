import sys
from pathlib import Path

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent))

from reconhecimento import Reconhecedor, extrair_candidatos


def test_placa_antiga_nao_e_convertida_para_mercosul():
    candidatos = [placa for placa, _ in extrair_candidatos('ABC1234')]
    assert 'ABC1234' in candidatos
    assert 'ABC1Z34' not in candidatos


def test_obter_candidatos_ocr_pula_contraste_quando_original_ja_acha():
    rec = Reconhecedor.__new__(Reconhecedor)
    chamadas: list[str] = []

    def ler_ocr(versao):
        chamadas.append('original' if versao is original else 'contraste')
        if len(chamadas) == 1:
            return [('ABC1234', 0.96)]
        return []

    original = np.zeros((60, 180, 3), dtype=np.uint8)
    rec.ler_ocr = ler_ocr

    candidatos = rec.obter_candidatos_ocr(original, 'antiga')

    assert len(candidatos) == 1
    assert candidatos[0]['placa'] == 'ABC1234'
    assert chamadas == ['original']
