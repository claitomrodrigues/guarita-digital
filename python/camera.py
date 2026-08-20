"""Interface fluida com captura temporal ao redor do clique."""

from __future__ import annotations

import argparse
import sys
import time
from collections import deque
from concurrent.futures import Future, ThreadPoolExecutor
from dataclasses import dataclass, field

import cv2
import numpy as np

import config
from reconhecimento import Leitura, formatar_placa, inicializar_modelos, reconhecer_sequencia

AO_VIVO = "ao_vivo"
COLETANDO = "coletando"
PROCESSANDO = "processando"
RESULTADO = "resultado"
BOTAO = (0, 0, 0, 0)
CLICOU = False
LARGURA_CAPTURA, ALTURA_CAPTURA = 1280, 720
LARGURA_TELA, ALTURA_TELA = 1100, 620


@dataclass
class Estado:
    fase: str = AO_VIVO
    buffer: deque[np.ndarray] = field(default_factory=lambda: deque(maxlen=12))
    sequencia: list[np.ndarray] = field(default_factory=list)
    posteriores_restantes: int = 0
    futuro: Future[Leitura] | None = None
    leitura: Leitura | None = None
    inicio: float = 0.0
    duracao: float = 0.0
    mensagem: str = "Centralize a placa e clique em CAPTURAR"


def _tentar_camera(indice: int) -> cv2.VideoCapture:
    backends = (cv2.CAP_DSHOW, cv2.CAP_MSMF) if sys.platform == "win32" else (cv2.CAP_ANY,)
    for backend in backends:
        camera = cv2.VideoCapture(indice, backend)
        if not camera.isOpened():
            camera.release(); continue
        camera.set(cv2.CAP_PROP_FOURCC, cv2.VideoWriter_fourcc(*"MJPG"))
        camera.set(cv2.CAP_PROP_FRAME_WIDTH, LARGURA_CAPTURA)
        camera.set(cv2.CAP_PROP_FRAME_HEIGHT, ALTURA_CAPTURA)
        camera.set(cv2.CAP_PROP_FPS, 30)
        camera.set(cv2.CAP_PROP_BUFFERSIZE, 1)
        camera.set(cv2.CAP_PROP_AUTOFOCUS, 1)
        for _ in range(3):
            sucesso, quadro = camera.read()
        if sucesso and quadro is not None and quadro.size:
            return camera
        camera.release()
    return cv2.VideoCapture()


def abrir_camera(indice: int | None = None) -> tuple[cv2.VideoCapture, int]:
    for candidato in ([indice] if indice is not None else [1, 2, 3, 0]):
        camera = _tentar_camera(candidato)
        if camera.isOpened():
            return camera, candidato
    raise RuntimeError("Nenhuma câmera forneceu imagem. Teste --camera 0, 1, 2 ou 3.")


def ajustar_para_tela(frame: np.ndarray) -> np.ndarray:
    altura, largura = frame.shape[:2]
    escala = min(LARGURA_TELA / largura, ALTURA_TELA / altura, 1.0)
    if escala >= 1.0:
        return frame
    return cv2.resize(frame, (int(largura * escala), int(altura * escala)), interpolation=cv2.INTER_AREA)


def iniciar_coleta(estado: Estado) -> bool:
    """Congela os quadros anteriores e passa a aguardar os posteriores."""

    if estado.fase in (COLETANDO, PROCESSANDO) or not estado.buffer:
        return False
    anteriores = list(estado.buffer)[-(config.QUADROS_ANTES + 1):]
    estado.sequencia = [imagem.copy() for imagem in anteriores]
    estado.posteriores_restantes = config.QUADROS_DEPOIS
    estado.fase = COLETANDO
    estado.leitura = None
    estado.duracao = 0.0
    estado.mensagem = "Capturando os melhores quadros..."
    return True


def atualizar_coleta(estado: Estado, recorte: np.ndarray, executor: ThreadPoolExecutor) -> None:
    if estado.fase != COLETANDO:
        return
    estado.sequencia.append(recorte.copy())
    estado.posteriores_restantes -= 1
    if estado.posteriores_restantes > 0:
        return
    estado.inicio = time.monotonic()
    estado.fase = PROCESSANDO
    estado.mensagem = "Analisando qualidade, YOLO e PaddleOCR..."
    quadros = [imagem.copy() for imagem in estado.sequencia]
    estado.futuro = executor.submit(reconhecer_sequencia, quadros)


def receber_resultado(estado: Estado) -> None:
    if estado.fase != PROCESSANDO or estado.futuro is None or not estado.futuro.done():
        return
    estado.duracao = time.monotonic() - estado.inicio
    try:
        estado.leitura = estado.futuro.result()
    except Exception as erro:
        print(f"Erro no reconhecimento: {erro!r}", file=sys.stderr)
        estado.leitura = Leitura("", 0.0, "", "erro interno; consulte o terminal")
    estado.futuro = None
    estado.fase = RESULTADO
    if estado.leitura.placa:
        estado.mensagem = f"Confirmada em {estado.leitura.quadros_confirmados} quadro(s). Capture a próxima."
    else:
        estado.mensagem = estado.leitura.motivo or "Leitura rejeitada. Capture novamente."


