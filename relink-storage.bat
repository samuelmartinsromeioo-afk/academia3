@echo off
REM ============================================================
REM  relink-storage.bat
REM  Recria o link public\storage -> storage\app\public no Windows.
REM
REM  Por que existe: no repo o public/storage e um symlink (git 120000)
REM  apontando para o caminho do servidor Linux. No Windows o git o
REM  materializa como arquivo de texto e as imagens quebram.
REM  Este script troca isso por uma JUNCTION local (nao precisa de admin)
REM  e nao afeta o symlink versionado usado pelo servidor.
REM ============================================================

setlocal
set "ROOT=%~dp0"
set "LINK=%ROOT%public\storage"
set "TARGET=%ROOT%storage\app\public"

echo.
echo Recriando link de storage...
echo   Link  : %LINK%
echo   Alvo  : %TARGET%
echo.

REM Garante que o git ignore alteracoes locais nesse caminho
git -C "%ROOT%." update-index --skip-worktree public/storage >nul 2>&1

REM Remove o que estiver la (junction antiga, pasta ou arquivo falso)
if exist "%LINK%" (
    rmdir "%LINK%" >nul 2>&1
    del /f /q "%LINK%" >nul 2>&1
)

REM Cria a junction
mklink /J "%LINK%" "%TARGET%"
if errorlevel 1 (
    echo.
    echo [ERRO] Nao foi possivel criar a junction.
    echo Verifique se a pasta storage\app\public existe.
) else (
    echo.
    echo [OK] Link criado com sucesso. Recarregue a pagina com Ctrl+F5.
)

echo.
pause
endlocal
