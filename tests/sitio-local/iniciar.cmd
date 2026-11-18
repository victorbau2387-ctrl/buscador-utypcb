@echo off
rem Arranca el buscador en tu computadora: MariaDB local + base de prueba + sitio simulado + panel.
rem Uso: doble clic, o desde la raiz del repo:  tests\sitio-local\iniciar.cmd [php74]
rem Requiere las herramientas portatiles en %USERPROFILE%\dev-tools (php74, php83, mariadb).
setlocal
set "DEV=%USERPROFILE%\dev-tools"
set "VER=%~1"
if "%VER%"=="" set "VER=php83"
set "PHP=%DEV%\%VER%\php.exe"
set "MDB=%DEV%\mariadb\bin"
cd /d "%~dp0..\.."

if not exist "%PHP%" ( echo No encuentro %PHP% & pause & exit /b 1 )

"%PHP%" -r "exit(@fsockopen('127.0.0.1',3307)?0:1);"
if errorlevel 1 (
  echo Iniciando MariaDB en 127.0.0.1:3307 ...
  start "MariaDB local" /min "%MDB%\mysqld.exe" --defaults-file="%DEV%\mariadb-data\my.ini" --bind-address=127.0.0.1 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
  timeout /t 4 /nobreak >nul
)

echo Preparando la base bf_widget ...
"%MDB%\mariadb.exe" -h127.0.0.1 -P3307 -uroot -e "CREATE DATABASE IF NOT EXISTS bf_widget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" || ( pause & exit /b 1 )
"%MDB%\mariadb.exe" -h127.0.0.1 -P3307 -uroot bf_widget < database\schema.sql || ( pause & exit /b 1 )

set "BF_CONFIG=tests/sitio-local/config.local.php"
"%PHP%" private\bin\importar.php | findstr /b "Importaci"

echo.
echo  Sitio UTyP con el buscador:    http://127.0.0.1:8080/
echo  Pagina de pruebas (desarrollo): http://127.0.0.1:8080/pruebas.php
echo  Panel de administracion:       http://127.0.0.1:8080/buscador/admin/
echo  (Ctrl+C para detener)
echo.
start "" http://127.0.0.1:8080/
"%PHP%" -S 127.0.0.1:8080 -t tests/sitio-local tests/sitio-local/router.php
