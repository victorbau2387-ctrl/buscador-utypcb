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
        'constancia de estudios' => 'constancia', 'kardex' => 'historial',
    ],

    // Grupos de sinónimos: cualquier palabra del grupo se convierte en la primera.
    'sinonimos' => [
        ['costo', 'costos', 'precio', 'precios', 'cuesta', 'cuestan', 'cobran', 'pagar', 'pago', 'pagos', 'cuota', 'cuotas', 'colegiatura', 'colegiaturas', 'mensualidad', 'arancel', 'aranceles', 'tarifa'],
        ['admision', 'ingreso', 'ingresar', 'entrar', 'aspirante', 'aspirantes', 'convocatoria', 'preinscripcion'],
        ['inscripcion', 'inscribirme', 'inscribir', 'inscribo', 'inscripciones', 'matricula', 'registrarme'],
        ['reinscripcion', 'reinscribirme', 'reinscribir', 'reinscripciones'],
        ['carrera', 'carreras', 'licenciatura', 'licenciaturas', 'ingenieria', 'ingenierias', 'oferta', 'programa', 'programas', 'estudiar'],
        ['requisito', 'requisitos', 'documento', 'documentos', 'papeles', 'papeleria', 'necesito', 'piden'],
        ['beca', 'becas', 'apoyo', 'apoyos', 'ayuda', 'descuento', 'descuentos'],
        ['horario', 'horarios', 'hora', 'horas', 'turno', 'turnos', 'abren', 'cierran', 'atienden'],
        ['ubicacion', 'direccion', 'domicilio', 'mapa', 'llegar', 'ubicados'],
        ['contacto', 'telefono', 'correo', 'email', 'comunicarme', 'llamar', 'whatsapp', 'contactar'],
        ['calendario', 'fechas', 'fecha', 'cuando', 'periodo', 'periodos'],
        ['vacaciones', 'vacacion', 'descanso', 'asueto', 'puente'],
        ['titulacion', 'titularme', 'titulo', 'titularse', 'cedula'],
        ['deporte', 'deportes', 'futbol', 'basquet', 'voleibol', 'equipo', 'equipos', 'torneo', 'selectivo'],
        ['noticia', 'noticias', 'aviso', 'avisos', 'novedades', 'comunicado'],
        ['tramite', 'tramites', 'solicitar', 'sacar', 'saco', 'tramitar', 'gestionar'],
        ['transparencia', 'rendicion'],
        ['examen', 'examenes', 'prueba', 'evaluacion', 'exani'],
        ['ficha', 'fichas'],
        ['triptico', 'tripticos', 'folleto', 'folletos', 'brochure'],
        ['medico', 'consultorio', 'salud'],
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
    ],

    // Palabra coloquial → palabra que aparece en el nombre oficial de una carrera.
    'alias_carrera' => [
        'sistemas' => 'tecnologias', 'informatica' => 'tecnologias', 'computacion' => 'tecnologias', 'ti' => 'tecnologias',
        'programacion' => 'tecnologias', 'software' => 'tecnologias', 'tics' => 'tecnologias',
        'aduanas' => 'comercio', 'logistica' => 'comercio', 'aduana' => 'comercio',
        'solar' => 'energia', 'energias' => 'energia', 'renovables' => 'energia', 'sostenible' => 'energia',
        'fauna' => 'recursos', 'ambiental' => 'recursos', 'naturales' => 'recursos', 'forestal' => 'recursos',
        'civil' => 'civil', 'construccion' => 'civil',
    ],

    // Carreras que la universidad NO ofrece. Confirmar la lista con Servicios Escolares.
    // Si alguien pregunta por ellas, se responde con claridad en vez de mostrar otro tema.
    'no_ofertadas' => [
        'enfermeria' => 'Enfermería', 'medicina' => 'Medicina', 'derecho' => 'Derecho', 'psicologia' => 'Psicología',
        'contaduria' => 'Contaduría', 'arquitectura' => 'Arquitectura', 'odontologia' => 'Odontología',
        'veterinaria' => 'Medicina Veterinaria', 'gastronomia' => 'Gastronomía', 'nutricion' => 'Nutrición',
        'pedagogia' => 'Pedagogía', 'mecatronica' => 'Mecatrónica',
    ],
];
