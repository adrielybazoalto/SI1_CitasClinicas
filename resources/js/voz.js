/**
 * RNF20 · Comandos de voz con Web Speech API (RF42–RF44).
 * Un solo micrófono flotante para todos los roles (<x-voz-global>):
 * <div data-voz data-destinos='{"CU13": url, ...}' data-rutas='{"inicio": url}'>
 *   - Navegación: frases naturales («reporte de citas por médico», «quiero
 *     sacar una cita») solo hacia los CU que el servidor permite para el rol.
 *   - Dictado: las pantallas declaran sus campos con <div data-voz-campos>
 *     (<x-voz>): CU12/CU13 (especialidad, médico, fecha, motivo), CU20
 *     (médico, fecha, estado) y CU23 (tipo, rango, médico, especialidad,
 *     estado). Se llenan por campo o con una sola frase compuesta.
 *   - NUNCA envía el formulario: la usuaria revisa y confirma con el teclado o el ratón.
 *   - Sin soporte del navegador, se avisa y se sigue con teclado y ratón.
 */

const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const DIAS = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
const NUMEROS = { uno: 1, primero: 1, dos: 2, tres: 3, cuatro: 4, cinco: 5, seis: 6, siete: 7, ocho: 8, nueve: 9, diez: 10 };

/** Ejemplo de dictado de cada campo, para los avisos del micrófono. */
const EJEMPLOS_CAMPO = {
    tipo: '«reporte de citas por médico»',
    desde: '«desde el 1 de octubre hasta hoy»',
    especialidad: '«especialidad pediatría»',
    medico: '«médico Pérez»',
    fecha: '«fecha mañana»',
    motivo: '«motivo dolor de cabeza»',
    estado: '«estado confirmadas»',
};

/** Lo que se pide: cita, ficha, turno o consulta (en singular o plural). */
const OBJETO_CITA = /\b(citas?|fichas?|turnos?|consultas?|reservas?|hora)\b/;

/**
 * Intenciones de navegación por voz, de la más específica a la más general,
 * para que «reporte de citas canceladas» sea CU23 y «cancelar la cita que
 * reservé» sea CU16 y no CU13.
 *  - fuerte: basta con la acción («quiero pagar», «abrir la bitácora»).
 *  - debil + objeto: la acción solo cuenta si se nombra lo pedido («necesito una ficha»).
 * Las acciones que también son adjetivos o sustantivos («canceladas», «cobros»,
 * «programadas») se comparan solo como verbo. El servidor decide cuáles se
 * ofrecen a cada rol (MenuModulos::destinosVoz).
 */
