"""API local de reconhecimento de placas do Guarita Digital.

Execute com o Python 3.11 da .venv. Configure GUARITA_API_TOKEN antes
 de expor o serviço por um túnel HTTPS.
"""
from __future__ import annotations

import hmac
import os
import threading
from contextlib import redirect_stdout
from fastapi import FastAPI, File, Header, HTTPException, UploadFile

app = FastAPI(title="Guarita Digital - API OCR", docs_url=None, redoc_url=None)
_LIMITE_BYTES = 8 * 1024 * 1024
_TRAVA_RECONHECIMENTO = threading.Lock()


def _autenticar(authorization: str | None) -> None:
    token = os.environ.get("GUARITA_API_TOKEN", "").strip()
    if not token:
        raise HTTPException(status_code=503, detail="Token da API não configurado")
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Não autorizado")
    recebido = authorization[7:]
    if not hmac.compare_digest(recebido, token):
        raise HTTPException(status_code=401, detail="Não autorizado")


@app.get("/saude")
def saude():
    return {"status": "online"}


@app.post("/reconhecer")
def reconhecer(arquivo: UploadFile = File(...), authorization: str | None = Header(default=None)):
    _autenticar(authorization)
    if arquivo.content_type not in {"image/jpeg", "image/png", "image/webp"}:
        raise HTTPException(status_code=415, detail="Envie JPEG, PNG ou WebP")
    conteudo = arquivo.file.read(_LIMITE_BYTES + 1)
    if len(conteudo) > _LIMITE_BYTES:
        raise HTTPException(status_code=413, detail="Imagem excede 8 MB")
    if not conteudo:
        raise HTTPException(status_code=400, detail="Imagem vazia")

    try:
        import cv2
        import numpy as np
        from reconhecimento import reconhecer_placa
        imagem = cv2.imdecode(np.frombuffer(conteudo, dtype=np.uint8), cv2.IMREAD_COLOR)
        if imagem is None:
            raise HTTPException(status_code=400, detail="Imagem inválida")
        if imagem.shape[0] * imagem.shape[1] > 20_000_000:
            raise HTTPException(status_code=413, detail="Resolução de imagem excessiva")
        with _TRAVA_RECONHECIMENTO:
            # O carregamento dos modelos é reutilizado entre requisições.
            leitura = reconhecer_placa(imagem)
    except HTTPException:
        raise
    except Exception as erro:
        # Detalhes técnicos ficam no console, não na resposta pública.
        print(f"Falha interna do OCR: {erro}", flush=True)
        raise HTTPException(status_code=503, detail="Reconhecimento temporariamente indisponível") from erro

    if not leitura.placa:
        return {"placa": None, "motivo": leitura.motivo or "Nenhuma placa reconhecida"}
    return {
        "placa": leitura.placa,
        "confianca": leitura.confianca,
        "confianca_yolo": leitura.confianca_yolo,
        "modelo": leitura.modelo,
        "quadros_confirmados": leitura.quadros_confirmados,
    }
