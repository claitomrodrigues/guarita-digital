"""Testes locais sem carregar YOLO, PaddleOCR ou abrir câmera."""

import numpy as np
import reconhecimento as rec
from qualidade import avaliar, selecionar_melhores


def testar_formatos() -> None:
    assert rec.modelo_da_placa("ABC1234") == "antiga"
    assert rec.modelo_da_placa("ABC1D23") == "mercosul"
    assert rec.modelo_da_placa("123ABCD") == "inválida"
    assert rec.formatar_placa("ABC1234") == "ABC-1234"
    assert ("ABC1234", 0) in rec.extrair_candidatos("ABC-1234", "antiga")
    assert ("ABC1D23", 0) in rec.extrair_candidatos("ABC1D23", "mercosul")


def testar_ambiguidades_contextuais() -> None:
    mercosul = rec.extrair_candidatos("ABC1123", "mercosul")
    antiga = rec.extrair_candidatos("ABC1123", "antiga")
    indefinida = rec.extrair_candidatos("ABC1123", "indefinida")
    assert ("ABC1I23", 1) in mercosul
    assert ("ABC1123", 0) not in mercosul
    assert ("ABC1123", 0) in antiga
    assert {placa for placa, _ in indefinida} >= {"ABC1123", "ABC1I23"}


def testar_tipo_visual() -> None:
    placa = np.full((120, 360, 3), 245, dtype=np.uint8)
    placa[:30] = (180, 70, 10)
    tipo, confianca = rec.detectar_tipo_visual(placa)
    assert tipo == "mercosul" and confianca > 0.5
    tipo, _ = rec.detectar_tipo_visual(np.full((120, 360, 3), 235, dtype=np.uint8))
    assert tipo == "antiga"


def testar_qualidade() -> None:
    boa = np.zeros((220, 700, 3), dtype=np.uint8)
    boa[:, ::20] = 255
    boa[::20, :] = 255
    ruim = np.full((220, 700, 3), 125, dtype=np.uint8)
    assert avaliar(boa).nitidez > avaliar(ruim).nitidez
    selecionadas = selecionar_melhores([ruim, boa])
    assert selecionadas[0][2] == 1


def testar_parser_paddle3() -> None:
    class Resultado:
        def __init__(self) -> None:
            self.json = {
                "res": {"rec_texts": ["ABC1D23"], "rec_scores": [0.93]},
            }

    assert rec.normalizar_resultado_paddle([Resultado()]) == [("ABC1D23", 0.93)]

    class ResultadoReconhecimento:
        def __init__(self) -> None:
            self.json = {"res": {"rec_text": "ABC1234", "rec_score": 0.89}}

    assert rec.normalizar_resultado_paddle([ResultadoReconhecimento()]) == [
        ("ABC1234", 0.89)
    ]


def testar_associacao_da_mesma_placa() -> None:
    assert rec.caixas_compativeis((100, 70, 300, 140), (112, 73, 315, 144), 900, 240)
    assert not rec.caixas_compativeis(
        (100, 70, 300, 140), (600, 70, 820, 145), 900, 240
    )


def main() -> None:
    testar_formatos()
    testar_ambiguidades_contextuais()
    testar_tipo_visual()
    testar_qualidade()
    testar_parser_paddle3()
    testar_associacao_da_mesma_placa()
    print("OK: formatos, ambiguidades, qualidade, associação e parser PaddleOCR.")


if __name__ == "__main__":
    main()
