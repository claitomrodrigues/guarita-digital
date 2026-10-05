@echo off
setlocal EnableExtensions DisableDelayedExpansion
chcp 65001 >nul
cd /d "%~dp0"

set "PYTHON_EXE="

echo Procurando o Python 3.11 de 64 bits...

rem Primeiro tenta localizar o Python 3.11 pelo Python Launcher.
where py >nul 2>&1
if not errorlevel 1 (
    for /f "delims=" %%I in ('py -3.11 -c "import sys; print(sys.executable)" 2^>nul') do set "PYTHON_EXE=%%I"
)

rem Se o py.exe nao existir, aceita o comando python quando ele for o 3.11 x64.
if not defined PYTHON_EXE (
    for /f "delims=" %%I in ('where python 2^>nul') do (
        if not defined PYTHON_EXE (
            "%%I" -c "import sys; assert sys.version_info.major == 3 and sys.version_info.minor == 11 and sys.maxsize > 2**32" >nul 2>&1
            if not errorlevel 1 set "PYTHON_EXE=%%I"
        )
    )
)

rem Tambem verifica os locais mais comuns de instalacao no Windows.
if not defined PYTHON_EXE (
    for %%I in ("%LocalAppData%\Programs\Python\Python311\python.exe" "%ProgramFiles%\Python311\python.exe" "C:\Python311\python.exe") do (
        if not defined PYTHON_EXE if exist "%%~I" (
            "%%~I" -c "import sys; assert sys.version_info.major == 3 and sys.version_info.minor == 11 and sys.maxsize > 2**32" >nul 2>&1
            if not errorlevel 1 set "PYTHON_EXE=%%~I"
        )
    )
)

if not defined PYTHON_EXE (
    echo.
    echo ERRO: o Python 3.11 de 64 bits nao foi encontrado.
    echo Instale o Python 3.11 x64 e execute este arquivo novamente.
    echo O Python 3.14 nao deve ser usado neste projeto.
    exit /b 1
)

echo Python encontrado: %PYTHON_EXE%

if exist ".venv\Scripts\python.exe" (
    ".venv\Scripts\python.exe" -c "import sys; assert sys.version_info.major == 3 and sys.version_info.minor == 11 and sys.maxsize > 2**32" >nul 2>&1
    if errorlevel 1 (
        echo.
        echo Foi encontrado um ambiente virtual antigo ou invalido em:
        echo %CD%\.venv
        choice /C SN /M "Deseja recria-lo com o Python 3.11"
        if errorlevel 2 exit /b 1
        rmdir /s /q "%CD%\.venv"
    )
)

if not exist ".venv\Scripts\python.exe" (
    echo Criando o ambiente virtual com Python 3.11...
    "%PYTHON_EXE%" -m venv .venv
    if errorlevel 1 exit /b 1
)

echo Atualizando as ferramentas de instalacao...
".venv\Scripts\python.exe" -m pip install --upgrade pip setuptools wheel
if errorlevel 1 exit /b 1

echo Instalando as dependencias do reconhecimento...
".venv\Scripts\python.exe" -m pip install --prefer-binary -r requirements.txt
if errorlevel 1 exit /b 1

echo Conferindo conflitos entre os pacotes...
".venv\Scripts\python.exe" -m pip check
if errorlevel 1 exit /b 1

echo Verificando a instalacao...
".venv\Scripts\python.exe" verificar_instalacao.py
if errorlevel 1 exit /b 1

echo.
echo Instalacao concluida com sucesso.
echo Python para o Laravel: %CD%\.venv\Scripts\python.exe
exit /b 0
