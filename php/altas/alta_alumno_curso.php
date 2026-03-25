<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol(['administrador', 'preceptor']);

$id_operador = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_operador);
$es_admin = in_array('administrador', $tipos_usuario, true);

if ($es_admin) {
    $cursos = db_fetch_all(
        $con,
        "SELECT c.id_curso, c.grado, m.moda, s.seccion
         FROM cursos AS c
         INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         ORDER BY c.grado, s.seccion"
    );
} else {
    $cursos = db_fetch_all(
        $con,
        "SELECT DISTINCT c.id_curso, c.grado, m.moda, s.seccion
         FROM cursos AS c
         INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso
         WHERE pc.id_persona = ?
         ORDER BY c.grado, s.seccion",
        'i',
        [$id_operador]
    );
}

$ids_cursos_habilitados = array_map(static fn($fila) => (int)$fila['id_curso'], $cursos);

$msg_alta = '';
$error_form = '';
$curso_sel = 0;
$persona_sel = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificar_csrf();

    $curso_sel = (int)($_POST["curso"] ?? 0);
    $persona_sel = (int)($_POST["persona"] ?? 0);

    if ($curso_sel <= 0 || $persona_sel <= 0) {
        $error_form = 'Debés seleccionar un curso y un alumno válidos.';
    } elseif (!in_array($curso_sel, $ids_cursos_habilitados, true)) {
        http_response_code(403);
        $error_form = 'No tenés permisos para inscribir alumnos en ese curso.';
    } elseif (!persona_tiene_tipo($con, $persona_sel, 'alumno')) {
        $error_form = 'La persona seleccionada no tiene tipo alumno.';
    } else {
        $curso_actual = db_fetch_one(
            $con,
            "SELECT id_persona_x_curso
             FROM alumnos_x_curso
             WHERE id_persona = ?
             LIMIT 1",
            'i',
            [$persona_sel]
        );

        if ($curso_actual) {
            $error_form = 'Ese alumno ya tiene un curso asignado.';
        } else {
            $stmt = mysqli_prepare($con, "INSERT INTO alumnos_x_curso (id_curso, id_persona) VALUES (?, ?)");
            if (!$stmt) {
                $error_form = 'No se pudo preparar la inscripción del alumno.';
            } else {
                mysqli_stmt_bind_param($stmt, "ii", $curso_sel, $persona_sel);
                $ok = mysqli_stmt_execute($stmt);
                $errno = mysqli_errno($con);
                mysqli_stmt_close($stmt);

                if ($ok) {
                    $msg_alta = 'Alumno dado de alta en el curso correctamente.';
                    $curso_sel = 0;
                    $persona_sel = 0;
                } elseif ($errno === 1062) {
                    $error_form = 'Ese alumno ya está asignado a un curso.';
                } else {
                    $error_form = 'No se pudo guardar la inscripción. Verificá que curso y alumno sean válidos.';
                }
            }
        }
    }
}

$prefill_curso = ['id' => 0, 'label' => '', 'extra' => ''];
$prefill_alumno = ['id' => 0, 'label' => '', 'extra' => ''];

if ($curso_sel > 0) {
    if ($es_admin) {
        $curso_info = db_fetch_one(
            $con,
            "SELECT c.id_curso, c.grado, s.seccion, m.moda
             FROM cursos AS c
             INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
             INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
             WHERE c.id_curso = ?
             LIMIT 1",
            'i',
            [$curso_sel]
        );
    } else {
        $curso_info = db_fetch_one(
            $con,
            "SELECT c.id_curso, c.grado, s.seccion, m.moda
             FROM cursos AS c
             INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
             INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
             INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso
             WHERE c.id_curso = ? AND pc.id_persona = ?
             LIMIT 1",
            'ii',
            [$curso_sel, $id_operador]
        );
    }

    if ($curso_info) {
        $prefill_curso = [
            'id' => (int)$curso_info['id_curso'],
            'label' => (string)($curso_info['grado'] . '° ' . $curso_info['seccion'] . ' — ' . $curso_info['moda']),
            'extra' => 'Curso #' . (int)$curso_info['id_curso'],
        ];
    }
}

