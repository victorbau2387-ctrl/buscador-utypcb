<?php
// Léxico del buscador: palabras vacías, frases coloquiales y sinónimos.
// Todo en minúsculas y SIN acentos (se compara después de normalizar).
// Editable por el área de sistemas; no requiere tocar el motor.

return [
    // Abreviaturas y escritura de chat → forma estándar.
    'expansiones' => [
        'q' => 'que', 'k' => 'que', 'ke' => 'que', 'xq' => 'porque', 'pq' => 'porque', 'porq' => 'porque',
        'tmb' => 'tambien', 'tb' => 'tambien', 'info' => 'informacion', 'inf' => 'informacion',
        'uni' => 'universidad', 'utp' => 'universidad', 'utyp' => 'universidad', 'xfa' => 'favor',
        'porfa' => 'favor', 'pls' => 'favor', 'd' => 'de', 'x' => 'por', 'xa' => 'para', 'pa' => 'para',
        'ing' => 'ingenieria', 'lic' => 'licenciatura', 'tsu' => 'tecnico', 'cuatri' => 'cuatrimestre',
        'compu' => 'computacion', 'insc' => 'inscripcion', 'reinsc' => 'reinscripcion', 'tel' => 'telefono',
        'cel' => 'telefono', 'ay' => 'hay', 'ai' => 'hay', 'kiero' => 'quiero', 'kisiera' => 'quisiera', 'cuanta' => 'cuanto', 'whats' => 'whatsapp', 'wasap' => 'whatsapp', 'face' => 'facebook', 'fb' => 'facebook',
        // Agregadas en el borrador de la base de conocimiento (escritura de chat frecuente).
        'cuatris' => 'cuatrimestres', 'prepa' => 'bachillerato', 'bachi' => 'bachillerato', 'insta' => 'instagram',
        'dnd' => 'donde', 'cnd' => 'cuando', 'kndo' => 'cuando', 'cdo' => 'cuando', 'xk' => 'porque',
        'wasa' => 'whatsapp', 'guasap' => 'whatsapp', 'whatsap' => 'whatsapp', 'wpp' => 'whatsapp',
        'plis' => 'favor', 'porfis' => 'favor', 'prope' => 'propedeutico', 'extras' => 'extraordinario',
        'extra' => 'extraordinario', 'chamba' => 'trabajo', 'chambear' => 'trabajar',
        'kuanto' => 'cuanto', 'kuando' => 'cuando', 'keda' => 'queda', 'kedan' => 'quedan', 'aki' => 'aqui', 'ola' => 'hola',
    ],

    // Frases que se reemplazan antes de separar palabras (la clave se busca dentro del texto normalizado).
    'frases' => [
        'cuanto cuesta'   => 'costo', 'cuanto sale'    => 'costo', 'cuanto se paga' => 'costo',
        'cuanto cobran'   => 'costo', 'cuanto hay que pagar' => 'costo', 'cuanto es' => 'costo',
        'a que hora'      => 'horario', 'en que horario' => 'horario',
        'donde queda'     => 'ubicacion', 'donde esta'   => 'ubicacion', 'como llego' => 'ubicacion',
        'servicio social' => 'serviciosocial', 'estadia' => 'estadias',
        'examen de admision' => 'examen admision', 'nuevo ingreso' => 'admision',
        'ficha de admision'  => 'ficha admision', 'plan de estudios' => 'planestudios',
        'mapa curricular'    => 'planestudios', 'materias' => 'planestudios', 'reticula' => 'planestudios',
        'dar de baja'        => 'baja', 'darme de baja' => 'baja',
        'constancia de estudios' => 'constancia', 'historial academico' => 'kardex',
        // Agregadas en el borrador de la base de conocimiento.
        'cuanto me cuesta' => 'costo', 'cuanto me sale' => 'costo', 'cuanto vale' => 'costo', 'a como esta' => 'costo',
        'en cuanto esta' => 'costo', 'cuanto dinero' => 'costo', 'cuanto pagan' => 'costo', 'en cuanto anda' => 'costo', 'cuanto anda' => 'costo',
        'pre registro' => 'preregistro', 'pre registrarme' => 'preregistro',
        'lista de aceptados' => 'resultado aceptados', 'redes sociales' => 'facebook',
        'tecnico superior universitario' => 'tecnico',
        'proyecto integrador' => 'integrador', 'curso propedeutico' => 'propedeutico',
        'curso de induccion' => 'induccion', 'dias festivos' => 'festivo', 'sin clases' => 'suspension',
        'no hay clases' => 'suspension', 'control escolar' => 'escolares', 'servicios escolares' => 'escolares',
        'recursos financieros' => 'financieros', 'bolsa de trabajo' => 'bolsa trabajo',
        'cedula profesional' => 'cedula', 'beca benito juarez' => 'beca benito', 'jovenes escribiendo el futuro' => 'beca benito',
        // Agregadas con las preguntas de alumnos inscritos.
        // "dónde/cómo pago" pregunta por la forma de pago, no por el monto (pagar es sinónimo de costo).
        'donde pago' => 'formapago', 'donde se paga' => 'formapago', 'donde pagar' => 'formapago', 'donde le pago' => 'formapago',
        'como pago' => 'formapago', 'como se paga' => 'formapago', 'como pagar' => 'formapago', 'donde deposito' => 'formapago deposito',
        'donde le deposito' => 'formapago deposito',
        // "matrícula" suele ser el número de alumno; como sinónimo de inscripción se confundía.
        'numero de control' => 'numcontrol', 'mi matricula' => 'numcontrol',
        'con cuanto se pasa una materia' => 'aprobar materia', 'con cuanto paso' => 'aprobar', 'con cuanto se aprueba' => 'aprobar',
        'buena conducta' => 'buenaconducta', 'sociedad de alumnos' => 'sociedadalumnos', 'objetos perdidos' => 'perdido',
        'no puedo entrar' => 'olvide', 'tiempo completo' => 'tiempocompleto',
    ],

    // Grupos de sinónimos: cualquier palabra del grupo se convierte en la primera.
    'sinonimos' => [
        ['costo', 'costos', 'precio', 'precios', 'cuesta', 'cuestan', 'cobran', 'pagar', 'pago', 'pagos', 'cuota', 'cuotas', 'colegiatura', 'colegiaturas', 'mensualidad', 'arancel', 'aranceles', 'tarifa', 'costar', 'cobrar'],
        ['admision', 'ingreso', 'ingresar', 'entrar', 'aspirante', 'aspirantes', 'convocatoria', 'preinscripcion'],
        ['inscripcion', 'inscribirme', 'inscribir', 'inscribo', 'inscripciones', 'matricula', 'registrarme', 'inscriba', 'inscribe'],
        ['reinscripcion', 'reinscribirme', 'reinscribir', 'reinscripciones', 'reinscribo', 'reinscribe'],
        ['carrera', 'carreras', 'licenciatura', 'licenciaturas', 'ingenieria', 'ingenierias', 'oferta', 'programa', 'programas', 'estudiar'],
        ['requisito', 'requisitos', 'documento', 'documentos', 'papeles', 'papeleria', 'necesito', 'piden', 'ocupo', 'pedir'],
        ['beca', 'becas', 'apoyo', 'apoyos', 'ayuda', 'descuento', 'descuentos'],
        ['horario', 'horarios', 'hora', 'horas', 'turno', 'turnos', 'abren', 'cierran', 'atienden'],
        ['ubicacion', 'direccion', 'domicilio', 'mapa', 'llegar', 'ubicados', 'calle', 'ubicada', 'ubicado'],
        // Antes teléfono, correo y WhatsApp eran sinónimos de "contacto" y no se podían distinguir.
        ['contacto', 'comunicarme', 'contactar', 'comunico', 'contactos'],
        ['telefono', 'telefonos', 'llamar', 'marcar', 'celular'],
        ['correo', 'email', 'mail', 'gmail', 'correos'],
        ['whatsapp', 'whatsaap', 'watsap'],
        ['facebook', 'instagram', 'tiktok'],
        ['calendario', 'fechas', 'fecha', 'cuando', 'periodo', 'periodos'],
        ['vacaciones', 'vacacion', 'descanso', 'asueto', 'puente', 'receso'],
        ['titulacion', 'titularme', 'titulo', 'titularse', 'titulos'],
        ['cedula', 'cedulas'],
        ['deporte', 'deportes', 'futbol', 'basquet', 'voleibol', 'equipo', 'equipos', 'torneo', 'selectivo', 'baloncesto', 'basquetbol', 'voley', 'seleccion', 'selecciones', 'practican', 'practicar'],
        ['noticia', 'noticias', 'aviso', 'avisos', 'novedades', 'comunicado', 'comunicados'],
        ['tramite', 'tramites', 'solicitar', 'sacar', 'saco', 'tramitar', 'gestionar'],
        ['transparencia', 'rendicion'],
        ['examen', 'examenes', 'prueba', 'evaluacion', 'evaluaciones', 'exani', 'ezamen'],
        ['ficha', 'fichas', 'fixa'],
        ['triptico', 'tripticos', 'folleto', 'folletos', 'brochure'],
        ['medico', 'consultorio', 'salud', 'doctor', 'doctora', 'enfermera', 'enfermero'],
        // Agregados en el borrador de la base de conocimiento.
        ['inicio', 'empiezan', 'empieza', 'comienzan', 'comienza', 'inician', 'inicia', 'arrancan', 'arranca'],
        ['fin', 'termina', 'terminan', 'acaba', 'acaban', 'cierre'],
        ['suspension', 'suspenden', 'suspendido', 'festivo', 'festivos', 'feriado', 'feriados', 'inhabil', 'inhabiles'],
        ['extraordinario', 'extraordinarios', 'extraordinaria', 'extraordinarias'],
        ['clausura', 'graduacion', 'graduaciones', 'graduarme', 'graduan', 'gradua', 'egresados'],
        ['estadias', 'estancias', 'estancia', 'practicas', 'residencias', 'residencia'],
        ['credencial', 'credenciales', 'gafete'],
        ['calificacion', 'calificaciones', 'notas', 'boleta', 'boletas'],
        ['baja', 'bajas', 'salirme'],
        // 'historial' y 'historia' comparten raíz ('histor'): el kárdex se normaliza a 'kardex'.
        ['kardex', 'historial', 'cardex', 'kardez'],
        ['biblioteca', 'bibliotecas', 'libros', 'bibloteca'],
        ['cafeteria', 'comedor', 'cafe', 'lonche', 'comida', 'tiendita', 'comer', 'almorzar', 'desayunar'],
        // 'camion' va primero: 'transporte' y 'transparencia' comparten la raíz de 6 letras 'transp'.
        ['camion', 'transporte', 'camiones', 'autobus', 'combi', 'combis', 'ruta', 'rutas'],
        ['internet', 'wifi', 'inalambrica'],
        ['seguro', 'imss', 'facultativo'],
        ['psicologo', 'psicologa', 'psicologica', 'tutoria', 'tutorias', 'orientacion'],
        ['propedeutico', 'propedeutica', 'propedeuticos'],
        ['psicometrico', 'psicometrica', 'sicometrico', 'sicometrica', 'psicometria'],
        ['preregistro', 'prerregistro', 'preregistrarme', 'prerregistrarme', 'registro'],
        ['rechazado', 'rechazaron', 'rechazada'],
        ['resultado', 'resultados', 'aceptados', 'aceptado', 'aceptaron', 'admitidos', 'quede', 'quedaron', 'quedo', 'acepten', 'acepta'],
        ['rector', 'rectora', 'rectoria'],
        ['ingles', 'idioma', 'idiomas', 'english'],
        ['duracion', 'dura', 'duran', 'tarda', 'tardan'],
        ['cuatrimestre', 'cuatrimestres', 'semestre', 'semestres', 'cuatrimestral', 'semestral', 'semestrales', 'cuatrimestrales'],
        ['bachillerato', 'preparatoria', 'cobach'],
        ['computadora', 'laptop', 'computo', 'pc'],
        ['mascota', 'coyote', 'coyotes'],
        ['presupuesto', 'presupuestos'],
        ['equivalencia', 'revalidacion', 'revalidar', 'equivalencias'],
        ['uniforme', 'uniformes'],
        ['trabajo', 'trabajar', 'empleo', 'vacantes'],
        ['puntaje', 'puntos', 'aciertos', 'puntuacion'],
        ['total', 'todo', 'todos', 'completo'],
        ['entrega', 'entrego', 'entregar', 'reciben', 'recepcion', 'entregan'],
        ['limite', 'ultimo', 'ultima', 'vence', 'vencimiento', 'plazo'],
        ['reingreso', 'volver', 'regresar', 'reincorporarme', 'reincorporar'],
        ['cambio', 'cambiar', 'cambiarme', 'pasarme'],
        // Agregados con las preguntas de alumnos inscritos.
        ['factura', 'facturas', 'facturar', 'facturan', 'facturacion', 'cfdi', 'deducir'],
        ['quitan', 'quiten', 'quita', 'pierde', 'pierdo', 'perder', 'cancelan', 'cancelar', 'cancelaron'],
        ['reprobar', 'repruebo', 'reprobe', 'reprobada', 'reprobadas', 'reprobado', 'reprobados', 'reprueba', 'repruebas', 'repruebe', 'reprobacion', 'trone', 'tronar', 'truene'],
        ['aprobar', 'aprobatoria', 'aprobatorio', 'aprobatorias', 'apruebo', 'aprobado', 'aprobada', 'aprueba'],
        ['minimo', 'minima', 'minimos', 'minimas'],
        ['revision', 'aclaracion', 'aclarar', 'inconforme', 'inconformidad', 'conforme', 'equivoco', 'equivocaron', 'equivocado'],
        // 'recursar' y 'recursos' comparten la raíz 'recurs': se normaliza a 'repetir'.
        ['repetir', 'recursar', 'recursamiento', 'recursando', 'recurse', 'recursarla', 'repito', 'repita'],
        ['retardo', 'retardos', 'tolerancia', 'impuntual'],
        ['grupo', 'grupos', 'salon', 'salones'],
        ['traslado', 'trasladarme', 'trasladar', 'portabilidad', 'transferirme'],
        // 'asesoria' y 'asesor' (de estadía) comparten raíz: las asesorías académicas se normalizan a 'regularizacion'.
        ['regularizacion', 'asesoria', 'asesorias', 'regularizarme', 'regularizar', 'refuerzo'],
        ['asesor', 'asesores', 'asesora', 'asesorar'],
        ['lengua', 'lenguas', 'frances', 'aleman', 'italiano', 'chino', 'japones', 'portugues', 'coreano'],
        ['semana', 'semanas', 'semanal', 'semanales'],
        ['reglamento', 'reglamentos', 'normas', 'norma', 'normativa', 'lineamientos', 'lineamiento', 'reglas', 'regla'],
        ['coordinador', 'coordinadora', 'coordinacion', 'coordinadores', 'director', 'directora', 'jefe', 'jefa'],
        ['visita', 'visitas', 'salida', 'salidas', 'viaje', 'viajes', 'excursion', 'excursiones'],
        ['accidente', 'accidentes', 'accidento', 'lastime', 'lastimo', 'lesion', 'cai'],
        ['imprimir', 'impresion', 'impresiones', 'impresora', 'imprimo', 'copias', 'copiadora', 'fotocopias'],
        ['plataforma', 'plataformas', 'portal', 'sistema', 'sescolar'],
        ['contrasena', 'contrasenas', 'password', 'contrasea'],
        ['olvide', 'olvido', 'olvidaron', 'olvidar', 'recuperar', 'restablecer', 'bloquearon', 'bloqueada'],
        ['institucional', 'institucionales'],
        ['intercambio', 'intercambios', 'movilidad', 'extranjero'],
        ['queja', 'quejas', 'quejarme', 'quejo', 'sugerencia', 'sugerencias', 'reclamo', 'buzon'],
        ['perdido', 'perdidos', 'perdida', 'perdidas', 'perdi', 'perdio', 'extravie', 'extraviado', 'extravio'],
        ['estacionamiento', 'estacionar', 'estaciono', 'estacionarme', 'parking', 'cochera'],
        ['carro', 'carros', 'coche', 'coches', 'auto', 'autos', 'moto', 'motos', 'bicicleta', 'bici', 'vehiculo'],
        ['discapacidad', 'discapacitado', 'discapacitada', 'rampa', 'rampas', 'accesibilidad', 'accesible', 'accesibles', 'silla', 'ruedas', 'inclusion'],
        ['acoso', 'acosando', 'acosa', 'acosan', 'acosador', 'hostigamiento', 'hostiga', 'violencia', 'bullying', 'abuso', 'genero'],
        ['denunciar', 'denuncia', 'denuncio', 'reportar'],
        // 'contingencia' y 'continuidad' comparten raíz: el grupo se normaliza a 'huracan'.
        ['huracan', 'huracanes', 'lluvia', 'lluvias', 'tormenta', 'tormentas', 'ciclon', 'clima', 'contingencia', 'inundacion', 'sismo', 'temblor', 'llueve'],
        ['embarazada', 'embarazo', 'embarazadas', 'maternidad', 'paternidad', 'bebe', 'hijo', 'hijos', 'hija', 'hijas', 'madre', 'lactancia'],
        ['expulsan', 'expulsion', 'expulsado', 'corren', 'corrieron'],
        ['maestro', 'maestra', 'maestros', 'profe', 'profesor', 'profesora', 'docente', 'docentes', 'profes'],
    ],

    // Palabras que no aportan significado a la búsqueda.
    'vacias' => [
        'a', 'al', 'algo', 'algun', 'alguna', 'ante', 'como', 'con', 'cual', 'cuales', 'de', 'del', 'donde', 'el', 'ella',
        'en', 'entre', 'es', 'esa', 'ese', 'eso', 'esta', 'este', 'esto', 'estan', 'favor', 'fue', 'ha', 'hay', 'hola',
        'la', 'las', 'le', 'les', 'lo', 'los', 'me', 'mi', 'mis', 'muy', 'no', 'nos', 'o', 'para', 'pero', 'por',
        'porque', 'puedo', 'puede', 'pueden', 'que', 'quiero', 'quisiera', 'saber', 'se', 'ser', 'si', 'sin', 'sobre',
        'son', 'su', 'sus', 'tambien', 'te', 'tengo', 'tiene', 'tienen', 'tu', 'un', 'una', 'unos', 'uno', 'y', 'ya',
        'yo', 'buenas', 'buenos', 'dias', 'tardes', 'noches', 'gracias', 'oye', 'disculpa', 'pregunta', 'duda',
        'universidad', 'utypcb', 'coyuca', 'benitez', 'hacer', 'hago', 'debo', 'tengo', 'necesitaria', 'informacion',
        'dime', 'digan', 'podrian', 'podria', 'gustaria', 'ustedes', 'aqui', 'alla', 'ahi', 'mas', 'menos', 'cosa',
        // Agregadas en el borrador de la base de conocimiento.
        'oigan', 'buen', 'buena', 'alguien', 'sabe', 'saben', 'porfavor', 'ok', 'okey', 'hey', 'joven', 'senorita',
        'escuela', 'nomas', 'nada', 'ahorita', 'hace', 'haga', 'van', 'va', 'onda', 'pues', 'ps', 'bueno', 'entonces', 'osea', 'tons',
    ],

    // Palabra coloquial → palabra que aparece en el nombre oficial de una carrera.
    'alias_carrera' => [
        'sistemas' => 'tecnologias', 'informatica' => 'tecnologias', 'computacion' => 'tecnologias', 'ti' => 'tecnologias',
        'programacion' => 'tecnologias', 'software' => 'tecnologias', 'tics' => 'tecnologias',
        'aduanas' => 'comercio', 'logistica' => 'comercio', 'aduana' => 'comercio',
        'solar' => 'energia', 'energias' => 'energia', 'renovables' => 'energia', 'sostenible' => 'energia',
        'fauna' => 'recursos', 'ambiental' => 'recursos', 'naturales' => 'recursos', 'forestal' => 'recursos',
        'civil' => 'civil', 'construccion' => 'civil',
        // Agregados en el borrador (nombres de TSU y temas publicados en plan_estudios.php y trípticos).
        'aduanal' => 'comercio', 'arancelaria' => 'comercio', 'logisticos' => 'comercio', 'exportacion' => 'comercio',
        'fotovoltaica' => 'energia', 'fotovoltaicos' => 'energia', 'paneles' => 'energia',
        'videojuegos' => 'tecnologias', 'desarrollador' => 'tecnologias', 'tecnologia' => 'tecnologias',
        'ecoturismo' => 'recursos', 'silvestre' => 'recursos',
        'topografia' => 'civil', 'civiles' => 'civil',
    ],

    // Carreras que la universidad NO ofrece. Confirmar la lista con Servicios Escolares.
    // Si alguien pregunta por ellas, se responde con claridad en vez de mostrar otro tema.
    // Enfermería se quitó de esta lista: el sitio (home.php) la anuncia como "Próximamente" y la
    // responde la pregunta frecuente carrera-enfermeria-proximamente.
    'no_ofertadas' => [
        'medicina' => 'Medicina', 'derecho' => 'Derecho', 'psicologia' => 'Psicología',
        'contaduria' => 'Contaduría', 'arquitectura' => 'Arquitectura', 'odontologia' => 'Odontología',
        'veterinaria' => 'Medicina Veterinaria', 'gastronomia' => 'Gastronomía', 'nutricion' => 'Nutrición',
        'pedagogia' => 'Pedagogía', 'mecatronica' => 'Mecatrónica',
        // Agregadas: no aparecen entre los 5 programas de plan_estudios.php.
        'administracion' => 'Administración', 'turismo' => 'Turismo', 'agronomia' => 'Agronomía',
        'criminologia' => 'Criminología', 'contabilidad' => 'Contabilidad', 'industrial' => 'Ingeniería Industrial',
        'electronica' => 'Electrónica', 'mercadotecnia' => 'Mercadotecnia',
    ],
];
