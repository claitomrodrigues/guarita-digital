# Reconhecimento de placas — Guarita Digital

Esta pasta contém o pipeline de reconhecimento com YOLO e PaddleOCR para ser
chamado pelo Laravel. O projeto foi padronizado para **Python 3.11 de 64 bits**.

## Instalação no Windows/Laragon

Não copie nem reutilize uma `.venv` criada em outra instalação do Python. O
ambiente virtual contém caminhos absolutos da máquina e precisa ser criado
localmente.

Abra o terminal do Laragon/Cmder na pasta `python` e execute:

```bat
.\instalar_windows.bat
```

O instalador procura o Python 3.11 pelo `py.exe`, pelo comando `python` e nos
diretórios mais comuns do Windows. Assim, ele também funciona quando o Python
Launcher não foi instalado. Em um terminal Git Bash, use
`cmd.exe //d //c instalar_windows.bat`.

O instalador identifica uma `.venv` antiga, pede confirmação antes de removê-la
e cria um ambiente novo com o Python 3.11. Ao final, ele informa o caminho que o
Laravel deve executar.

## Configuração no Laravel

O executável recomendado é:

```text
C:/laragon/www/guarita-digital/python/.venv/Scripts/python.exe
```

Use esse valor na variável que o projeto Laravel lê, normalmente no `.env`:

```env
PYTHON_PATH=C:/laragon/www/guarita-digital/python/.venv/Scripts/python.exe
```

Depois de alterar o `.env`, execute na raiz do Laravel:

```bat
php artisan optimize:clear
```

Reinicie também o `php artisan serve`.

## Teste direto

Com uma fotografia salva no computador:

```bat
.venv\Scripts\python.exe reconhecer_imagem.py "C:\caminho\foto.jpg"
```

O stdout sempre devolve uma única linha JSON, inclusive quando não encontra a
placa. Exemplo de sucesso:

```json
{"placa":"ABC1D23","confianca":91.2,"confianca_yolo":0.88,"modelo":"mercosul","quadros_confirmados":1}
```

Exemplo de falha controlada:

```json
{"placa":null,"motivo":"YOLO não encontrou uma placa"}
```

O primeiro reconhecimento precisa de internet para baixar os pesos do YOLO e
do PaddleOCR. Depois disso, os modelos ficam no cache local.

## Modelo YOLO local

Para trabalhar sem download em tempo de execução, salve o arquivo `.pt` no
computador e configure, por exemplo:

```env
GUARITA_MODELO_YOLO=C:/laragon/www/guarita-digital/python/modelos/best.pt
```

Também podem ser configuradas as variáveis `GUARITA_REPOSITORIO_YOLO`,
`GUARITA_ARQUIVO_YOLO`, `GUARITA_MODELO_OCR` e `GUARITA_DISPOSITIVO`.

## Validações

Para conferir apenas a instalação:

```bat
.venv\Scripts\python.exe verificar_instalacao.py
```

Para executar os testes locais do tratamento de placas:

```bat
.venv\Scripts\python.exe testar.py
```

## Arquivos gerados

Ambientes virtuais, caches, diagnósticos, datasets e resultados de treinamento
ficam fora do controle de versão e não devem ser enviados dentro do ZIP.