const INTENCIONES = [
    { cu: 'CU23', nombre: 'Generar reportes', fuerte: /\b(reportes?|informes?|estadisticas?)\b/ },
    { cu: 'CU24', nombre: 'Consultar bitácora', fuerte: /\b(bitacora|auditoria)\b/ },
    { cu: 'CU15', nombre: 'Reprogramar cita',
        fuerte: /\b(reprogram|posterg|aplaz)\w*/,
        debil: /\b(cambi|mov|pas)\w*/, objeto: /\b(otro dia|otra fecha|otra hora|de fecha|de hora|de dia)\b/ },
    { cu: 'CU16', nombre: 'Cancelar cita',
        fuerte: /\b(cancel(ar|a|e|o|en|arla|arlo|ame)|anul(ar|a|e|o|en|arla|arlo|ame))\b/,
        debil: /\b(elimin|borr|quit|suspend|dar de baja|ya no (voy|puedo|ire))\w*/, objeto: OBJETO_CITA },
    { cu: 'CU19', nombre: 'Cobrar en mostrador', fuerte: /\b(cobr(ar|a|e|ale|arle|amos|ando)|cobro en mostrador)\b/ },
    { cu: 'CU14', nombre: 'Pagar cita en línea',
        fuerte: /\b(pag(ar|o|os|ue|a|as|amos|ando)\w*|abon\w*)\b/,
        debil: /\b(qr|tarjeta|deuda|debo|monto)\b/, objeto: OBJETO_CITA },
    { cu: 'CU18', nombre: 'Gestionar caja', fuerte: /\b(caja|arqueo)\b/ },
    { cu: 'CU12', nombre: 'Consultar citas disponibles', fuerte: /\b(disponib\w*|libres?)\b/ },
    // «agenda» es la agenda médica (CU20); «agendar»/«agéndame» es programar (CU13).
    { cu: 'CU20', nombre: 'Consultar agenda médica', fuerte: /\bagenda\b/ },
    { cu: 'CU13', nombre: 'Programar cita',
        fuerte: /\b(sac|reserv|separ)\w*|\bagend(ar|ame|arme|arle|e|o|en)\b|\bprogram(ar|a|e|o|ame|arme|arle)\b/,
        // Verbos completos: una raíz corta («ped») también encontraría «pediatría».
        debil: /\b(nuev[oa]|pedir|pido|pida|pide|solicit\w*|quier\w*|necesit\w*|hacer|hago|haga|tomar|tomo|consig\w*|consegu\w*|dame|deme|marc(ar|o|a)|apart(ar|o|a))\b/, objeto: OBJETO_CITA },
    { cu: 'CU17', nombre: 'Registrar asistencia', fuerte: /\b(asistencia|llegada|llego)\b/ },
    { cu: 'CU22', nombre: 'Consultar historial', fuerte: /\b(historial\w*|historia clinica|expediente)\b/ },
    { cu: 'CU06', nombre: 'Antecedentes clínicos', fuerte: /\b(antecedentes?|alergias?)\b/ },
    { cu: 'CU05', nombre: 'Gestionar pacientes', fuerte: /\b(registrar|nuevo|buscar|gestionar|lista de|ver|editar)\s+(un |una |al |el |los |a )?pacientes?\b/ },
    { cu: 'CU10', nombre: 'Indisponibilidad médica', fuerte: /\b(indisponib\w*|vacaciones|ausencias?)\b/ },
    { cu: 'CU09', nombre: 'Gestionar horarios', fuerte: /\bhorarios?\b/ },
    { cu: 'CU08', nombre: 'Gestionar médicos', fuerte: /\bmedicos\b|\b(gestionar|registrar|nuevo)\s+medicos?\b/ },
    { cu: 'CU07', nombre: 'Gestionar especialidades', fuerte: /\bespecialidades\b/ },
    { cu: 'CU11', nombre: 'Gestionar tarifario', fuerte: /\b(tarifas?|tarifario|precios?)\b/ },
    { cu: 'CU02', nombre: 'Gestionar usuarios', fuerte: /\busuarios?\b/ },
    { cu: 'CU03', nombre: 'Gestionar roles y permisos', fuerte: /\b(roles?|permisos?)\b/ },
    { cu: 'CU04', nombre: 'Mi perfil',
        fuerte: /\b(perfil|mis datos|mi cuenta|mi informacion|datos personales)\b/,
        debil: /\b(cambi|actualiz|edit|modific|ver|corregi)\w*/,
        objeto: /\b(contrasena|clave|telefono|celular|correo|direccion|contacto de emergencia|recordatorios?)\b/ },
];

/** minúsculas y sin tildes, para comparar lo dicho con las opciones. */
const normalizar = (texto) => texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[.,;:!¡?¿]/g, ' ').replace(/\s+/g, ' ').trim();

const aIso = (fecha) => [fecha.getFullYear(), String(fecha.getMonth() + 1).padStart(2, '0'), String(fecha.getDate()).padStart(2, '0')].join('-');

/** "hoy", "mañana", "pasado mañana", "lunes", "15 de noviembre", "15/11", "el 15". */
function interpretarFecha(texto) {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const t = normalizar(texto);

    if (t.includes('anteayer')) return aIso(new Date(hoy.getTime() - 2 * 864e5));
    if (t.includes('ayer')) return aIso(new Date(hoy.getTime() - 864e5));
    if (t.includes('pasado manana')) return aIso(new Date(hoy.getTime() + 2 * 864e5));
    if (t.includes('manana')) return aIso(new Date(hoy.getTime() + 864e5));
    if (t.includes('hoy')) return aIso(hoy);

    const dia = DIAS.findIndex((d) => t.includes(d));
    if (dia >= 0) {
        const fecha = new Date(hoy);
        fecha.setDate(hoy.getDate() + ((dia - hoy.getDay() + 7) % 7 || 7));
        return aIso(fecha);
    }

    let m = t.match(/(\d{1,2})\s*\/\s*(\d{1,2})(?:\s*\/\s*(\d{2,4}))?/);
    if (m) {
        const anio = m[3] ? (m[3].length === 2 ? 2000 + Number(m[3]) : Number(m[3])) : hoy.getFullYear();
        return aIso(new Date(anio, Number(m[2]) - 1, Number(m[1])));
    }

    m = t.match(/\b(\d{1,2}|uno|primero|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez)\b(?:\s+de\s+([a-z]+))?/);
    if (m) {
        const numero = NUMEROS[m[1]] ?? Number(m[1]);
        const mes = m[2] ? MESES.indexOf(m[2]) : hoy.getMonth();
        if (mes < 0 || numero < 1 || numero > 31) return null;
        const fecha = new Date(hoy.getFullYear(), mes, numero);
        // Siempre hacia adelante: si ya pasó, el mismo día del año (o del mes) siguiente.
        if (fecha < hoy) {
            m[2] ? fecha.setFullYear(fecha.getFullYear() + 1) : fecha.setMonth(fecha.getMonth() + 1);
        }
        return aIso(fecha);
    }

    return null;
}

