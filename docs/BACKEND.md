# Backend do Guarita Digital

## Arquitetura

```text
Navegador/câmera
      ↓ imagem
Laravel Controller
      ↓
ReconhecimentoPlacaService
      ↓ processo externo
python/placa.py
      ↓
OpenCV + Tesseract OCR
      ↓ placa normalizada
AcessoService
      ↓
MySQL
```

O Laravel concentra autenticação, autorização, validação, cadastros, histórico e auditoria. O Python tem uma única responsabilidade: reconhecer a placa e imprimir um valor válido, como `FJB4E12` ou `CDU-9598`.

## Organização do diretório `app`

- `Enums`: valores permitidos e rótulos para o frontend;
- `Models`: entidades Eloquent, casts, relacionamentos e scopes;
- `Policies`: autorização independente das rotas;
- `Http/Requests`: validação e normalização das entradas;
- `Http/Resources`: formato estável das respostas JSON;
- `Services`: regras de acesso, auditoria e integração OCR;
- `Rules`: validações reutilizáveis de CPF e placa;
- `Support`: normalização e formatação de CPF/placa;
- `Observers`: auditoria automática dos cadastros.

## Banco de dados

Tabelas de domínio:

- `users`: duas contas, administrador e segurança;
- `pessoas`: proprietários e vínculos institucionais;
- `veiculos`: veículos e regras de autorização;
- `pontos_acesso`: guaritas e sentidos permitidos;
- `acessos`: entradas, saídas, tentativas e imagens;
- `logs_auditoria`: alterações administrativas;
- `configuracoes_sistema`: parâmetros exibidos pela aplicação.

A placa é sempre armazenada em maiúsculas e sem hífen. Exemplos: `CDU9598` e `FJB4E12`.

## Regras de autorização

| Recurso | Administrador | Segurança |
|---|---:|---:|
| Consultar pessoas e veículos | Sim | Sim |
| Alterar cadastros | Sim | Não |
| Reconhecer e registrar acesso | Sim | Sim |
| Liberar acesso manualmente | Sim | Sim |
| Gerenciar pontos de acesso | Sim | Não |
| Gerenciar contas | Sim | Não |
| Configurações e auditoria | Sim | Não |

As rotas possuem middleware, mas as Policies também são verificadas nos Form Requests e Controllers. Assim, uma rota adicionada incorretamente não ignora automaticamente as permissões.

## Endpoints principais

### Públicos

- `GET /api/health`
- `GET /api/configuracoes-publicas`
- `POST /api/login`

### Autenticados

- `GET /api/me`
- `POST /api/logout`
- `GET /api/meta`
- `GET /api/dashboard`
- `GET /api/pessoas`
- `GET /api/veiculos`
- `GET /api/acessos`
- `GET /api/pontos-acesso`
- `POST /api/reconhecimento/placa`
- `POST /api/acessos/manual`
- `PATCH /api/acessos/{acesso}/liberar`

### Exclusivos do administrador

- alterações em pessoas, veículos e pontos de acesso;
- consulta e atualização das duas contas;
- configurações do sistema;
- auditoria.

## Reconhecimento

Envie a captura como `multipart/form-data` no campo `imagem`:

```text
POST /api/reconhecimento/placa
imagem: arquivo JPG/PNG/WebP
ponto_acesso_id: opcional
tipo: entrada|saida (opcional quando o ponto define o sentido)
observacoes: opcional
```

A imagem é salva em `storage/app/private/capturas/AAAA/MM/DD`. Em erro ou leitura duplicada, a captura nova é removida. Em sucesso, o acesso mantém o caminho da imagem para consulta autenticada.

## Duplicidade e concorrência

O `AcessoService` usa:

1. lock de cache baseado no hash da placa;
2. transação de banco;
3. bloqueio pessimista no último acesso;
4. janela configurável de duplicidade.

Isso evita que duas requisições simultâneas registrem a mesma passagem.

## Configurações do `.env`

```env
PYTHON_EXECUTABLE="C:\laragon\www\guarita-digital\python\.venv\Scripts\python.exe"
PLACA_SCRIPT="C:\laragon\www\guarita-digital\python\placa.py"
TESSERACT_EXECUTABLE="C:\Program Files\Tesseract-OCR\tesseract.exe"
OCR_TIMEOUT_SECONDS=90
OCR_IDLE_TIMEOUT_SECONDS=30
MAX_CAPTURE_KB=10240
ACESSO_DUPLICATE_WINDOW_SECONDS=20
ACESSO_LOCK_SECONDS=15
ACESSO_LOCK_WAIT_SECONDS=5
CAPTURAS_DISK=local
```

## Verificação

```powershell
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
```

O PHP do Laragon deve ter `pdo_mysql`, `mbstring`, `xml`, `openssl` e `fileinfo` habilitados.
