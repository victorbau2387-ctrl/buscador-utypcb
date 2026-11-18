@echo off
rem Abre el Panel del buscador en esta PC (doble clic). Primero ejecuta instalar-herramientas.ps1 una vez.
set "BF_ABRIR=panel"
call "%~dp0tests\sitio-local\iniciar.cmd" %*
