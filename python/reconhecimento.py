"""Pipeline robusto de placas: qua0lidade, YOLO, geometria, OCR e consenso."""

from __future__ import annotations

import re
import threading
import time
from collections import Counter, defaultdict
from dataclasses import dataclass
from typing import Any

import config
import cv2
import numpy as np
from diagnostico import Diagnostico
from qualidade import selecionar_melhores

PADRAO_ANTIGO = re.compile(r"^[A-Z]{3}[0-9]{4}$")
PADRAO_MERCOSUL = re.compile(r"^[A-Z]{3}[0-9][A-Z][0-9]{2}$")
MASCARAS = {"antiga": "LLLNNNN", "mercosul": "LLLNLNN"}

DIGITO_PARA_LETRA = {
    "0": "O",
    "1": "I",
    "2": "Z",
    "4": "A",
    "5": "S",
    "6": "G",
    "7": "T",
    "8": "B",
}
LETRA_PARA_DIGITO = {
    "O": "0",
    "Q": "0",
    "D": "0",
    "I": "1",
    "L": "1",
    "Z": "2",
    "A": "4",
    "S": "5",
    "G": "6",
    "T": "7",
    "B": "8",
}


@dataclass(frozen=True)
class Leitura:
    placa: str
    confianca: float
    modelo: str
    motivo: str = ""
    quadros_confirmados: int = 0
    confianca_yolo: float = 0.0


@dataclass(frozen=True)
class Deteccao:
    recorte: np.ndarray
    caixa: tuple[int, int, int, int]
    confianca: float


@dataclass(frozen=True)
class Evidencia:
    placa: str
    quadro: int
    confianca_ocr: float
    confianca_yolo: float
    qualidade: float
    tipo_visual: str
    confianca_tipo: float
    correcoes: int

    @property
    def peso(self) -> float:
        penalidade = 0.82 if self.correcoes else 1.0
        return self.confianca_ocr * (0.70 + 0.30 * self.qualidade) * penalidade


def limpar_texto(texto: str) -> str:
    return re.sub(r"[^A-Z0-9]", "", str(texto).upper())


def modelo_da_placa(placa: str) -> str:
    if PADRAO_ANTIGO.fullmatch(placa):
        return "antiga"
    if PADRAO_MERCOSUL.fullmatch(placa):
        return "mercosul"
    return "inválida"


def formatar_placa(placa: str) -> str:
    return f"{placa[:3]}-{placa[3:]}" if modelo_da_placa(placa) == "antiga" else placa


def converter_por_mascara(texto: str, mascara: str) -> tuple[str, int] | None:
    if len(texto) != 7:
        return None
    saida: list[str] = []
    correcoes = 0
    for caractere, esperado in zip(texto, mascara):
        if esperado == "L":
            if caractere.isalpha():
                saida.append(caractere)
            elif caractere in DIGITO_PARA_LETRA:
                saida.append(DIGITO_PARA_LETRA[caractere])
                correcoes += 1
            else:
                return None
        else:
            if caractere.isdigit():
                saida.append(caractere)
            elif caractere in LETRA_PARA_DIGITO:
                saida.append(LETRA_PARA_DIGITO[caractere])
                correcoes += 1
            else:
                return None
        if correcoes > 1:
            return None
    return "".join(saida), correcoes


def extrair_candidatos(
    texto: str, tipo_visual: str = "indefinida"
) -> list[tuple[str, int]]:
    """Gera alternativas sem decidir prematuramente entre letra e número."""

    limpo = limpar_texto(texto)
    encontrados: dict[str, int] = {}
    for inicio in range(max(0, len(limpo) - 6)):
        trecho = limpo[inicio : inicio + 7]
        if len(trecho) != 7:
            continue

        if PADRAO_ANTIGO.fullmatch(trecho):
            tipos = ("antiga",)
        elif PADRAO_MERCOSUL.fullmatch(trecho):
            tipos = ("mercosul",)
        else:
            tipos = (tipo_visual,) if tipo_visual in MASCARAS else ("antiga", "mercosul")

        for tipo in tipos:
            convertido = converter_por_mascara(trecho, MASCARAS[tipo])
            if convertido is None:
                continue
            placa, custo = convertido
            if modelo_da_placa(placa) == tipo and (
                placa not in encontrados or custo < encontrados[placa]
            ):
                encontrados[placa] = custo
    return sorted(encontrados.items(), key=lambda item: (item[1], item[0]))