/**
 * CU de la frase natural del paciente («quiero sacar una cita» → CU13),
 * solo entre los destinos permitidos para el rol. null si no se reconoce.
 */
function detectarIntencion(texto, destinos) {
    const t = normalizar(texto);
    // Gana la primera que coincide; si el rol no la tiene, no se cae en otra («pagar» nunca abre CU13).
    const intencion = INTENCIONES.find((i) => i.fuerte.test(t) || (i.debil && i.debil.test(t) && i.objeto.test(t)));

    return intencion && destinos[intencion.cu] ? intencion : null;
}

/* ------------------------------------------------------------------
 | Frase compuesta en CU13: «quiero una ficha de cardiología con la
 | doctora Ana Paz para mañana» → especialidad, médico y fecha de una vez.
 * ------------------------------------------------------------------ */

const PALABRAS_VACIAS = new Set(['de', 'del', 'la', 'las', 'los', 'el', 'y', 'en', 'con', 'para', 'dr', 'dra', 'doctor', 'doctora', 'lic', 'min']);

/** Palabras con sentido de un texto («Cardiología · 30 min» → ['cardiologia']). */
const palabras = (texto) => normalizar(texto.split('·')[0]).split(' ').filter((p) => p.length >= 3 && !PALABRAS_VACIAS.has(p) && !/^\d+$/.test(p));

/**
 * Opción de especialidad nombrada en la frase. Compara raíces de 5 letras para
 * aceptar «cardiólogo» → Cardiología o «médico general» → Medicina General.
 * Gana la que coincide con más palabras.
 */
function detectarEspecialidad(texto, opciones) {
    const t = normalizar(texto);
    let mejor = null;
    let puntos = 0;
    for (const opcion of opciones) {
        const nombre = palabras(opcion.textContent);
        if (!opcion.value || !nombre.length) continue;
        const coinciden = nombre.every((p) => new RegExp('\\b' + p.slice(0, 5)).test(t));
        if (coinciden && nombre.length > puntos) { mejor = opcion; puntos = nombre.length; }
    }
    return mejor;
}

/**
 * Opción del médico nombrado: la que tiene más palabras de su nombre dichas
 * completas («doctora Ana Paz» gana a «Ana Flores»). Si empatan, no se elige.
 */
function detectarMedico(texto, opciones) {
    const dichas = new Set(normalizar(texto).split(' '));
    const puntajes = opciones
        .filter((o) => o.value)
        .map((o) => ({ opcion: o, puntos: palabras(o.textContent).filter((p) => dichas.has(p)).length }))
        .filter((p) => p.puntos > 0)
        .sort((a, b) => b.puntos - a.puntos);

    if (!puntajes.length || (puntajes[1] && puntajes[1].puntos === puntajes[0].puntos)) return null;
    return puntajes[0].opcion;
}

/**
 * Fecha dentro de una frase, solo con referencias claras: hoy, mañana,
 * pasado mañana, un día de la semana, «15 de noviembre», «15/11» o «el 15».
 * «en la mañana» es la mañana del día, no mañana.
 */
function extraerFecha(texto) {
    const t = normalizar(texto).replace(/\b(en|por|de|a) la manana\b/g, ' ');
    const m = t.match(/\bpasado manana\b/)
        ?? t.match(/\b\d{1,2}\s*\/\s*\d{1,2}(?:\s*\/\s*\d{2,4})?\b/)
        ?? t.match(new RegExp('\\b(\\d{1,2}|primero|uno|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez)\\s+de\\s+(' + MESES.join('|') + ')\\b'))
        ?? t.match(/\b(manana|hoy)\b/)
        ?? t.match(new RegExp('\\b(' + DIAS.join('|') + ')\\b'));
    if (m) return interpretarFecha(m[0]);

    const dia = t.match(/\b(?:el|dia|para el)\s+(\d{1,2}|primero)\b/);
    return dia ? interpretarFecha(dia[1]) : null;
}

