import sys
from pathlib import Path

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent))

from reconhecimento import Reconhecedor, extrair_candidatos

candidatos = [placa for placa, _ in extrair_candidatos('ABC1234')]
assert 'ABC1234' in candidatos
assert 'ABC1Z34' not in candidatos

rec = Reconhecedor.__new__(Reconhecedor)
chamadas = []
original = np.zeros((60, 180, 3), dtype=np.uint8)

def ler_ocr(versao):
    chamadas.append('original' if versao is original else 'contraste')
    if len(chamadas) == 1:
        return [('ABC1234', 0.96)]
    return []

rec.ler_ocr = ler_ocr
resultado = rec.obter_candidatos_ocr(original, 'antiga')
assert len(resultado) == 1, resultado
assert resultado[0]['placa'] == 'ABC1234'
assert chamadas == ['original'], chamadas
print('OK: validacao de otimizacao passou')
