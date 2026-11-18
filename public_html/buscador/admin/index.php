<?php
// Panel de administración del buscador. Toda la lógica vive en private/ (fuera de public_html).
declare(strict_types=1);

require __DIR__ . '/../_ruta_privada.php';
require BF_RUTA_PRIVADA . '/src/bootstrap.php';

\Buscador\Admin\App::atender();