/* ------------------------------------------------------------------
 | CU23 · Reportes por voz: tipo de reporte, rango de fechas y filtros.
 * ------------------------------------------------------------------ */

/**
 * Cómo se nombra cada tipo de reporte (claves de ReporteService::TIPOS).
 * En orden: «ingresos por periodo» es ingresos, no totales por periodo.
 */
const TIPOS_REPORTE = [
    ['ingresos', /\b(ingresos|ganancias|recaudacion|lo cobrado)\b/],
    ['agenda_diaria', /\bagenda (diaria|del dia)\b/],
    ['pacientes', /\bpacientes (registrados|nuevos)\b|\b(reporte|informe|lista|listado) de pacientes\b/],
    ['por_medico', /\bpor medicos?\b/],
    ['por_especialidad', /\bpor especialidad(es)?\b/],
    ['por_estado', /\bpor estados?\b/],
    ['totales_periodo', /\b(totales|atendidas,? canceladas( e inasistencias)?|resumen del periodo|por periodo)\b/],
    ['citas_fecha', /\bcitas programadas\b|\bcitas (de|para|del) (una fecha|hoy|manana|el dia)\b/],
];

/** Tipo de reporte nombrado en la frase (solo si el rol lo tiene en la lista). Devuelve la opción y el texto que lo nombró. */
function detectarTipo(texto, opciones) {
    const t = normalizar(texto);
    for (const [clave, patron] of TIPOS_REPORTE) {
        const m = t.match(patron);
        const opcion = m && opciones.find((o) => o.value === clave);
        if (opcion) return { opcion, texto: m[0] };
    }
    return null;
}

const NUMERO_DIA = '(\\d{1,2}|primero|uno|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez)';

/**
 * Un extremo de un rango: «hoy», «ayer», «15 de octubre», «15/10/2026» o
 * solo «1» (con el mes del otro extremo). No se mueve al futuro: en un
 * reporte «15 de octubre» es el de este año aunque ya haya pasado.
 */
function puntoFecha(texto, mesContexto = null) {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const t = normalizar(texto);
    const dias = (n) => ({ iso: aIso(new Date(hoy.getTime() + n * 864e5)), mes: null });

    if (/^hoy\b/.test(t)) return dias(0);
    if (/^anteayer\b/.test(t)) return dias(-2);
    if (/^ayer\b/.test(t)) return dias(-1);
    if (/^pasado manana\b/.test(t)) return dias(2);
    if (/^manana\b/.test(t)) return dias(1);

    const m = t.match(new RegExp('^' + NUMERO_DIA + '(?:\\s*/\\s*(\\d{1,2})(?:\\s*/\\s*(\\d{2,4}))?)?(?:\\s+de\\s+(' + MESES.join('|') + '))?(?:\\s+(?:de|del)\\s+(\\d{4}))?\\b'));
    if (!m) return null;
    const dia = NUMEROS[m[1]] ?? Number(m[1]);
    const mes = m[2] ? Number(m[2]) - 1 : m[4] ? MESES.indexOf(m[4]) : (mesContexto ?? hoy.getMonth());
    const anioDicho = m[3] ?? m[5];
    const anio = anioDicho ? (anioDicho.length === 2 ? 2000 + Number(anioDicho) : Number(anioDicho)) : hoy.getFullYear();
    if (dia < 1 || dia > 31 || mes < 0 || mes > 11) return null;

    return { iso: aIso(new Date(anio, mes, dia)), mes };
}

/**
 * Rango de fechas de un reporte: «desde el 1 de octubre hasta hoy», «del 1
 * al 15 de noviembre», «entre el 3 y el 10», «el mes pasado», «esta semana»,
 * «los últimos 7 días», «en octubre», o un solo día («ayer», «15/10», «el
 * lunes pasado»). Devuelve {desde, hasta} o null.
 */
