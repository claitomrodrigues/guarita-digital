# Backend do Guarita Digital

## Fluxo principal

1. `camera.py` captura uma sequência de quadros.
2. YOLO localiza a placa e PaddleOCR reconhece os caracteres.
3. O Python envia a leitura confirmada para `POST /api/v1/camera/reconhecimentos`.
4. O Laravel autentica a câmera, valida a captura e consulta o veículo.
5. Veículos autorizados geram acesso autorizado; placas desconhecidas ou bloqueadas geram triagem.

O Laravel não executa o OCR. Isso evita duplicação de processamento e permite instalar a câmera em outro computador.

## Autenticação

- Site: sessão Laravel e proteção CSRF.
- Câmera: `Authorization: Bearer TOKEN`, com um token diferente por ponto de acesso.
- O token é armazenado somente como SHA-256 e exibido uma única vez quando gerado.

## API da câmera

### Reconhecimento

`POST /api/v1/camera/reconhecimentos`

Campos JSON ou `multipart/form-data`:

```json
{
  "capture_id": "ENTRADA-20260821143520-a1b2c3d4",
  "placa": "ABC1D23",
  "confianca_ocr": 0.93,
  "confianca_yolo": 0.88,
  "modelo_placa": "mercosul",
  "quadros_confirmados": 3,
  "capturado_em": "2026-08-21T14:35:20-03:00",
  "versao_camera": "2.0.0"
}
```

`capture_id` torna a operação idempotente: reenviar a mesma captura devolve o registro existente.

### Heartbeat

`POST /api/v1/camera/heartbeat`

Atualiza a última comunicação e permite indicar câmeras online no painel.

## Rotas autenticadas do site

- `POST /api/login`, `POST /api/logout`, `GET /api/me`;
- CRUD de `/api/pessoas` e `/api/veiculos`;
- consulta e registro manual em `/api/acessos`;
- `/api/triagens` e `PATCH /api/triagens/{id}/concluir`;
- CRUD de `/api/pontos-acesso` e geração do token da câmera;
- CRUD administrativo de `/api/usuarios`;
- `GET /api/dashboard`;
- `GET /api/relatorios/acessos.csv`;
- `GET /api/auditoria`.

## Regras essenciais

- placas são armazenadas em maiúsculas e sem hífen;
- cada ponto deve indicar entrada, saída ou ambos;
- `capture_id` não pode ser processado duas vezes;
- placas desconhecidas e bloqueadas criam uma triagem pendente;
- somente uma triagem pendente é criada por acesso;
- uma triagem autorizada altera o acesso para liberação manual;
- toda liberação manual registra o usuário responsável;
- imagens ficam no disco privado e são acessadas apenas por rota autorizada;
- acessos e triagens não são excluídos pelo painel.

## Segurança de arquivos

Não versionar nem compartilhar:

- `.env`;
- `credenciais.txt`;
- `vendor/`;
- `python/.venv/`;
- `database/database.sqlite`;
- capturas e diagnósticos.

## Frontend

O painel está disponível em `/sistema` e utiliza sessão Laravel com CSRF. As telas consomem os mesmos endpoints documentados neste arquivo e respeitam as Policies do backend. Recursos administrativos são ocultados para vigilantes e continuam protegidos no servidor.

O frontend inclui dashboard, movimentações, triagens, pessoas, veículos, pontos de acesso, usuários, relatórios CSV, auditoria, configurações e recuperação de senha.

## Verificação

```powershell
composer install
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
python python\testar.py
```
