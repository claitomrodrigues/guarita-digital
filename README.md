# Guarita Digital

Backend do sistema de controle de acesso veicular do IFFar – Campus São Vicente do Sul. A aplicação utiliza Laravel e MySQL para cadastros e movimentações, enquanto o reconhecimento de placas é executado por Python, OpenCV e Tesseract OCR.

## Perfis do sistema

O banco permite exatamente duas contas, uma para cada perfil:

- **Administrador:** gerencia pessoas, veículos, pontos de acesso, configurações, usuários e auditoria.
- **Segurança da guarita:** consulta cadastros, reconhece placas, registra entradas e saídas e realiza liberações manuais.

Não existem endpoints para criar ou excluir usuários. A coluna `perfil` possui índice único, impedindo mais de uma conta de cada perfil.

## Requisitos

- PHP 8.3 ou superior, com `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` e `xml`;
- Composer;
- MySQL 8 ou MariaDB compatível;
- Python e ambiente virtual;
- OpenCV, NumPy, Pillow e pytesseract;
- Tesseract OCR instalado no Windows.

## Instalação

```powershell
cd C:\laragon\www\guarita-digital
composer install
copy .env.example .env
php artisan key:generate
```

Crie o banco:

```powershell
mysql -u root -p < database/create_database.sql
```

Configure as credenciais e os caminhos do Python/Tesseract no `.env`, depois execute:

```powershell
php artisan optimize:clear
php artisan migrate:fresh --seed
```

Prepare o reconhecimento:

```powershell
py -m venv python\.venv
python\.venv\Scripts\python.exe -m pip install -r python\requirements.txt
```

Inicie o backend:

```powershell
php artisan serve
```

## Contas iniciais

| Perfil | E-mail | Senha inicial |
|---|---|---|
| Administrador | `admin@guarita.local` | `Guarita@2026` |
| Segurança | `seguranca@guarita.local` | `Seguranca@2026` |

Altere as senhas no primeiro uso e não utilize essas credenciais em produção.

## Principais recursos

- autenticação por sessão com limitação de tentativas;
- autorização por Policies e perfis;
- validação real de CPF;
- validação de placas antigas e Mercosul;
- cadastro de pessoas, veículos e pontos de acesso;
- reconhecimento Laravel → Python → OpenCV/Tesseract;
- prevenção de leituras duplicadas com lock por placa;
- histórico de entrada e saída;
- liberação manual auditada;
- imagens armazenadas em disco privado;
- dashboard e logs de auditoria;
- respostas JSON por API Resources.

A documentação detalhada está em [`docs/BACKEND.md`](docs/BACKEND.md).