def clique_mouse(evento: int, x: int, y: int, _flags: int, _dados) -> None:
    global CLICOU
    if evento == cv2.EVENT_LBUTTONDOWN:
        x1, y1, x2, y2 = BOTAO
        CLICOU = x1 <= x <= x2 and y1 <= y <= y2


def desenhar(frame: np.ndarray, guia: tuple[int, int, int, int], estado: Estado) -> np.ndarray:
    global BOTAO
    tela = frame.copy()
    x1, y1, x2, y2 = guia
    cv2.rectangle(tela, (x1, y1), (x2, y2), (0, 230, 0), 3)
    cv2.putText(tela, "GuaritaDigital - IFFar Campus SVS", (20, 38), cv2.FONT_HERSHEY_SIMPLEX, .78, (0, 255, 0), 2)
    cv2.putText(tela, estado.mensagem, (20, 75), cv2.FONT_HERSHEY_SIMPLEX, .55, (255, 255, 255), 2)
    if estado.leitura and estado.leitura.placa:
        cv2.putText(tela, formatar_placa(estado.leitura.placa), (20, 135), cv2.FONT_HERSHEY_SIMPLEX, 1.45, (0, 255, 0), 3)
        detalhe = f"{estado.leitura.modelo} | OCR {estado.leitura.confianca:.0f}% | {estado.leitura.quadros_confirmados} quadros"
        cv2.putText(tela, detalhe, (20, 170), cv2.FONT_HERSHEY_SIMPLEX, .52, (255, 255, 255), 1)
    elif estado.leitura and estado.leitura.motivo:
        cv2.putText(tela, estado.leitura.motivo, (20, 130), cv2.FONT_HERSHEY_SIMPLEX, .52, (0, 210, 255), 2)
    if estado.duracao:
        cv2.putText(tela, f"Tempo: {estado.duracao:.2f}s", (20, tela.shape[0] - 35), cv2.FONT_HERSHEY_SIMPLEX, .52, (255, 255, 255), 1)

    bx2, by2 = tela.shape[1] - 20, tela.shape[0] - 20
    bx1, by1 = bx2 - 330, by2 - 58
    BOTAO = (bx1, by1, bx2, by2)
    if estado.fase == COLETANDO:
        rotulo, cor = "CAPTURANDO...", (110, 90, 0)
    elif estado.fase == PROCESSANDO:
        rotulo, cor = "PROCESSANDO...", (120, 80, 0)
    elif estado.fase == RESULTADO:
        rotulo, cor = "NOVA CAPTURA", (0, 150, 0)
    else:
        rotulo, cor = "CAPTURAR", (0, 150, 0)
    cv2.rectangle(tela, (bx1, by1), (bx2, by2), cor, -1)
    cv2.rectangle(tela, (bx1, by1), (bx2, by2), (255, 255, 255), 2)
    cv2.putText(tela, rotulo, (bx1 + 20, by1 + 38), cv2.FONT_HERSHEY_SIMPLEX, .68, (255, 255, 255), 2)
    cv2.putText(tela, "Espaço/Enter: capturar | Q/Esc: sair", (20, tela.shape[0] - 68), cv2.FONT_HERSHEY_SIMPLEX, .50, (255, 255, 255), 1)
    return tela


def main(indice_camera: int | None = None) -> None:
    global CLICOU
    inicializar_modelos()
    camera, indice_aberto = abrir_camera(indice_camera)
    print(f"Câmera ativa: índice {indice_aberto}")
    estado = Estado()
    nome = "GuaritaDigital"
    cv2.namedWindow(nome, cv2.WINDOW_AUTOSIZE)
    cv2.setMouseCallback(nome, clique_mouse)
    try:
        with ThreadPoolExecutor(max_workers=1) as executor:
            while True:
                sucesso, frame = camera.read()
                if not sucesso or frame is None:
                    raise RuntimeError("A câmera deixou de fornecer imagens.")
                frame = ajustar_para_tela(frame)
                h, w = frame.shape[:2]
                guia = (int(w * .13), int(h * .30), int(w * .87), int(h * .70))
                x1, y1, x2, y2 = guia
                recorte = frame[y1:y2, x1:x2].copy()
                estado.buffer.append(recorte)
                atualizar_coleta(estado, recorte, executor)
                receber_resultado(estado)

                cv2.imshow(nome, desenhar(frame, guia, estado))
                tecla = cv2.waitKey(1) & 0xFF
                pediu = CLICOU or tecla in (13, 32)
                CLICOU = False
                if pediu:
                    iniciar_coleta(estado)
                if tecla in (ord("q"), ord("Q"), 27):
                    break
    finally:
        camera.release()
        cv2.destroyAllWindows()


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Leitor de placas do GuaritaDigital")
    parser.add_argument("--camera", type=int, default=None, metavar="INDICE")
    argumentos = parser.parse_args()
    try:
        main(argumentos.camera)
    except Exception as erro:
        print(f"Erro: {erro}", file=sys.stderr)
        raise SystemExit(1)
