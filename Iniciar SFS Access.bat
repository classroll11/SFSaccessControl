@echo off
setlocal
cd /d "%~dp0"

if not exist "%~dp0.venv\Scripts\python.exe" (
    echo No se encontro el entorno Python de SFS Access.
    echo Completa primero la instalacion indicada en el README.
    pause
    exit /b 1
)

"%~dp0.venv\Scripts\python.exe" "%~dp0main.py"
if errorlevel 1 (
    echo.
    echo SFS Access se cerro con un error. Revisa el mensaje anterior.
    pause
)