if ($persona_sel > 0) {
    $alumno_info = db_fetch_one(
        $con,
        "SELECT p.id_persona, p.apellido, p.nombre, p.dni
         FROM personas AS p
         INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
         INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
         WHERE p.id_persona = ? AND LOWER(tp.tipo) = 'alumno'
         LIMIT 1",
        'i',
        [$persona_sel]
    );

    if ($alumno_info) {
        $prefill_alumno = [
            'id' => (int)$alumno_info['id_persona'],
            'label' => (string)($alumno_info['apellido'] . ', ' . $alumno_info['nombre']),
            'extra' => 'DNI ' . (int)$alumno_info['dni'],
        ];
    }
}

$estado_inicial = [
    'curso' => $prefill_curso,
    'alumno' => $prefill_alumno,
    'sin_cursos' => empty($ids_cursos_habilitados),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Alta Alumno a Curso</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
    <style>
        .alta-alumno-card {
            width: min(920px, 100%);
            margin: 0 auto;
            background: var(--white);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 2rem 2.1rem;
            box-shadow: var(--shadow-md);
        }

        .alta-alumno-card h2 {
            margin-bottom: .35rem;
        }

        .alta-subtitulo {
            color: var(--text-muted);
            font-size: .9rem;
            margin-bottom: 1.2rem;
        }

        .pasos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .paso-box {
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1rem;
            background: rgba(255, 255, 255, .03);
        }

        .paso-box.full {
            grid-column: 1 / -1;
        }

        .paso-head {
            display: flex;
            align-items: center;
            gap: .55rem;
            margin-bottom: .55rem;
            font-family: 'Outfit', sans-serif;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--accent-light);
        }

        .paso-num {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .72rem;
            color: white;
            background: var(--accent);
        }

        .paso-num.inactive {
            background: var(--glass-border);
            color: var(--text-muted);
        }

        .search-status {
            min-height: 1.1rem;
            margin-top: .4rem;
            font-size: .82rem;
            color: var(--text-muted);
        }

        .search-status.err { color: #fecaca; }
        .search-status.ok { color: #a7f3d0; }

        .search-results {
            margin-top: .55rem;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            max-height: 190px;
            overflow-y: auto;
            background: rgba(255, 255, 255, .02);
        }

        .result-item {
            width: 100%;
            border: 0;
            border-bottom: 1px solid var(--divider);
            text-align: left;
            padding: .6rem .7rem;
            background: transparent;
            color: var(--text);
            transition: background .15s ease;
        }

        .result-item:last-child {
            border-bottom: 0;
        }

        .result-item:hover,
        .result-item:focus {
            outline: none;
            background: var(--accent-soft);
        }

        .result-main {
            display: block;
            font-size: .9rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .result-extra {
            display: block;
            font-size: .76rem;
            color: var(--text-muted);
            margin-top: .15rem;
        }

        .result-empty {
            padding: .8rem;
            color: var(--text-muted);
            font-size: .84rem;
        }

        .selection-chip {
            margin-top: .6rem;
            border: 1px solid rgba(129, 140, 248, .48);
            border-radius: var(--radius-md);
            background: rgba(99, 102, 241, .12);
            padding: .62rem .7rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
        }

        .selection-chip.hidden {
            display: none;
        }

        .selection-label {
            display: block;
            font-size: .88rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .selection-extra {
            display: block;
            font-size: .76rem;
            color: var(--text-muted);
        }

        .chip-clear {
            border: 1px solid var(--glass-border-hi);
            border-radius: 999px;
            font-size: .74rem;
            padding: .22rem .62rem;
            color: var(--text);
            background: rgba(255, 255, 255, .03);
        }

        .chip-clear:hover {
            background: rgba(255, 255, 255, .12);
        }

        .resumen-box {
            border: 1px dashed var(--glass-border-hi);
            border-radius: var(--radius-md);
            padding: .8rem .9rem;
            background: rgba(255, 255, 255, .02);
            margin-bottom: .8rem;
        }

        .resumen-item {
            font-size: .88rem;
            color: var(--text);
            margin-bottom: .35rem;
        }

        .resumen-item:last-child {
            margin-bottom: 0;
        }

        .resumen-item strong {
            color: var(--accent-light);
            font-weight: 700;
            margin-right: .35rem;
        }

        .actions-row {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-plei-submit[disabled] {
            opacity: .55;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 900px) {
            .pasos-grid {
                grid-template-columns: 1fr;
            }

            .paso-box,
            .paso-box.full {
                grid-column: auto;
            }

            .alta-alumno-card {
                padding: 1.45rem 1.2rem;
            }
        }
    </style>
</head>
<body class="form-page-body">
<div class="alta-alumno-card">
    <h2 class="fw-bold">Alta de Alumno a Curso</h2>
    <p class="alta-subtitulo">Flujo guiado para preceptoría/dirección: elegí curso, buscá alumno sin curso y confirmá la inscripción.</p>

    <?php if ($msg_alta !== ''): ?>
    <div class="alert-ok">
        <i class="bi bi-check-circle-fill"></i>
        <?= htmlspecialchars($msg_alta) ?>
    </div>
    <?php endif; ?>

    <?php if ($error_form !== ''): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($error_form) ?>
    </div>
    <?php endif; ?>

    <?php if (empty($ids_cursos_habilitados)): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No tenés cursos a cargo para realizar inscripciones.
    </div>
    <?php endif; ?>

    <noscript>
        <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i>Esta pantalla necesita JavaScript para usar el buscador guiado.</div>
    </noscript>

    <form autocomplete="off" method="POST" id="formAltaAlumnoCurso">
        <?php campo_csrf(); ?>
        <input type="hidden" name="curso" id="hiddenCurso" value="">
        <input type="hidden" name="persona" id="hiddenAlumno" value="">

        <div class="pasos-grid">
            <section class="paso-box">
                <div class="paso-head"><span class="paso-num" id="numPaso1">1</span> Elegí el curso</div>
                <input type="text" class="form-control" id="buscarCurso" placeholder="Escribí al menos 2 caracteres (ej: 5°, Electromecánica)">
                <div class="search-status" id="estadoCurso">Escribí para buscar cursos.</div>
                <div class="search-results" id="resultadosCurso" role="listbox" aria-label="Resultados de cursos"></div>
                <div class="selection-chip hidden" id="seleccionCurso">
                    <div>
                        <span class="selection-label" id="seleccionCursoLabel"></span>
                        <span class="selection-extra" id="seleccionCursoExtra"></span>
                    </div>
                    <button type="button" class="chip-clear" id="limpiarCurso">Quitar</button>
                </div>
            </section>

            <section class="paso-box">
                <div class="paso-head"><span class="paso-num inactive" id="numPaso2">2</span> Buscá alumno sin curso</div>
                <input type="text" class="form-control" id="buscarAlumno" placeholder="Escribí apellido, nombre o DNI" disabled>
                <div class="search-status" id="estadoAlumno">Primero seleccioná un curso.</div>
                <div class="search-results" id="resultadosAlumno" role="listbox" aria-label="Resultados de alumnos"></div>
                <div class="selection-chip hidden" id="seleccionAlumno">
                    <div>
                        <span class="selection-label" id="seleccionAlumnoLabel"></span>
                        <span class="selection-extra" id="seleccionAlumnoExtra"></span>
                    </div>
                    <button type="button" class="chip-clear" id="limpiarAlumno">Quitar</button>
                </div>
            </section>

            <section class="paso-box full">
                <div class="paso-head"><span class="paso-num inactive" id="numPaso3">3</span> Confirmá la inscripción</div>
                <div class="resumen-box">
                    <div class="resumen-item"><strong>Curso:</strong> <span id="resumenCurso">Sin seleccionar</span></div>
                    <div class="resumen-item"><strong>Alumno:</strong> <span id="resumenAlumno">Sin seleccionar</span></div>
                </div>

                <button type="submit" class="btn-plei-submit" id="btnAlta" <?= empty($ids_cursos_habilitados) ? 'disabled' : '' ?>>
                    <i class="bi bi-person-plus-fill me-2"></i>Dar de alta
                </button>
            </section>
        </div>
    </form>

    <div class="actions-row">
        <a href="<?php echo url('home.php'); ?>" class="btn-plei-cancel">Volver</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const ENDPOINTS = {
        cursos: '../../php/ajax/buscar_cursos.php',
        alumnos: '../../php/ajax/buscar_alumnos_sin_curso.php'
    };

    const MIN_QUERY = 2;
    const LIMIT = 20;
    const DEBOUNCE_MS = 280;

    const estadoInicial = <?php echo json_encode($estado_inicial, JSON_UNESCAPED_UNICODE); ?>;

    const state = {
        curso: null,
        alumno: null,
        timers: {},
        reqSeq: { curso: 0, alumno: 0 }
    };

    const els = {
        form: document.getElementById('formAltaAlumnoCurso'),
        hiddenCurso: document.getElementById('hiddenCurso'),
        hiddenAlumno: document.getElementById('hiddenAlumno'),
        btnAlta: document.getElementById('btnAlta'),

        buscarCurso: document.getElementById('buscarCurso'),
        estadoCurso: document.getElementById('estadoCurso'),
        resultadosCurso: document.getElementById('resultadosCurso'),
        seleccionCurso: document.getElementById('seleccionCurso'),
        seleccionCursoLabel: document.getElementById('seleccionCursoLabel'),
        seleccionCursoExtra: document.getElementById('seleccionCursoExtra'),
        limpiarCurso: document.getElementById('limpiarCurso'),

        buscarAlumno: document.getElementById('buscarAlumno'),
        estadoAlumno: document.getElementById('estadoAlumno'),
        resultadosAlumno: document.getElementById('resultadosAlumno'),
        seleccionAlumno: document.getElementById('seleccionAlumno'),
        seleccionAlumnoLabel: document.getElementById('seleccionAlumnoLabel'),
        seleccionAlumnoExtra: document.getElementById('seleccionAlumnoExtra'),
        limpiarAlumno: document.getElementById('limpiarAlumno'),

        numPaso2: document.getElementById('numPaso2'),
        numPaso3: document.getElementById('numPaso3'),
        resumenCurso: document.getElementById('resumenCurso'),
        resumenAlumno: document.getElementById('resumenAlumno')
    };

    function debounce(key, fn) {
        clearTimeout(state.timers[key]);
        state.timers[key] = setTimeout(fn, DEBOUNCE_MS);
    }

    function setStatus(el, text, type = '') {
        el.textContent = text;
        el.classList.remove('err', 'ok');
        if (type) {
            el.classList.add(type);
        }
    }

    function clearResults(container, text = '') {
        container.innerHTML = text ? `<div class="result-empty">${text}</div>` : '';
    }

    function renderResults(container, data, onPick) {
        container.innerHTML = '';
        if (!Array.isArray(data) || data.length === 0) {
            clearResults(container, 'Sin resultados para esa búsqueda.');
            return;
        }

        data.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'result-item';

            const main = document.createElement('span');
            main.className = 'result-main';
            main.textContent = String(item.label ?? '');

            const extra = document.createElement('span');
            extra.className = 'result-extra';
            extra.textContent = String(item.extra ?? '');

            button.appendChild(main);
            button.appendChild(extra);
            button.addEventListener('click', () => onPick(item));
            container.appendChild(button);
        });
    }

    function paintSelection(type, item) {
        if (type === 'curso') {
            if (!item) {
                els.seleccionCurso.classList.add('hidden');
                return;
            }
            els.seleccionCursoLabel.textContent = String(item.label ?? '');
            els.seleccionCursoExtra.textContent = String(item.extra ?? '');
            els.seleccionCurso.classList.remove('hidden');
            return;
        }

        if (!item) {
            els.seleccionAlumno.classList.add('hidden');
            return;
        }
        els.seleccionAlumnoLabel.textContent = String(item.label ?? '');
        els.seleccionAlumnoExtra.textContent = String(item.extra ?? '');
        els.seleccionAlumno.classList.remove('hidden');
    }

    function updateSummary() {
        els.resumenCurso.textContent = state.curso ? state.curso.label : 'Sin seleccionar';
        els.resumenAlumno.textContent = state.alumno ? `${state.alumno.label} (${state.alumno.extra})` : 'Sin seleccionar';
    }

    function updateStepVisuals() {
        if (state.curso) {
            els.numPaso2.classList.remove('inactive');
            els.numPaso2.style.background = 'var(--accent)';
            els.numPaso2.style.color = 'white';
        } else {
            els.numPaso2.classList.add('inactive');
            els.numPaso2.style.background = '';
            els.numPaso2.style.color = '';
        }

        if (state.curso && state.alumno) {
            els.numPaso3.classList.remove('inactive');
            els.numPaso3.style.background = 'var(--accent)';
            els.numPaso3.style.color = 'white';
        } else {
            els.numPaso3.classList.add('inactive');
            els.numPaso3.style.background = '';
            els.numPaso3.style.color = '';
        }
    }

    function updateSubmitState() {
        const ok = !!(state.curso && state.alumno) && !estadoInicial.sin_cursos;
        els.btnAlta.disabled = !ok;
        els.hiddenCurso.value = state.curso ? String(state.curso.id) : '';
        els.hiddenAlumno.value = state.alumno ? String(state.alumno.id) : '';
        updateSummary();
        updateStepVisuals();
    }

    function clearAlumno() {
        state.alumno = null;
        els.buscarAlumno.value = '';
        paintSelection('alumno', null);
        clearResults(els.resultadosAlumno, 'Escribí al menos 2 caracteres para buscar alumnos sin curso.');
        updateSubmitState();
    }

    function selectCurso(item) {
        state.curso = {
            id: Number(item.id || 0),
            label: String(item.label || ''),
            extra: String(item.extra || '')
        };
        paintSelection('curso', state.curso);
        clearResults(els.resultadosCurso, 'Curso seleccionado. Podés buscar otro si necesitás cambiarlo.');

        els.buscarAlumno.disabled = false;
        setStatus(els.estadoAlumno, 'Ahora buscá y elegí un alumno sin curso.', '');
        clearAlumno();
        els.buscarAlumno.focus();
        updateSubmitState();
    }

    function clearCurso() {
        state.curso = null;
        els.buscarCurso.value = '';
        paintSelection('curso', null);
        clearResults(els.resultadosCurso, 'Escribí al menos 2 caracteres para buscar cursos.');

        els.buscarAlumno.disabled = true;
        setStatus(els.estadoAlumno, 'Primero seleccioná un curso.', '');
        clearAlumno();
        updateSubmitState();
    }

    function selectAlumno(item) {
        state.alumno = {
            id: Number(item.id || 0),
            label: String(item.label || ''),
            extra: String(item.extra || '')
        };
        paintSelection('alumno', state.alumno);
        clearResults(els.resultadosAlumno, 'Alumno seleccionado.');
        updateSubmitState();
    }

    async function fetchResultados(key, url, estadoEl, resultadosEl, onPick) {
        const seq = ++state.reqSeq[key];
        setStatus(estadoEl, 'Buscando...', '');

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();
            if (seq !== state.reqSeq[key]) {
                return;
            }

            renderResults(resultadosEl, data, onPick);
            if (Array.isArray(data) && data.length > 0) {
                setStatus(estadoEl, `${data.length} resultado(s).`, 'ok');
            } else {
                setStatus(estadoEl, 'No se encontraron resultados.', '');
            }
        } catch (error) {
            if (seq !== state.reqSeq[key]) {
                return;
            }
            clearResults(resultadosEl, 'Hubo un error al buscar. Intentá nuevamente.');
            setStatus(estadoEl, 'No se pudo completar la búsqueda.', 'err');
        }
    }

    function wireSearchCurso() {
        els.buscarCurso.addEventListener('input', () => {
            if (estadoInicial.sin_cursos) {
                return;
            }

            const q = els.buscarCurso.value.trim();
            if (q.length < MIN_QUERY) {
                clearResults(els.resultadosCurso, 'Escribí al menos 2 caracteres para buscar cursos.');
                setStatus(els.estadoCurso, 'Escribí al menos 2 caracteres.', '');
                return;
            }

            debounce('curso', () => {
                const params = new URLSearchParams({ q, limit: String(LIMIT), offset: '0' });
                fetchResultados('curso', `${ENDPOINTS.cursos}?${params.toString()}`, els.estadoCurso, els.resultadosCurso, selectCurso);
            });
        });
    }

    function wireSearchAlumno() {
        els.buscarAlumno.addEventListener('input', () => {
            if (!state.curso) {
                setStatus(els.estadoAlumno, 'Primero seleccioná un curso.', 'err');
                return;
            }

            const q = els.buscarAlumno.value.trim();
            if (q.length < MIN_QUERY) {
                clearResults(els.resultadosAlumno, 'Escribí al menos 2 caracteres para buscar alumnos sin curso.');
                setStatus(els.estadoAlumno, 'Escribí al menos 2 caracteres.', '');
                return;
            }

            debounce('alumno', () => {
                const params = new URLSearchParams({ q, limit: String(LIMIT), offset: '0' });
                fetchResultados('alumno', `${ENDPOINTS.alumnos}?${params.toString()}`, els.estadoAlumno, els.resultadosAlumno, selectAlumno);
            });
        });
    }

    function wireActions() {
        els.limpiarCurso.addEventListener('click', () => {
            clearCurso();
            setStatus(els.estadoCurso, 'Curso limpiado.', '');
        });

        els.limpiarAlumno.addEventListener('click', () => {
            clearAlumno();
            setStatus(els.estadoAlumno, 'Alumno limpiado.', '');
        });

        els.form.addEventListener('submit', (event) => {
            if (!state.curso || !state.alumno) {
                event.preventDefault();
                alert('Seleccioná un curso y un alumno antes de confirmar.');
                return;
            }

            els.hiddenCurso.value = String(state.curso.id);
            els.hiddenAlumno.value = String(state.alumno.id);
        });
    }

    function hydrateFromServer() {
        if (estadoInicial.sin_cursos) {
            els.buscarCurso.disabled = true;
            els.buscarAlumno.disabled = true;
            setStatus(els.estadoCurso, 'No hay cursos habilitados para tu usuario.', 'err');
            setStatus(els.estadoAlumno, 'No hay cursos disponibles para continuar.', 'err');
            clearResults(els.resultadosCurso, 'Sin cursos habilitados.');
            clearResults(els.resultadosAlumno, 'No podés buscar alumnos sin curso hasta tener cursos habilitados.');
            updateSubmitState();
            return;
        }

        clearResults(els.resultadosCurso, 'Escribí al menos 2 caracteres para buscar cursos.');
        clearResults(els.resultadosAlumno, 'Escribí al menos 2 caracteres para buscar alumnos sin curso.');

        if (estadoInicial.curso && Number(estadoInicial.curso.id || 0) > 0) {
            state.curso = {
                id: Number(estadoInicial.curso.id),
                label: String(estadoInicial.curso.label || ''),
                extra: String(estadoInicial.curso.extra || '')
            };
            paintSelection('curso', state.curso);
            els.buscarAlumno.disabled = false;
            setStatus(els.estadoAlumno, 'Ahora buscá y elegí un alumno sin curso.', '');
        } else {
            els.buscarAlumno.disabled = true;
            setStatus(els.estadoAlumno, 'Primero seleccioná un curso.', '');
        }

        if (estadoInicial.alumno && Number(estadoInicial.alumno.id || 0) > 0) {
            state.alumno = {
                id: Number(estadoInicial.alumno.id),
                label: String(estadoInicial.alumno.label || ''),
                extra: String(estadoInicial.alumno.extra || '')
            };
            paintSelection('alumno', state.alumno);
        }

        updateSubmitState();
    }

    wireSearchCurso();
    wireSearchAlumno();
    wireActions();
    hydrateFromServer();
})();
</script>
</body>
</html>
