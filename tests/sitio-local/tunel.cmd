@echo off
rem Comparte el sitio local por internet con un enlace temporal de Cloudflare (sin cuenta).
rem 1) Ejecuta antes iniciar.cmd (o deja este script iniciar el servidor). 2) Copia el enlace
rem    https://....trycloudflare.com que aparece abajo. 3) Cierra esta ventana para apagarlo.
rem AVISO: cualquiera con el enlace puede entrar, incluido el panel. Apagalo al terminar.
setlocal
set "DEV=%USERPROFILE%\dev-tools"
cd /d "%~dp0..\.."
"%DEV%\php83\php.exe" -r "exit(@fsockopen('127.0.0.1',8080)?0:1);"
if errorlevel 1 (
  set "BF_CONFIG=tests/sitio-local/config.tunel.php"
  start "Servidor buscador" /min "%DEV%\php83\php.exe" -S 127.0.0.1:8080 -t tests/sitio-local tests/sitio-local/router.php
)
echo Buscando enlace... (si dice 429, espera un minuto y vuelve a abrir este archivo)
"%DEV%\cloudflared\cloudflared.exe" tunnel --no-autoupdate --url http://127.0.0.1:8080
