# Instala las herramientas para usar el Panel del buscador en una PC con Windows (solo una vez).
#
#   Clic derecho > "Ejecutar con PowerShell", o en PowerShell desde la carpeta del proyecto:
#     powershell -ExecutionPolicy Bypass -File .\instalar-herramientas.ps1
#
# Descarga de sus sitios oficiales y verifica con SHA-256:
#   - PHP 7.4.33 y PHP 8.3.36 (windows.php.net)       -> %USERPROFILE%\dev-tools\php74, php83
#   - MariaDB 10.11.20 portátil (mariadb.org)          -> %USERPROFILE%\dev-tools\mariadb
# No instala nada en el sistema ni toca el registro: todo queda en esa carpeta y se borra borrándola.
# Si un archivo ya está, lo omite. Requiere unos 400 MB libres e internet.

param([string]$Destino = (Join-Path $env:USERPROFILE 'dev-tools'))

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'   # sin barra de progreso: descarga mucho más rápido
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$paquetes = @(
    @{ Nombre = 'php74'; Archivo = 'php74.zip'; Sha = '14ae3250d4447c8ccfc4c45a70d90adfbcd61e728d85f0be56a7ddf8f9c8aace'
       Urls = @('https://downloads.php.net/~windows/releases/archives/php-7.4.33-nts-Win32-vc15-x64.zip',
                'https://windows.php.net/downloads/releases/archives/php-7.4.33-nts-Win32-vc15-x64.zip') },
    @{ Nombre = 'php83'; Archivo = 'php83.zip'; Sha = '1a097d52b7a68622cdc723073b61cc70caf79c941218ff65b2d8441dbc6b10d4'
       Urls = @('https://downloads.php.net/~windows/releases/php-8.3.36-1-nts-Win32-vs16-x64.zip',
                'https://downloads.php.net/~windows/releases/archives/php-8.3.36-1-nts-Win32-vs16-x64.zip',
                'https://windows.php.net/downloads/releases/archives/php-8.3.36-1-nts-Win32-vs16-x64.zip') },
    @{ Nombre = 'mariadb'; Archivo = 'mariadb.zip'; Sha = 'aad30008fd9ce30c12366edc6414920d8596e69d7db94de50c63642a6e2982c3'
       Urls = @('https://mirror.rackspace.com/mariadb/mariadb-10.11.20/winx64-packages/mariadb-10.11.20-winx64.zip',
                'https://archive.mariadb.org/mariadb-10.11.20/winx64-packages/mariadb-10.11.20-winx64.zip') }
)

function Escribir($texto, $color = 'Gray') { Write-Host $texto -ForegroundColor $color }

New-Item -ItemType Directory -Force (Join-Path $Destino 'dl') | Out-Null
Escribir "Carpeta de herramientas: $Destino" 'Cyan'

foreach ($p in $paquetes) {
    $carpeta = Join-Path $Destino $p.Nombre
    if (Test-Path $carpeta) { Escribir "  $($p.Nombre): ya instalado, se omite."; continue }

    $zip = Join-Path $Destino ('dl\' + $p.Archivo)
    $ok = (Test-Path $zip) -and ((Get-FileHash $zip -Algorithm SHA256).Hash.ToLower() -eq $p.Sha)
    foreach ($u in $p.Urls) {
        if ($ok) { break }
        Escribir "  Descargando $($p.Nombre) ..."
        try {
            Invoke-WebRequest -Uri $u -OutFile $zip -UseBasicParsing -UserAgent 'Mozilla/5.0'
            $ok = ((Get-FileHash $zip -Algorithm SHA256).Hash.ToLower() -eq $p.Sha)
            if (-not $ok) { Escribir "    La suma SHA-256 no coincide; se prueba otra fuente." 'Yellow' }
        } catch { Escribir "    No se pudo desde $u ; se prueba otra fuente." 'Yellow' }
    }
    if (-not $ok) { throw "No se pudo descargar y verificar $($p.Nombre). Revisa tu conexión e inténtalo de nuevo." }
    Escribir "    Verificado (SHA-256)." 'Green'

    $tmp = Join-Path $Destino ($p.Nombre + '-tmp')
    if (Test-Path $tmp) { Remove-Item -Recurse -Force $tmp }
    Expand-Archive -Path $zip -DestinationPath $tmp
    $interno = Get-ChildItem $tmp -Directory | Where-Object { $_.Name -like 'mariadb-*' } | Select-Object -First 1
    if ($p.Nombre -eq 'mariadb' -and $interno) { Move-Item $interno.FullName $carpeta; Remove-Item -Recurse -Force $tmp }
    else { Move-Item $tmp $carpeta }
}

# php.ini con las extensiones que usa el proyecto
foreach ($v in 'php74', 'php83') {
    $dir = Join-Path $Destino $v
    $ini = Join-Path $dir 'php.ini'
    if (-not (Test-Path $ini)) {
        $txt = Get-Content (Join-Path $dir 'php.ini-development') -Raw
        $txt = $txt -replace '(?m)^;\s*extension_dir\s*=\s*"ext"', 'extension_dir = "ext"'
        foreach ($e in 'pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'curl') {
            $txt = $txt -replace "(?m)^;extension=$e\s*$", "extension=$e"
            $txt = $txt -replace "(?m)^;extension=php_$e\.dll\s*$", "extension=php_$e.dll"
        }
        [IO.File]::WriteAllText($ini, $txt)
    }
    $salida = & (Join-Path $dir 'php.exe') -r "echo PHP_VERSION, extension_loaded('pdo_mysql') ? ' pdo_mysql' : ' SIN pdo_mysql';" 2>&1
    if ($LASTEXITCODE -ne 0 -or -not $salida) {
        Escribir "  $v no arranca. Casi siempre falta el 'Microsoft Visual C++ Redistributable 2015-2022 (x64)'." 'Yellow'
        Escribir "  Instálalo desde https://aka.ms/vs/17/release/vc_redist.x64.exe y vuelve a ejecutar este script." 'Yellow'
    } else { Escribir "  $v listo: $salida" 'Green' }
}

# Base de datos MariaDB (solo la primera vez), escuchando solo en esta PC (127.0.0.1:3307)
$datos = Join-Path $Destino 'mariadb-data'
if (-not (Test-Path $datos)) {
    Escribir "  Inicializando MariaDB en $datos ..."
    & (Join-Path $Destino 'mariadb\bin\mariadb-install-db.exe') "--datadir=$datos" '--port=3307' | Out-Null
}
Escribir "  MariaDB listo (puerto 3307, solo accesible desde esta PC)." 'Green'

Escribir ""
Escribir "Listo. Ahora da doble clic en  iniciar-panel.cmd  (en la carpeta del proyecto)." 'Cyan'