function extraerRango(texto) {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const t = normalizar(texto).replace(/\b(en|por|de|a) la manana\b/g, ' ');
    const rango = (a, b) => ({ desde: aIso(a), hasta: aIso(b) });
    const lunes = (fecha) => { const f = new Date(fecha); f.setDate(f.getDate() - ((f.getDay() + 6) % 7)); return f; };

    // «desde X hasta Y», «del X al Y», «entre X y Y»: se lee primero Y para heredar su mes («del 1 al 15 de noviembre»).
    const m = t.match(/\b(?:desde|del|entre)\s+(?:el\s+|la\s+)?(.+?)\s+(?:hasta|al|y)\s+(?:el\s+|la\s+)?(.+)$/);
    if (m) {
        const hasta = puntoFecha(m[2]);
        const desde = hasta && puntoFecha(m[1], hasta.mes);
        if (desde && hasta) return desde.iso <= hasta.iso ? { desde: desde.iso, hasta: hasta.iso } : { desde: hasta.iso, hasta: desde.iso };
    }

    const ultimos = t.match(/\bultimos?\s+(\d{1,3}|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez)\s+dias\b/);
    if (ultimos) return rango(new Date(hoy.getTime() - ((NUMEROS[ultimos[1]] ?? Number(ultimos[1])) - 1) * 864e5), hoy);
    if (/\b(esta semana|semana actual)\b/.test(t)) return rango(lunes(hoy), hoy);
    if (/\b(la )?semana pasada\b/.test(t)) { const l = lunes(new Date(hoy.getTime() - 7 * 864e5)); return rango(l, new Date(l.getTime() + 6 * 864e5)); }
    if (/\b(este mes|mes actual)\b/.test(t)) return rango(new Date(hoy.getFullYear(), hoy.getMonth(), 1), hoy);
    if (/\b(el )?mes pasado\b/.test(t)) return rango(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1), new Date(hoy.getFullYear(), hoy.getMonth(), 0));
    if (/\b(este ano|ano actual)\b/.test(t)) return rango(new Date(hoy.getFullYear(), 0, 1), hoy);
    if (/\b(el )?ano pasado\b/.test(t)) return rango(new Date(hoy.getFullYear() - 1, 0, 1), new Date(hoy.getFullYear() - 1, 11, 31));

    // Un día suelto (antes que el mes entero: «15 de octubre» no es todo octubre).
    const dia = t.match(new RegExp('\\b(?:' + NUMERO_DIA + '\\s*/\\s*\\d{1,2}(?:\\s*/\\s*\\d{2,4})?|' + NUMERO_DIA + '\\s+de\\s+(?:' + MESES.join('|') + ')(?:\\s+de[l]?\\s+\\d{4})?|anteayer|ayer|hoy|pasado manana|manana)\\b'));
    if (dia) { const p = puntoFecha(dia[0]); if (p) return { desde: p.iso, hasta: p.iso }; }

    // Mes entero: «en octubre», «de noviembre de 2026». Un mes aún por venir es el del año pasado.
    const mes = t.match(new RegExp('\\b(' + MESES.join('|') + ')(?:\\s+(?:de|del)\\s+(\\d{4}))?\\b'));
    if (mes) {
        const n = MESES.indexOf(mes[1]);
        const anio = mes[2] ? Number(mes[2]) : (n > hoy.getMonth() ? hoy.getFullYear() - 1 : hoy.getFullYear());
        return rango(new Date(anio, n, 1), new Date(anio, n + 1, 0));
    }

    // Día de la semana: «el lunes pasado» hacia atrás; «el lunes» el próximo.
    const semana = t.match(new RegExp('\\b(' + DIAS.join('|') + ')( pasado)?\\b'));
    if (semana) {
        const objetivo = DIAS.indexOf(semana[1]);
        if (semana[2]) { const f = new Date(hoy); f.setDate(hoy.getDate() - (((hoy.getDay() - objetivo + 7) % 7) || 7)); return rango(f, f); }
        const iso = interpretarFecha(semana[1]);
        return { desde: iso, hasta: iso };
    }

    const suelto = t.match(/\b(?:el|dia)\s+(\d{1,2}|primero)\b/);
    if (suelto) { const p = puntoFecha(suelto[1]); if (p) return { desde: p.iso, hasta: p.iso }; }

    return null;
}

