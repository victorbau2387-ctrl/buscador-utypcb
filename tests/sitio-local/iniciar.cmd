@echo off
rem Arranca el buscador en esta PC: MariaDB local + base de datos + servidor + navegador.
rem Uso: doble clic, o desde la raiz del repo:  tests\sitio-local\iniciar.cmd [php74]
rem      iniciar-panel.cmd (en la raiz) hace lo mismo pero abre directo el Panel del buscador.
rem Requiere las herramientas en %USERPROFILE%\dev-tools (las instala instalar-herramientas.ps1).
rem
rem Las preguntas del CSV se importan SOLO la primera vez (base vacia), para no pisar lo editado en el panel.
rem Para volver a cargar el CSV a proposito:  set BF_REIMPORTAR=1  antes de ejecutarlo.
setlocal
set "DEV=%USERPROFILE%\dev-tools"
set "VER=%~1"
if "%VER%"=="" set "VER=php83"
set "PHP=%DEV%\%VER%\php.exe"
set "MDB=%DEV%\mariadb\bin"
cd /d "%~dp0..\.."

if not exist "%PHP%" (
  echo No encuentro %PHP%
  echo Ejecuta primero instalar-herramientas.ps1 ^(clic derecho ^> Ejecutar con PowerShell^).
  pause & exit /b 1
)

"%PHP%" -r "exit(@fsockopen('127.0.0.1',3307)?0:1);"
if errorlevel 1 (
  echo Iniciando MariaDB en 127.0.0.1:3307 ...
  start "MariaDB local" /min "%MDB%\mysqld.exe" --defaults-file="%DEV%\mariadb-data\my.ini" --bind-address=127.0.0.1 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
  rem Espera hasta 30 s a que MariaDB acepte conexiones (sin depender del comando timeout).
  "%PHP%" -r "for($i=0;$i<60;$i++){if(@fsockopen('127.0.0.1',3307)){exit(0);}usleep(500000);}exit(1);"
  if errorlevel 1 ( echo MariaDB no arranco. Cierra esta ventana y vuelve a intentarlo. & pause & exit /b 1 )
)

echo Preparando la base de datos ...
"%MDB%\mariadb.exe" -h127.0.0.1 -P3307 -uroot -e "CREATE DATABASE IF NOT EXISTS bf_widget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" || ( pause & exit /b 1 )
"%MDB%\mariadb.exe" -h127.0.0.1 -P3307 -uroot bf_widget < database\schema.sql || ( pause & exit /b 1 )

set "BF_CONFIG=tests/sitio-local/config.local.php"
set "CONTAR=$p=new PDO('mysql:host=127.0.0.1;port=3307;dbname=bf_widget','root','');exit((int)$p->query('SELECT COUNT(*) FROM TABLA')->fetchColumn()>0?0:1);"

"%PHP%" -r "%CONTAR:TABLA=bf_faq%"
if errorlevel 1 set "BF_REIMPORTAR=1"
if "%BF_REIMPORTAR%"=="1" (
  echo Cargando las preguntas de conocimiento\preguntas.csv ...
  "%PHP%" private\bin\importar.php | findstr /b "Importaci"
) else (
  echo Preguntas ya cargadas: se conservan los cambios hechos en el panel.
)

"%PHP%" -r "%CONTAR:TABLA=bf_admin%"
if errorlevel 1 (
  echo.
  echo  ==== Primera vez: crea tu cuenta del panel ====
  echo  Elige el rol superadmin. La contrasena se ve como asteriscos, o no se ve, al escribirla.
  echo.
  "%PHP%" private\bin\crear-admin.php
)

set "URL=http://127.0.0.1:8080/"
if /i "%BF_ABRIR%"=="panel" set "URL=http://127.0.0.1:8080/buscador/admin/"

echo.
echo  Panel del buscador:            http://127.0.0.1:8080/buscador/admin/
echo  Sitio UTyP con el buscador:    http://127.0.0.1:8080/
echo  Deja esta ventana abierta mientras lo usas (Ctrl+C o cerrarla lo detiene).
echo.
start "" %URL%
"%PHP%" -S 127.0.0.1:8080 -t tests/sitio-local tests/sitio-local/router.php
echo.
echo El servidor se detuvo. Si fue por un error, lee el mensaje de arriba.
pause
