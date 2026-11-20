# Guarita Digital

Sistema Laravel para cadastro de condutores e veículos e reconhecimento de
placas na guarita do IFFar - Campus São Vicente do Sul.

## Funcionalidades desta versão

- login por matrícula ou e-mail;
- Home com câmera e resumo dos cadastros;
- captura de uma foto pelo navegador;
- detecção da placa com YOLO e leitura com PaddleOCR;
- consulta de autorização do veículo;
- cadastro, edição, busca e exclusão de condutores e veículos;
- validação de CPF e de placas brasileiras antigas e Mercosul.

A foto é enviada em JSON para uma rota interna protegida pelo login. O Laravel
salva um arquivo temporário, chama `python/reconhecer_imagem.py`, recebe uma
resposta JSON e apaga a foto ao final do processamento.

## Requisitos

- PHP 8.3 ou superior;
- Composer;
- MySQL 8 ou MariaDB compatível;
- Python 3.11 de 64 bits;
- conexão com a internet na primeira execução para baixar os modelos.

Não é necessário instalar pacotes com NPM: os arquivos CSS e JavaScript usados
pelo sistema já estão em `public`.

## Instalação no Laragon

No terminal, dentro da pasta do projeto:

```powershell
composer install
copy .env.example .env
php artisan key:generate
mysql -u root -p < database/create_database.sql
php artisan migrate --seed

py -3.11 -m venv python\.venv
python\.venv\Scripts\python.exe -m pip install --upgrade pip
python\.venv\Scripts\python.exe -m pip install -r python\requirements.txt
```

Configure no `.env` o caminho completo do Python. Use barras normais para
evitar problemas de escape no Windows:

```env
PYTHON_PATH="C:/laragon/www/guarita-digital/python/.venv/Scripts/python.exe"
PLACA_SCRIPT="C:/laragon/www/guarita-digital/python/reconhecer_imagem.py"
```

Depois, limpe os caches e inicie o sistema:

```powershell
php artisan optimize:clear
php artisan serve
```

Acesse `http://127.0.0.1:8000`. A webcam funciona em `localhost` ou por HTTPS e
precisa ser autorizada no navegador.

## Acesso inicial

O seeder cria uma única conta ativa, identificada como **Guarda da Guarita** e
com perfil de Segurança. Ao executá-lo em uma instalação existente, as outras
contas são desativadas e movidas para exclusão lógica, preservando os registros
históricos. Altere `ADMIN_EMAIL`, `GUARD_NAME`, `ADMIN_MATRICULA` e
`ADMIN_PASSWORD` no `.env` antes de executar o seeder para definir as credenciais
do operador. Em produção, `ADMIN_PASSWORD` deve ter pelo menos 16 caracteres;
não use credenciais de exemplo. A execução do seeder em produção falha se essa
senha não estiver configurada.

Para aplicar a configuração em uma instalação existente:

```powershell
php artisan db:seed --class=UsuariosSistemaSeeder
```

> O seeder desativa e move para exclusão lógica todas as contas diferentes da
> conta configurada. Em atualizações normais, execute apenas as migrações e não
> rode o seeder novamente.

## Estrutura principal

- `app/`: controllers, models, validações e integração com o Python;
- `public/`: CSS e JavaScript utilizados diretamente pelas páginas;
- `python/`: reconhecimento YOLO/PaddleOCR e análise de qualidade;
- `resources/views/`: telas Blade;
- `database/`: migrations, seeders e criação do banco.

As pastas `vendor`, `python/.venv`, o arquivo `.env`, logs e caches não fazem
parte do código-fonte. Eles são recriados pelos comandos de instalação acima.

Veja [docs/IMPLANTACAO-HOSPEDAGEM.md](docs/IMPLANTACAO-HOSPEDAGEM.md) para
publicação em hospedagem compartilhada por SFTP.

## Erro de UTF-8 corrigido

A comunicação entre Python e Laravel usa JSON ASCII no `stdout`. Mensagens do
processo são normalizadas antes de entrarem na resposta HTTP, e o controller
substitui qualquer byte inválido como proteção adicional. Isso evita o erro
`Malformed UTF-8 characters, possibly incorrectly encoded`.