def _ordenar_pontos(pontos: np.ndarray) -> np.ndarray:
    pontos = np.asarray(pontos, dtype=np.float32)
    soma = pontos.sum(axis=1)
    diferenca = np.diff(pontos, axis=1).ravel()
    return np.array(
        [
            pontos[np.argmin(soma)],
            pontos[np.argmin(diferenca)],
            pontos[np.argmax(soma)],
            pontos[np.argmax(diferenca)],
        ],
        dtype=np.float32,
    )


def corrigir_perspectiva(recorte: np.ndarray) -> np.ndarray:
    """Retifica um contorno quadrilateral; preserva o original se incerto."""

    cinza = cv2.cvtColor(recorte, cv2.COLOR_BGR2GRAY)
    bordas = cv2.Canny(cv2.GaussianBlur(cinza, (5, 5), 0), 45, 150)
    contornos, _ = cv2.findContours(bordas, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
    area_total = recorte.shape[0] * recorte.shape[1]
    for contorno in sorted(contornos, key=cv2.contourArea, reverse=True)[:10]:
        perimetro = cv2.arcLength(contorno, True)
        aproximado = cv2.approxPolyDP(contorno, 0.025 * perimetro, True)
        if len(aproximado) != 4 or cv2.contourArea(aproximado) < area_total * 0.35:
            continue
        se, sd, id_, ie = _ordenar_pontos(aproximado.reshape(4, 2))
        largura = int(max(np.linalg.norm(sd - se), np.linalg.norm(id_ - ie)))
        altura = int(max(np.linalg.norm(ie - se), np.linalg.norm(id_ - sd)))
        if altura < 15 or not 1.8 <= largura / altura <= 6.5:
            continue
        destino = np.float32(
            [[0, 0], [largura - 1, 0], [largura - 1, altura - 1], [0, altura - 1]]
        )
        matriz = cv2.getPerspectiveTransform(np.float32([se, sd, id_, ie]), destino)
        return cv2.warpPerspective(
            recorte, matriz, (largura, altura), flags=cv2.INTER_CUBIC
        )
    return recorte


def detectar_tipo_visual(recorte: np.ndarray) -> tuple[str, float]:
    """Classifica Mercosul somente com evidência positiva de faixa azul."""

    if recorte.shape[0] < 25 or recorte.shape[1] < 70:
        return "indefinida", 0.0
    topo = recorte[: max(8, int(recorte.shape[0] * 0.32))]
    hsv = cv2.cvtColor(topo, cv2.COLOR_BGR2HSV)
    azul = cv2.inRange(hsv, np.array([85, 45, 35]), np.array([140, 255, 255]))
    proporcao = float(np.count_nonzero(azul) / max(azul.size, 1))
    cobertura = float(np.mean(np.count_nonzero(azul, axis=0) >= 2))
    evidencia = 0.55 * min(proporcao / 0.10, 1.0) + 0.45 * min(cobertura / 0.45, 1.0)
    if proporcao >= 0.035 and cobertura >= 0.18:
        return "mercosul", evidencia
    if proporcao <= 0.008:
        return "antiga", min(1.0, 1.0 - proporcao / 0.008)
    return "indefinida", evidencia


def preparar_recorte(recorte: np.ndarray) -> np.ndarray:
    altura, largura = recorte.shape[:2]
    if altura < 110:
        escala = min(4.0, 110.0 / max(altura, 1))
        recorte = cv2.resize(
            recorte,
            (int(largura * escala), int(altura * escala)),
            interpolation=cv2.INTER_CUBIC,
        )
    return cv2.copyMakeBorder(recorte, 10, 10, 14, 14, cv2.BORDER_REPLICATE)


def variante_contraste(recorte: np.ndarray) -> np.ndarray:
    lab = cv2.cvtColor(recorte, cv2.COLOR_BGR2LAB)
    l, a, b = cv2.split(lab)
    l = cv2.createCLAHE(2.0, (8, 8)).apply(l)
    return cv2.cvtColor(cv2.merge((l, a, b)), cv2.COLOR_LAB2BGR)


def caixas_compativeis(
    primeira: tuple[int, int, int, int],
    segunda: tuple[int, int, int, int],
    largura: int,
    altura: int,
) -> bool:
    """Associa a mesma placa entre quadros próximos sem misturar veículos."""

    def centro(caixa: tuple[int, int, int, int]) -> tuple[float, float]:
        return ((caixa[0] + caixa[2]) / 2, (caixa[1] + caixa[3]) / 2)

    c1, c2 = centro(primeira), centro(segunda)
    distancia = np.hypot(
        (c1[0] - c2[0]) / max(largura, 1), (c1[1] - c2[1]) / max(altura, 1)
    )
    area1 = max(1, (primeira[2] - primeira[0]) * (primeira[3] - primeira[1]))
    area2 = max(1, (segunda[2] - segunda[0]) * (segunda[3] - segunda[1]))
    razao_area = min(area1, area2) / max(area1, area2)
    return distancia <= 0.18 and razao_area >= 0.38


def _procurar_chave(objeto: Any, chave: str) -> Any:
    if isinstance(objeto, dict):
        if chave in objeto:
            return objeto[chave]
        for valor in objeto.values():
            achado = _procurar_chave(valor, chave)
            if achado is not None:
                return achado
    elif isinstance(objeto, (list, tuple)):
        for valor in objeto:
            achado = _procurar_chave(valor, chave)
            if achado is not None:
                return achado
    return None


def normalizar_resultado_paddle(resultado: Any) -> list[tuple[str, float]]:
    leituras: list[tuple[str, float]] = []
    itens = resultado if isinstance(resultado, (list, tuple)) else [resultado]
    for item in itens:
        dados = getattr(item, "json", item)
        texto_unico = _procurar_chave(dados, "rec_text")
        nota_unica = _procurar_chave(dados, "rec_score")
        if texto_unico is not None:
            leituras.append((str(texto_unico), float(nota_unica or 0.0)))
            continue
        textos = _procurar_chave(dados, "rec_texts")
        notas = _procurar_chave(dados, "rec_scores")
        if textos is not None:
            notas = list(notas) if notas is not None else [0.0] * len(textos)
            leituras.extend(
                (str(texto), float(notas[i])) for i, texto in enumerate(textos)
            )
            continue
        if isinstance(item, (list, tuple)):
            for linha in item:
                if (
                    isinstance(linha, (list, tuple))
                    and len(linha) >= 2
                    and isinstance(linha[1], (list, tuple))
                    and len(linha[1]) >= 2
                ):
                    leituras.append((str(linha[1][0]), float(linha[1][1])))
    return leituras


def priorizar_candidatos(grupos: dict[str, list[Evidencia]]) -> list[tuple[str, list[Evidencia]]]:
    def chave(item: tuple[str, list[Evidencia]]) -> tuple[float, float, float, float]:
        placa, provas = item
        quadros = len({e.quadro for e in provas})
        peso_total = sum(e.peso for e in provas)
        tipo_esperado = modelo_da_placa(placa)
        consistencia_visual = sum(
            1 for e in provas if e.tipo_visual == tipo_esperado
        ) / max(len(provas), 1)
        votos_tipo = Counter(e.tipo_visual for e in provas)
        dominante_tipo = votos_tipo.most_common(1)[0][0] if votos_tipo else "indefinida"
        bonus_tipo = 1.0 if tipo_esperado == dominante_tipo else 0.0
        return (quadros, peso_total, consistencia_visual, bonus_tipo)

    return sorted(grupos.items(), key=chave, reverse=True)


class Reconhecedor:
    def __init__(self) -> None:
        try:
            from huggingface_hub import hf_hub_download
            from paddleocr import TextRecognition
            from ultralytics import YOLO
        except ImportError as erro:
            modulo = getattr(erro, "name", None) or "desconhecido"
            raise RuntimeError(
                f"Dependência ausente ({modulo}). Execute instalar_windows.bat"
            ) from erro
        print("Carregando YOLO...")
        if config.CAMINHO_MODELO:
            peso_local = config.PASTA_PYTHON / config.CAMINHO_MODELO
            if not peso_local.is_file():
                peso_local = config.PASTA_PYTHON.parent / config.CAMINHO_MODELO
            if not peso_local.is_file():
                raise RuntimeError(
                    f"Modelo YOLO local não encontrado: {config.CAMINHO_MODELO}"
                )
            peso = str(peso_local.resolve())
        else:
            try:
                peso = hf_hub_download(
                    repo_id=config.REPOSITORIO_MODELO,
                    filename=config.ARQUIVO_MODELO,
                )
            except Exception as erro:
                raise RuntimeError(
                    "Não foi possível obter o modelo YOLO. Conecte-se à internet "
                    "na primeira execução ou configure GUARITA_MODELO_YOLO."
                ) from erro
        self.detector = YOLO(peso)
        print("Carregando PaddleOCR...")
        # O YOLO já entrega uma linha recortada; executar outra detecção textual
        # seria redundante. Este módulo reconhece diretamente letras e números.
        self.ocr = TextRecognition(
            model_name=config.MODELO_OCR,
            device=config.DISPOSITIVO,
        )
        self.ultimas: dict[str, float] = {}
        print("Modelos prontos.")

    def detectar(self, imagem: np.ndarray) -> list[Deteccao]:
        resultados = self.detector.predict(
            source=imagem,
            imgsz=640,
            conf=config.CONFIANCA_YOLO_MINIMA,
            max_det=3,
            device=config.DISPOSITIVO,
            verbose=False,
        )
        altura, largura = imagem.shape[:2]
        deteccoes: list[Deteccao] = []
        for resultado in resultados:
            boxes = getattr(resultado, "boxes", None)
            if boxes is None:
                continue
            for caixa in boxes:
                x1, y1, x2, y2 = (int(v) for v in caixa.xyxy[0].tolist())
                confianca = float(caixa.conf[0])
                margem_x, margem_y = (
                    max(5, int((x2 - x1) * 0.08)),
                    max(4, int((y2 - y1) * 0.14)),
                )
                x1, y1 = max(0, x1 - margem_x), max(0, y1 - margem_y)
                x2, y2 = min(largura, x2 + margem_x), min(altura, y2 + margem_y)
                recorte = imagem[y1:y2, x1:x2]
                if recorte.size and recorte.shape[0] >= config.ALTURA_MINIMA_PLACA:
                    deteccoes.append(
                        Deteccao(recorte.copy(), (x1, y1, x2, y2), confianca)
                    )
        # Maior confiança, área e proximidade do centro são preferidas.
        cx, cy = largura / 2, altura / 2
        deteccoes.sort(
            key=lambda d: (
                d.confianca,
                (d.caixa[2] - d.caixa[0]) * (d.caixa[3] - d.caixa[1]),
                -abs((d.caixa[0] + d.caixa[2]) / 2 - cx)
                - abs((d.caixa[1] + d.caixa[3]) / 2 - cy),
            ),
            reverse=True,
        )
        return deteccoes

    def ler_ocr(self, recorte: np.ndarray) -> list[tuple[str, float]]:
        retorno = self.ocr.predict(input=recorte, batch_size=1)
        return normalizar_resultado_paddle(retorno)

    def obter_candidatos_ocr(
        self, recorte: np.ndarray, tipo: str
    ) -> list[dict[str, Any]]:
        """Executa OCR em ordem de custo crescente e aborta no primeiro sucesso útil."""

        candidatos: list[dict[str, Any]] = []
        for nome, versao in (("original", recorte), ("contraste", variante_contraste(recorte))):
            leituras = self.ler_ocr(versao)
            for texto, confianca_ocr in leituras:
                if confianca_ocr < config.CONFIANCA_OCR_MINIMA:
                    continue
                for placa, correcoes in extrair_candidatos(texto, tipo):
                    minimo = (
                        config.CONFIANCA_OCR_CORRIGIDA
                        if correcoes
                        else config.CONFIANCA_OCR_MINIMA
                    )
                    if confianca_ocr < minimo:
                        continue
                    candidatos.append(
                        {
                            "texto": texto,
                            "placa": placa,
                            "ocr": confianca_ocr,
                            "tipo": tipo,
                            "correcoes": correcoes,
                            "tratamento": nome,
                        }
                    )
            if candidatos:
                break
        return candidatos

    def processar_sequencia(
        self,
        imagens: list[np.ndarray],
        confirmacoes_minimas: int | None = None,
    ) -> Leitura:
        """Processa fotos reais e permite calibrar o consenso por origem.

        A câmera contínua continua exigindo o número configurado de quadros.
        A Home envia uma única fotografia e, nesse caso, usa uma confirmação.
        """

        inicio = time.monotonic()
        diagnostico = Diagnostico()
        confirmacoes_necessarias = max(
            1,
            int(
                config.QUADROS_PARA_CONFIRMAR
                if confirmacoes_minimas is None
                else confirmacoes_minimas
            ),
        )
        selecionadas = selecionar_melhores(imagens)
        if not selecionadas:
            return Leitura("", 0, "", "nenhum quadro capturado")
        if not any(q.aceitavel for _, q, _ in selecionadas):
            motivo = selecionadas[0][1].motivo or "qualidade insuficiente"
            diagnostico.evento(resultado="rejeitada", motivo=motivo)
            diagnostico.concluir()
            return Leitura("", 0, "", motivo)

        evidencias: list[Evidencia] = []
        encontrou_placa = False
        caixa_referencia: tuple[int, int, int, int] | None = None
        for ordem, (imagem, qualidade, indice_original) in enumerate(selecionadas):
            diagnostico.imagem(f"quadro_{ordem:02d}", imagem)
            deteccoes = self.detectar(imagem)
            dados_quadro: dict[str, Any] = {
                "indice": indice_original,
                "nitidez": qualidade.nitidez,
                "brilho": qualidade.brilho,
                "qualidade": qualidade.nota,
                "deteccoes": len(deteccoes),
                "leituras": [],
            }
            if not deteccoes:
                diagnostico.quadro(dados_quadro)
                continue
            if caixa_referencia is not None:
                compativeis = [
                    d
                    for d in deteccoes
                    if caixas_compativeis(
                        caixa_referencia, d.caixa, imagem.shape[1], imagem.shape[0]
                    )
                ]
                if not compativeis:
                    dados_quadro["ignorado"] = "detecção pertence a outro veículo"
                    diagnostico.quadro(dados_quadro)
                    continue
                deteccoes = compativeis
            encontrou_placa = True
            deteccao = deteccoes[0]
            if caixa_referencia is None:
                caixa_referencia = deteccao.caixa
            diagnostico.imagem(f"recorte_{ordem:02d}", deteccao.recorte)
            geometrico = corrigir_perspectiva(deteccao.recorte)
            tipo, confianca_tipo = detectar_tipo_visual(geometrico)
            corrigido = preparar_recorte(geometrico)
            diagnostico.imagem(f"corrigido_{ordem:02d}", corrigido)

            candidatos_ocr = self.obter_candidatos_ocr(corrigido, tipo)
            candidatos_no_quadro: list[Evidencia] = []
            for item in candidatos_ocr:
                texto = item["texto"]
                confianca_ocr = item["ocr"]
                placa = item["placa"]
                correcoes = item["correcoes"]
                evidencia = Evidencia(
                    placa,
                    indice_original,
                    confianca_ocr,
                    deteccao.confianca,
                    qualidade.nota,
                    tipo,
                    confianca_tipo,
                    correcoes,
                )
                candidatos_no_quadro.append(evidencia)
                dados_quadro["leituras"].append(
                    {
                        "tratamento": item["tratamento"],
                        "texto": texto,
                        "placa": placa,
                        "ocr": confianca_ocr,
                        "yolo": deteccao.confianca,
                        "tipo": tipo,
                        "correcoes": correcoes,
                    }
                )
            # Uma placa conta no máximo uma vez por quadro.
            melhor_por_placa: dict[str, Evidencia] = {}
            for evidencia in candidatos_no_quadro:
                if (
                    evidencia.placa not in melhor_por_placa
                    or evidencia.peso > melhor_por_placa[evidencia.placa].peso
                ):
                    melhor_por_placa[evidencia.placa] = evidencia
            evidencias.extend(melhor_por_placa.values())
            diagnostico.quadro(dados_quadro)

        if not encontrou_placa:
            diagnostico.evento(
                resultado="rejeitada", motivo="YOLO não encontrou uma placa"
            )
            diagnostico.concluir()
            return Leitura("", 0, "", "YOLO não encontrou uma placa")
        if not evidencias:
            diagnostico.evento(resultado="rejeitada", motivo="OCR sem formato válido")
            diagnostico.concluir()
            return Leitura("", 0, "", "PaddleOCR não confirmou sete caracteres")

        grupos: dict[str, list[Evidencia]] = defaultdict(list)
        for evidencia in evidencias:
            grupos[evidencia.placa].append(evidencia)
        ranking = priorizar_candidatos(grupos)
        placa, provas = ranking[0]
        quadros = len({e.quadro for e in provas})
        pontuacao = sum(e.peso for e in provas) / len(provas)
        confianca_yolo = sum(e.confianca_yolo for e in provas) / len(provas)
        segundo = ranking[1] if len(ranking) > 1 else None
        if segundo:
            pontos_segundo = sum(e.peso for e in segundo[1]) / len(segundo[1])
            quadros_segundo = len({e.quadro for e in segundo[1]})
            if (
                quadros_segundo == quadros
                and pontuacao - pontos_segundo < config.MARGEM_EMPATE
            ):
                diagnostico.evento(
                    resultado="rejeitada",
                    motivo="leituras ambíguas",
                    candidatos=[placa, segundo[0]],
                )
                diagnostico.concluir()
                return Leitura(
                    "", pontuacao * 100, "", "leitura ambígua; capture novamente"
                )
        if quadros < confirmacoes_necessarias:
            diagnostico.evento(
                resultado="rejeitada",
                motivo="sem consenso",
                placa=placa,
                quadros=quadros,
            )
            diagnostico.concluir()
            return Leitura(
                "",
                pontuacao * 100,
                "",
                f"placa confirmada em {quadros} de {confirmacoes_necessarias} quadros",
                quadros,
                confianca_yolo,
            )

        agora = time.monotonic()
        ultima = self.ultimas.get(placa, 0.0)
        if agora - ultima < config.INTERVALO_DUPLICIDADE_SEGUNDOS:
            diagnostico.evento(resultado="duplicada", placa=placa)
            diagnostico.concluir()
            return Leitura(
                placa,
                pontuacao * 100,
                modelo_da_placa(placa),
                "veículo já reconhecido recentemente",
                quadros,
                confianca_yolo,
            )
        self.ultimas[placa] = agora
        diagnostico.evento(
            resultado="aceita",
            placa=placa,
            quadros=quadros,
            confianca=pontuacao,
            tempo=time.monotonic() - inicio,
        )
        diagnostico.concluir()
        return Leitura(
            placa, pontuacao * 100, modelo_da_placa(placa), "", quadros, confianca_yolo
        )


_INSTANCIA: Reconhecedor | None = None
_TRAVA = threading.Lock()


def inicializar_modelos() -> Reconhecedor:
    global _INSTANCIA
    if _INSTANCIA is None:
        with _TRAVA:
            if _INSTANCIA is None:
                _INSTANCIA = Reconhecedor()
    return _INSTANCIA


def reconhecer_sequencia(imagens: list[np.ndarray]) -> Leitura:
    return inicializar_modelos().processar_sequencia(imagens)


def reconhecer_placa(imagem: np.ndarray) -> Leitura:
    """Reconhece uma fotografia única recebida da Home do Laravel.

    Uma foto vale como uma evidência. Ela não é duplicada artificialmente para
    simular consenso entre quadros diferentes.
    """

    return inicializar_modelos().processar_sequencia(
        [imagem],
        confirmacoes_minimas=1,
    )