/** Elige en un <select> la opción cuyo texto contiene lo dicho. */
function elegirOpcion(select, dicho) {
    const buscado = normalizar(dicho).replace(/^(el|la|los|las|doctor|doctora|dr|dra)\s+/g, '').trim();
    if (!buscado) return null;

    // Solo opciones habilitadas: en CU13 los médicos de otra especialidad están deshabilitados.
    const opciones = [...select.options].filter((o) => o.value && !o.disabled);
    const opcion = opciones.find((o) => normalizar(o.textContent).includes(buscado))
        ?? opciones.find((o) => buscado.split(' ').every((p) => normalizar(o.textContent).includes(p)))
        ?? detectarEspecialidad(buscado, opciones); // por raíz: «canceladas» → Cancelada

    if (!opcion || select.disabled) return null;
    select.value = opcion.value;
    select.dispatchEvent(new Event('change', { bubbles: true }));

    return opcion.textContent.trim();
}

function iniciar(caja) {
    const rutas = JSON.parse(caja.dataset.rutas || '{}');
    // Campos propios y los que declara la pantalla con <div data-voz-campos> (así el
    // micrófono flotante también dicta especialidad, médico, fecha y motivo en CU13).
    const campos = Object.assign(
        JSON.parse(caja.dataset.campos || '{}'),
        ...[...document.querySelectorAll('[data-voz-campos]')].map((m) => JSON.parse(m.dataset.vozCampos || '{}')),
    );
    // {"CU13": url, ...}: destinos que entiende detectarIntencion (frases naturales).
    const destinos = JSON.parse(caja.dataset.destinos || '{}');
    const ejemplos = [
        ...Object.keys(EJEMPLOS_CAMPO).filter((c) => campos[c]).map((c) => EJEMPLOS_CAMPO[c]),
        ...INTENCIONES.filter((i) => destinos[i.cu]).map((i) => '«' + i.nombre.toLowerCase() + '»'),
    ].slice(0, 5).join(', ') || '«abrir agenda»';
    const boton = caja.querySelector('[data-voz-boton]');
    const estado = caja.querySelector('[data-voz-estado]');
    const oido = caja.querySelector('[data-voz-texto]');
    const Reconocimiento = window.SpeechRecognition || window.webkitSpeechRecognition;

    const avisar = (mensaje, tipo = 'info') => {
        estado.textContent = mensaje;
        estado.className = 'text-xs ' + (tipo === 'error' ? 'text-rose-600' : tipo === 'ok' ? 'text-emerald-700' : 'text-gray-500');
    };

    if (!Reconocimiento) {
        const mensaje = 'Su navegador no admite comandos de voz (use Chrome o Edge). Puede seguir con el teclado y el ratón.';
        boton.disabled = true;
        boton.title = mensaje;
        boton.classList.add('opacity-50', 'cursor-not-allowed');
        avisar(mensaje, 'error');
        return;
    }

    const reconocimiento = new Reconocimiento();
    // Idioma por pantalla: data-lang="es-BO" en el micrófono del portal del paciente.
    reconocimiento.lang = caja.dataset.lang || 'es-ES';
    reconocimiento.interimResults = false;
    reconocimiento.maxAlternatives = 1;
    let escuchando = false;

    const marcar = (activo) => {
        escuchando = activo;
        boton.setAttribute('aria-pressed', String(activo));
        boton.classList.toggle('animate-pulse', activo);
        boton.classList.toggle('bg-rose-600', activo);
        boton.classList.toggle('bg-purple-800', !activo);
        if (activo) avisar('Escuchando… diga, por ejemplo, ' + ejemplos + '.');
        // Para el indicador de escucha (panel del micrófono flotante, <x-voz-global>).
        caja.dispatchEvent(new CustomEvent('voz-estado', { detail: { activo } }));
    };

    boton.addEventListener('click', () => {
        if (escuchando) { reconocimiento.stop(); return; }
        try { reconocimiento.start(); marcar(true); } catch { /* ya estaba iniciado */ }
    });
    reconocimiento.onend = () => marcar(false);
    reconocimiento.onerror = (e) => {
        marcar(false);
        avisar(e.error === 'not-allowed' ? 'Permita el uso del micrófono para usar comandos de voz.' : 'No se entendió. Intente de nuevo o use el teclado.', 'error');
    };
    reconocimiento.onresult = (e) => {
        const dicho = e.results[0][0].transcript;
        oido.textContent = '«' + dicho + '»';
        ejecutar(dicho);
    };

    function llenar(campo, valor) {
        const el = document.getElementById(campos[campo]);
        if (!el) return null;
        if (el.tagName === 'SELECT') return elegirOpcion(el, valor);
        el.value = valor;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        return valor;
    }

    const elegir = (select, opcion) => {
        select.value = opcion.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const nombreOpcion = (opcion) => opcion.textContent.split('·')[0].trim();
    const elemento = (campo) => (campos[campo] ? document.getElementById(campos[campo]) : null);
    const ponerFecha = (input, iso) => {
        input.value = iso;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const fechaVisible = (iso) => iso.split('-').reverse().join('/');

    /**
     * Frase compuesta: extrae en una sola pasada los datos de los campos que
     * declara la pantalla y los aplica en orden.
     *  - CU13: especialidad (filtra a los médicos), médico y fecha (busca las fichas).
     *  - CU23: tipo de reporte, rango «desde… hasta…» (o la fecha), médico,
     *    especialidad y estado. El tipo se elige sin disparar su envío automático.
     * Nunca envía el formulario. Devuelve false si la frase trae menos de
     * `minimo` datos (así «médico Pérez» sigue siendo dictado de un campo).
     */
    function rellenarFrase(dicho, minimo = 1) {
        const selTipo = elemento('tipo');
        const selEspecialidad = elemento('especialidad');
        const selMedico = elemento('medico');
        const selEstado = elemento('estado');
        const inputFecha = elemento('fecha');
        const inputDesde = elemento('desde');
        const inputHasta = elemento('hasta');

        const tipo = selTipo ? detectarTipo(dicho, [...selTipo.options]) : null;
        // Lo que nombró el tipo no cuenta como filtro («atendidas, canceladas…» no es el estado).
        const resto = tipo ? normalizar(dicho).replace(tipo.texto, ' ') : dicho;
        const especialidad = selEspecialidad && !selEspecialidad.disabled ? detectarEspecialidad(resto, [...selEspecialidad.options]) : null;
        const medico = selMedico ? detectarMedico(resto, [...selMedico.options]) : null;
        const estado = selEstado ? detectarEspecialidad(resto, [...selEstado.options]) : null;
        // En reportes las fechas miran al pasado («el mes pasado»); en CU13, al futuro.
        const rango = inputDesde && inputHasta ? extraerRango(resto) : null;
        const fecha = inputFecha ? (inputDesde ? rango?.desde ?? null : extraerFecha(resto)) : null;
        if ([tipo, especialidad, medico, estado, rango, fecha].filter(Boolean).length < minimo) return false;

        const hechos = [];
        const avisos = [];

        if (tipo && selTipo.value !== tipo.opcion.value) {
            selTipo.value = tipo.opcion.value; // sin 'change': su onchange enviaría el formulario
            hechos.push('reporte «' + nombreOpcion(tipo.opcion) + '»');
        }

        if (especialidad) {
            elegir(selEspecialidad, especialidad);
            hechos.push('especialidad ' + nombreOpcion(especialidad));
        }

        if (medico) {
            // Médico sin especialidad dicha: se deduce si atiende una sola de la lista.
            if (medico.disabled && !especialidad && selEspecialidad && !selEspecialidad.disabled) {
                const anterior = [...selEspecialidad.options].find((o) => o.value === selEspecialidad.value);
                const atiende = [...selEspecialidad.options].filter((o) => o.value && (elegir(selEspecialidad, o), !medico.disabled));
                if (atiende.length === 1) {
                    elegir(selEspecialidad, atiende[0]);
                    hechos.push('especialidad ' + nombreOpcion(atiende[0]));
                } else {
                    elegir(selEspecialidad, anterior ?? { value: '' });
                    if (atiende.length > 1) avisos.push(nombreOpcion(medico) + ' atiende varias especialidades: diga cuál.');
                }
            }

            if (!medico.disabled) {
                elegir(selMedico, medico);
                hechos.push('médico ' + nombreOpcion(medico));
            } else if (!avisos.length) {
                avisos.push(nombreOpcion(medico) + ' no atiende ' + (especialidad ? nombreOpcion(especialidad) : 'esa especialidad') + '.');
            }
        } else if (selMedico && /\b(doctor|doctora|dr|dra|medica)\b/.test(normalizar(resto))) {
            avisos.push('No encontré a ese médico en la lista.');
        }

        if (rango) {
            ponerFecha(inputDesde, rango.desde);
            ponerFecha(inputHasta, rango.hasta);
            hechos.push(rango.desde === rango.hasta ? 'fecha ' + fechaVisible(rango.desde) : 'del ' + fechaVisible(rango.desde) + ' al ' + fechaVisible(rango.hasta));
        }
        if (fecha) {
            ponerFecha(inputFecha, fecha);
            if (!rango) hechos.push('fecha ' + fechaVisible(fecha));
        }

        if (estado) {
            elegir(selEstado, estado);
            hechos.push('estado ' + nombreOpcion(estado));
        }

        const cierre = selTipo ? ' Pulse «Generar» para ver el reporte.' : ' Revise los datos antes de confirmar.';
        avisar(
            (hechos.length ? 'Listo: ' + hechos.join(', ') + '.' + cierre : 'No pude aplicar los datos.') + (avisos.length ? ' ' + avisos.join(' ') : ''),
            hechos.length ? 'ok' : 'error',
        );
        return true;
    }

    /** ¿La URL es la pantalla actual? Misma ruta y los mismos parámetros que pide (los filtros de más no cuentan). */
    function esPantallaActual(url) {
        const actual = new URL(window.location.href);
        return url.pathname === actual.pathname && [...url.searchParams].every(([clave, valor]) => actual.searchParams.get(clave) === valor)
            && (url.searchParams.has('accion') || !actual.searchParams.has('accion'));
    }

    function ejecutar(dicho) {
        const t = normalizar(dicho);

        // 1) Navegación (solo rutas permitidas para el rol). Primero la intención de la
        //    frase natural («quiero cancelar mis citas» es CU16 aunque diga «mis citas»);
        //    después las frases fijas de la pantalla («abrir agenda», «historial»).
        //    En reportes, nombrar un tipo («agenda diaria por médico») llena el formulario: no navega.
        const esDictado = /^(especialidad|medico|doctor|doctora|fecha|dia|motivo|estado)\b/.test(t);
        const nombraTipo = elemento('tipo') && detectarTipo(dicho, [...elemento('tipo').options]);
        const intencion = esDictado || nombraTipo ? null : detectarIntencion(dicho, destinos);
        const url = intencion ? new URL(destinos[intencion.cu], window.location.href) : null;
        const enEstaPantalla = url && esPantallaActual(url);
        if (intencion && !enEstaPantalla) {
            avisar('Abriendo «' + intencion.nombre + '»…', 'ok');
            window.location.href = url.href;
            return;
        }

        // 2) Frase compuesta en la pantalla actual: «ficha de cardiología con la doctora Paz para mañana».
        //    Si empieza como dictado de un campo («médico general con la doctora Rivero el
        //    viernes»), solo cuenta como compuesta con 2 datos o más; «motivo …» se dicta tal cual.
        if (!t.startsWith('motivo') && rellenarFrase(dicho, esDictado ? 2 : 1)) return;

        // Ya está en esa pantalla: no se recarga (se perdería lo elegido); se sugiere dictar.
        if (enEstaPantalla) {
            avisar('Ya está en «' + intencion.nombre + '». Puede dictar: ' + ejemplos + '.', 'ok');
            return;
        }
        const destino = esDictado ? null : Object.keys(rutas).find((frase) => t.includes(normalizar(frase)));
        if (destino) {
            avisar('Abriendo «' + destino + '»…', 'ok');
            window.location.href = rutas[destino];
            return;
        }

        // 3) Dictado de un campo: "especialidad cardiología", "médico Pérez", "fecha mañana", "motivo dolor de cabeza".
        const m = t.match(/^(especialidad|medico|doctor|doctora|fecha|dia|motivo|estado)\s+(.+)$/);
        if (m) {
            const campo = { doctor: 'medico', doctora: 'medico', dia: 'fecha' }[m[1]] ?? m[1];
            if (!campos[campo]) { avisar('En esta pantalla no hay campo «' + campo + '».', 'error'); return; }

            let valor = m[2];
            if (campo === 'fecha') {
                valor = interpretarFecha(m[2]);
                if (!valor) { avisar('No entendí la fecha. Diga, por ejemplo, «fecha mañana» o «fecha 15 de noviembre».', 'error'); return; }
            } else if (campo === 'motivo') {
                valor = dicho.replace(/^\s*motivo\s*/i, '');
            }

            const puesto = llenar(campo, valor);
            puesto
                ? avisar('Listo: ' + campo + ' = ' + puesto + '. Revise y confirme con el botón.', 'ok')
                : avisar('No encontré «' + m[2] + '» en ' + campo + '.', 'error');
            return;
        }

        avisar('No entendí qué desea hacer. Pruebe con: ' + ejemplos + '.', 'error');
    }
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-voz]').forEach(iniciar);
}

export { detectarEspecialidad, detectarIntencion, detectarMedico, detectarTipo, extraerFecha, extraerRango, interpretarFecha, normalizar };
