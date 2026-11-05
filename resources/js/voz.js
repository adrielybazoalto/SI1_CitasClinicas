/**
 * RNF20 · Comandos de voz con Web Speech API (RF42–RF44).
 * Pantallas: CU12/CU13 (citas disponibles y programar) y CU20 (agenda).
 *
 * <div data-voz data-rutas='{"agenda": "/atencion/agenda", ...}' data-campos='{"fecha": "sel_fecha", ...}'>
 *   - Navegación: solo a las rutas que el servidor permite para el rol.
 *   - Dictado: llena especialidad, médico, fecha, motivo o estado; NUNCA envía
 *     el formulario: la usuaria revisa y confirma con el teclado o el ratón.
 *   - Sin soporte del navegador, se avisa y se sigue con teclado y ratón.
 */

const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const DIAS = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
const NUMEROS = { uno: 1, primero: 1, dos: 2, tres: 3, cuatro: 4, cinco: 5, seis: 6, siete: 7, ocho: 8, nueve: 9, diez: 10 };

/** minúsculas y sin tildes, para comparar lo dicho con las opciones. */
const normalizar = (texto) => texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[.,;:!¡?¿]/g, ' ').replace(/\s+/g, ' ').trim();

const aIso = (fecha) => [fecha.getFullYear(), String(fecha.getMonth() + 1).padStart(2, '0'), String(fecha.getDate()).padStart(2, '0')].join('-');

/** "hoy", "mañana", "pasado mañana", "lunes", "15 de noviembre", "15/11", "el 15". */
function interpretarFecha(texto) {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const t = normalizar(texto);

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

/** Elige en un <select> la opción cuyo texto contiene lo dicho. */
function elegirOpcion(select, dicho) {
    const buscado = normalizar(dicho).replace(/^(el|la|los|las|doctor|doctora|dr|dra)\s+/g, '').trim();
    if (!buscado) return null;

    const opcion = [...select.options].find((o) => o.value && normalizar(o.textContent).includes(buscado))
        ?? [...select.options].find((o) => o.value && buscado.split(' ').every((p) => normalizar(o.textContent).includes(p)));

    if (!opcion || select.disabled) return null;
    select.value = opcion.value;
    select.dispatchEvent(new Event('change', { bubbles: true }));

    return opcion.textContent.trim();
}

function iniciar(caja) {
    const rutas = JSON.parse(caja.dataset.rutas || '{}');
    const campos = JSON.parse(caja.dataset.campos || '{}');
    const boton = caja.querySelector('[data-voz-boton]');
    const estado = caja.querySelector('[data-voz-estado]');
    const oido = caja.querySelector('[data-voz-texto]');
    const Reconocimiento = window.SpeechRecognition || window.webkitSpeechRecognition;

    const avisar = (mensaje, tipo = 'info') => {
        estado.textContent = mensaje;
        estado.className = 'text-xs ' + (tipo === 'error' ? 'text-rose-600' : tipo === 'ok' ? 'text-emerald-700' : 'text-gray-500');
    };

    if (!Reconocimiento) {
        boton.disabled = true;
        boton.classList.add('opacity-50', 'cursor-not-allowed');
        avisar('Su navegador no admite comandos de voz (use Chrome o Edge). Puede seguir con el teclado y el ratón.', 'error');
        return;
    }

    const reconocimiento = new Reconocimiento();
    reconocimiento.lang = 'es-ES';
    reconocimiento.interimResults = false;
    reconocimiento.maxAlternatives = 1;
    let escuchando = false;

    const marcar = (activo) => {
        escuchando = activo;
        boton.setAttribute('aria-pressed', String(activo));
        boton.classList.toggle('animate-pulse', activo);
        boton.classList.toggle('bg-rose-600', activo);
        boton.classList.toggle('bg-purple-800', !activo);
        if (activo) avisar('Escuchando… diga un comando.');
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

    function ejecutar(dicho) {
        const t = normalizar(dicho);

        // 1) Navegación (solo rutas permitidas para el rol).
        const esDictado = /^(especialidad|medico|doctor|doctora|fecha|dia|motivo|estado)\b/.test(t);
        const destino = esDictado ? null : Object.keys(rutas).find((frase) => t.includes(normalizar(frase)));
        if (destino) {
            avisar('Abriendo «' + destino + '»…', 'ok');
            window.location.href = rutas[destino];
            return;
        }

        // 2) Dictado de campos: "especialidad cardiología", "médico Pérez", "fecha mañana", "motivo dolor de cabeza".
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

        avisar('Comando no reconocido. Ejemplos: «especialidad pediatría», «fecha mañana», «abrir agenda».', 'error');
    }
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-voz]').forEach(iniciar);
}

export { interpretarFecha, normalizar };
