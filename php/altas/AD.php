<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol(['administrador', 'preceptor']);

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);

$mensaje_tipo = '';
$mensaje_texto = '';

$modo_inicial = 'curso';
$prefill_curso = ['id' => 0, 'label' => '', 'extra' => ''];
$prefill_materia = ['id' => 0, 'label' => '', 'extra' => '', 'grupos' => []];
$prefill_docente = ['id' => 0, 'label' => '', 'extra' => ''];
$prefill_grupo = 0;
$filtro_activo_docente = condicion_persona_activa($con, 'p');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificar_csrf();

    $modo_post = strtolower(trim((string)($_POST['modo'] ?? 'curso')));
    if ($modo_post === 'curso' || $modo_post === 'directo') {
        $modo_inicial = $modo_post;
    }

    $id_materia = (int)($_POST["materia"] ?? 0);
    $id_docente = (int)($_POST["docente"] ?? 0);
    $id_grupo_post = (int)($_POST["grupo"] ?? 0);

    if ($id_materia <= 0 || $id_docente <= 0) {
        $mensaje_tipo = 'err';
        $mensaje_texto = 'Debés seleccionar una materia y un docente válidos.';
    } else {
        if ($es_admin) {
            $materia_habilitada = db_fetch_one(
                $con,
                "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo,
                        c.id_curso, c.grado, s.seccion, mo.moda
                 FROM materias AS m
                 INNER JOIN cursos AS c ON c.id_curso = m.id_curso
                 INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
                 INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
                 WHERE m.id_materia = ?
                 LIMIT 1",
                "i",
                [$id_materia]
            );
        } else {
            $materia_habilitada = db_fetch_one(
                $con,
                "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo,
                        c.id_curso, c.grado, s.seccion, mo.moda
                 FROM materias AS m
                 INNER JOIN cursos AS c ON c.id_curso = m.id_curso
                 INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
                 INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
                 INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso
                 WHERE m.id_materia = ?
                   AND pc.id_persona = ?
                 LIMIT 1",
                "ii",
                [$id_materia, $id_persona]
            );
        }

        if ($materia_habilitada) {
            $grupos_materia = grupos_de_materia($con, (int)$materia_habilitada['id_materia']);
            $grupos_texto = $grupos_materia !== [] ? implode(',', $grupos_materia) : (string)$materia_habilitada['grupo'];
            $prefill_curso = [
                'id' => (int)$materia_habilitada['id_curso'],
                'label' => (string)($materia_habilitada['grado'] . '° ' . $materia_habilitada['seccion'] . ' — ' . $materia_habilitada['moda']),
                'extra' => 'Curso #' . (int)$materia_habilitada['id_curso'],
            ];
            $prefill_materia = [
                'id' => (int)$materia_habilitada['id_materia'],
                'label' => (string)($materia_habilitada['nombre_materia'] . ' — ' . $materia_habilitada['grado'] . '° ' . $materia_habilitada['seccion'] . ' (' . $materia_habilitada['moda'] . ') Turno ' . $materia_habilitada['turno']),
                'extra' => 'Grupos ' . $grupos_texto . ' | Curso #' . (int)$materia_habilitada['id_curso'],
                'grupos' => $grupos_materia,
            ];
            $prefill_grupo = $id_grupo_post;
        }

        $docente_valido = db_fetch_one(
            $con,
            "SELECT p.id_persona, p.apellido, p.nombre, p.dni
             FROM personas AS p
             INNER JOIN tipo_persona_x_persona AS ti ON ti.id_persona = p.id_persona
             INNER JOIN tipos_personas AS t ON t.id_tipo_persona = ti.id_tipo_persona
             WHERE p.id_persona = ?
               AND LOWER(TRIM(t.tipo)) = 'docente'
               $filtro_activo_docente
             LIMIT 1",
            "i",
            [$id_docente]
        );

        if ($docente_valido) {
            $prefill_docente = [
                'id' => (int)$docente_valido['id_persona'],
                'label' => (string)($docente_valido['apellido'] . ', ' . $docente_valido['nombre']),
                'extra' => 'DNI ' . (int)$docente_valido['dni'],
            ];
        }

        if (!$materia_habilitada) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'No tenés permisos para asignar docentes a esa materia o la materia no existe.';
        } elseif (!$docente_valido) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'La persona seleccionada no tiene tipo docente.';
        } else {
            $grupos_materia = grupos_de_materia($con, $id_materia);
            if ($grupos_materia !== []) {
                if ($id_grupo_post <= 0) {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'Debés seleccionar un grupo válido para esa materia.';
                } elseif (!in_array($id_grupo_post, $grupos_materia, true)) {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'El grupo seleccionado no pertenece a la materia.';
                }
            } else {
                $id_grupo_post = 0;
            }
        }

        if ($mensaje_texto === '') {
            $existe = db_fetch_one(
                $con,
                "SELECT id_docente_x_materia
                 FROM docentes_x_materia
                 WHERE id_materia = ? AND id_persona = ?
                 LIMIT 1",
                "ii",
                [$id_materia, $id_docente]
            );

            if ($existe) {
                $mensaje_tipo = 'err';
                $mensaje_texto = 'Ese docente ya está asignado a esa materia.';
            } elseif ($id_grupo_post > 0) {
                $grupo_ocupado = db_fetch_one(
                    $con,
                    "SELECT id_docente_x_materia
                     FROM docentes_x_materia
                     WHERE id_materia = ? AND id_grupo = ?
                     LIMIT 1",
                    "ii",
                    [$id_materia, $id_grupo_post]
                );

                if ($grupo_ocupado) {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'Ese grupo de la materia ya tiene un docente asignado.';
                }
            }
        }

        if ($mensaje_texto === '') {
            if ($id_grupo_post > 0) {
                $stmt = mysqli_prepare($con, "INSERT INTO docentes_x_materia (id_materia, id_persona, id_grupo) VALUES (?, ?, ?)");
            } else {
                $stmt = mysqli_prepare($con, "INSERT INTO docentes_x_materia (id_materia, id_persona) VALUES (?, ?)");
            }

            if (!$stmt) {
                $mensaje_tipo = 'err';
                $mensaje_texto = 'No se pudo preparar la asignación.';
            } else {
                if ($id_grupo_post > 0) {
                    mysqli_stmt_bind_param($stmt, "iii", $id_materia, $id_docente, $id_grupo_post);
                } else {
                    mysqli_stmt_bind_param($stmt, "ii", $id_materia, $id_docente);
                }
                $ok = mysqli_stmt_execute($stmt);
                $errno = mysqli_errno($con);
                mysqli_stmt_close($stmt);

                if ($ok) {
                    $mensaje_tipo = 'ok';
                    $mensaje_texto = 'Materia asignada al docente correctamente.';
                    $modo_inicial = 'curso';
                    $prefill_curso = ['id' => 0, 'label' => '', 'extra' => ''];
                    $prefill_materia = ['id' => 0, 'label' => '', 'extra' => '', 'grupos' => []];
                    $prefill_docente = ['id' => 0, 'label' => '', 'extra' => ''];
                    $prefill_grupo = 0;
                } elseif ($errno === 1062) {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'No se pudo guardar: ya existe una asignación incompatible para esa materia/grupo.';
                } else {
                    $mensaje_tipo = 'err';
                    $mensaje_texto = 'No se pudo guardar la asignación.';
                }
            }
        }
    }
}

$estado_inicial = [
    'modo' => $modo_inicial,
    'curso' => $prefill_curso,
    'materia' => $prefill_materia,
    'docente' => $prefill_docente,
    'grupo' => $prefill_grupo,
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Asignar materia a docente</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
    <style>
        .asignar-docente-card {
            width: min(940px, 100%);
            margin: 0 auto;
            background: var(--white);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            padding: 2rem 2.1rem;
            box-shadow: var(--shadow-md);
        }

        .asignar-docente-card h2 {
            margin-bottom: .25rem;
        }

        .ayuda-subtitulo {
            color: var(--text-muted);
            margin-bottom: 1.35rem;
            font-size: .92rem;
        }

        .modo-switch {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: .6rem;
            margin-bottom: 1rem;
        }

        .modo-switch label {
            display: flex;
            align-items: center;
            gap: .55rem;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, .04);
            border-radius: var(--radius-md);
            padding: .75rem .85rem;
            cursor: pointer;
            transition: border-color .2s ease, background .2s ease;
        }

        .modo-switch label:has(input:checked) {
            border-color: var(--accent-light);
            background: var(--accent-soft);
        }

        .modo-switch input[type="radio"] {
            accent-color: var(--accent);
            margin: 0;
            flex-shrink: 0;
        }

        .busqueda-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .busqueda-seccion {
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: .95rem;
            background: rgba(255, 255, 255, .03);
            min-height: 312px;
        }

        .busqueda-seccion.full {
            grid-column: 1 / -1;
        }

        .search-status {
            min-height: 1.1rem;
            font-size: .82rem;
            margin-top: .35rem;
            color: var(--text-muted);
        }

        .search-status.err { color: #fecaca; }
        .search-status.ok { color: #a7f3d0; }

        .search-results {
            margin-top: .55rem;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            max-height: 172px;
            overflow-y: auto;
            background: rgba(255, 255, 255, .02);
        }

        .result-item {
            width: 100%;
            text-align: left;
            border: 0;
            border-bottom: 1px solid var(--divider);
            background: transparent;
            color: var(--text);
            padding: .62rem .72rem;
            transition: background .15s ease;
        }

        .result-item:last-child {
            border-bottom: 0;
        }

        .result-item:hover,
        .result-item:focus {
            background: var(--accent-soft);
            outline: none;
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
            font-size: .85rem;
        }

        .seleccion-box {
            margin-top: .6rem;
            border: 1px solid rgba(129, 140, 248, .45);
            border-radius: var(--radius-md);
            background: rgba(99, 102, 241, .12);
            padding: .62rem .7rem;
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            align-items: center;
        }

        .seleccion-label {
            font-size: .88rem;
            font-weight: 700;
            display: block;
            line-height: 1.2;
        }

        .seleccion-extra {
            font-size: .76rem;
            color: var(--text-muted);
        }

        .seleccion-clear {
            border: 1px solid var(--glass-border-hi);
            border-radius: 999px;
            color: var(--text);
            background: rgba(255, 255, 255, .03);
            padding: .2rem .62rem;
            font-size: .74rem;
        }

        .seleccion-clear:hover {
            background: rgba(255, 255, 255, .12);
        }

        .bloque-oculto {
            display: none;
        }

        .acciones-finales {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
            margin-top: 1.2rem;
        }

        .btn-plei-submit[disabled] {
            opacity: .55;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 920px) {
            .busqueda-grid {
                grid-template-columns: 1fr;
            }

            .busqueda-seccion,
            .busqueda-seccion.full {
                grid-column: auto;
                min-height: auto;
            }

            .asignar-docente-card {
                padding: 1.45rem 1.2rem;
            }
        }
    </style>
</head>
<body class="form-page-body">
<div class="asignar-docente-card">
    <h2 class="fw-bold">Asignar materia a docente</h2>
    <p class="ayuda-subtitulo">Buscá rápido por curso o en modo directo. Las asignaciones respetan los permisos de administración/preceptoría.</p>

    <?php if ($mensaje_texto !== ''): ?>
    <div class="<?php echo $mensaje_tipo === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
        <i class="bi <?php echo $mensaje_tipo === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
        <?php echo htmlspecialchars($mensaje_texto); ?>
    </div>
    <?php endif; ?>

    <noscript>
        <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i>Esta pantalla requiere JavaScript para buscar y seleccionar cursos/materias/docentes.</div>
    </noscript>

    <form autocomplete="off" method="POST" id="formAsignacionDocente">
        <?php campo_csrf(); ?>
        <input type="hidden" name="modo" id="hiddenModo" value="curso">
        <input type="hidden" name="materia" id="hiddenMateria" value="">
        <input type="hidden" name="docente" id="hiddenDocente" value="">
        <input type="hidden" name="grupo" id="hiddenGrupo" value="">

        <div class="modo-switch" role="radiogroup" aria-label="Modo de búsqueda de materia">
            <label>
                <input type="radio" name="modo_busqueda_visual" value="curso" id="modoCurso" checked>
                <span><strong>Por curso</strong><br><small class="texto-opcional">Curso → materia → docente</small></span>
            </label>
            <label>
                <input type="radio" name="modo_busqueda_visual" value="directo" id="modoDirecto">
                <span><strong>Búsqueda directa</strong><br><small class="texto-opcional">Materia global + docente</small></span>
            </label>
        </div>

        <div class="busqueda-grid">
            <section class="busqueda-seccion full" id="seccionCurso">
                <label for="buscarCurso" class="form-label">Curso</label>
                <input type="text" id="buscarCurso" class="form-control" placeholder="Escribí al menos 2 caracteres (ej: 6 A, técnica)">
                <div class="search-status" id="estadoCurso">Escribí para buscar cursos visibles.</div>
                <div class="search-results" id="resultadosCurso" role="listbox" aria-label="Resultados de cursos"></div>
                <div class="seleccion-box bloque-oculto" id="seleccionCurso">
                    <div>
                        <span class="seleccion-label" id="seleccionCursoLabel"></span>
                        <span class="seleccion-extra" id="seleccionCursoExtra"></span>
                    </div>
                    <button type="button" class="seleccion-clear" id="limpiarCurso">Quitar</button>
                </div>
            </section>

            <section class="busqueda-seccion" id="seccionMateria">
                <label for="buscarMateria" class="form-label">Materia</label>
                <input type="text" id="buscarMateria" class="form-control" placeholder="Escribí al menos 2 caracteres" disabled>
                <div class="search-status" id="estadoMateria">Primero seleccioná un curso o cambiá a búsqueda directa.</div>
                <div class="search-results" id="resultadosMateria" role="listbox" aria-label="Resultados de materias"></div>
                <div class="seleccion-box bloque-oculto" id="seleccionMateria">
                    <div>
                        <span class="seleccion-label" id="seleccionMateriaLabel"></span>
                        <span class="seleccion-extra" id="seleccionMateriaExtra"></span>
                    </div>
                    <button type="button" class="seleccion-clear" id="limpiarMateria">Quitar</button>
                </div>
            </section>

            <section class="busqueda-seccion" id="seccionGrupo">
                <label for="selectGrupo" class="form-label">Grupo (si corresponde)</label>
                <select id="selectGrupo" class="form-select" disabled>
                    <option value="">Seleccioná un grupo</option>
                </select>
                <div class="search-status" id="estadoGrupo">Seleccioná una materia para ver los grupos disponibles.</div>
            </section>

            <section class="busqueda-seccion" id="seccionDocente">
                <label for="buscarDocente" class="form-label">Docente</label>
                <input type="text" id="buscarDocente" class="form-control" placeholder="Escribí al menos 2 caracteres (apellido, nombre o DNI)">
                <div class="search-status" id="estadoDocente">Escribí para buscar docentes.</div>
                <div class="search-results" id="resultadosDocente" role="listbox" aria-label="Resultados de docentes"></div>
                <div class="seleccion-box bloque-oculto" id="seleccionDocente">
                    <div>
                        <span class="seleccion-label" id="seleccionDocenteLabel"></span>
                        <span class="seleccion-extra" id="seleccionDocenteExtra"></span>
                    </div>
                    <button type="button" class="seleccion-clear" id="limpiarDocente">Quitar</button>
                </div>
            </section>
        </div>

        <div class="acciones-finales">
            <button type="submit" class="btn-plei-submit" id="btnAsignar" disabled>Asignar materia</button>
            <a href="<?php echo url('home.php'); ?>" class="btn-plei-cancel">Volver</a>
        </div>
    </form>
</div>

<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const ENDPOINTS = {
        cursos: '../../php/ajax/buscar_cursos.php',
        materias: '../../php/ajax/buscar_materias.php',
        docentes: '../../php/ajax/buscar_docentes.php'
    };

    const MIN_QUERY = 2;
    const LIMIT = 20;
    const DEBOUNCE_MS = 280;

    const state = {
        mode: 'curso',
        curso: null,
        materia: null,
        docente: null,
        grupo: null,
        gruposMateria: [],
        timers: {},
        reqSeq: { curso: 0, materia: 0, docente: 0 }
    };

    const els = {
        modoCurso: document.getElementById('modoCurso'),
        modoDirecto: document.getElementById('modoDirecto'),
        hiddenModo: document.getElementById('hiddenModo'),
        hiddenMateria: document.getElementById('hiddenMateria'),
        hiddenDocente: document.getElementById('hiddenDocente'),
        hiddenGrupo: document.getElementById('hiddenGrupo'),
        btnAsignar: document.getElementById('btnAsignar'),

        seccionCurso: document.getElementById('seccionCurso'),

        buscarCurso: document.getElementById('buscarCurso'),
        estadoCurso: document.getElementById('estadoCurso'),
        resultadosCurso: document.getElementById('resultadosCurso'),
        seleccionCurso: document.getElementById('seleccionCurso'),
        seleccionCursoLabel: document.getElementById('seleccionCursoLabel'),
        seleccionCursoExtra: document.getElementById('seleccionCursoExtra'),
        limpiarCurso: document.getElementById('limpiarCurso'),

        buscarMateria: document.getElementById('buscarMateria'),
        estadoMateria: document.getElementById('estadoMateria'),
        resultadosMateria: document.getElementById('resultadosMateria'),
        seleccionMateria: document.getElementById('seleccionMateria'),
        seleccionMateriaLabel: document.getElementById('seleccionMateriaLabel'),
        seleccionMateriaExtra: document.getElementById('seleccionMateriaExtra'),
        limpiarMateria: document.getElementById('limpiarMateria'),

        selectGrupo: document.getElementById('selectGrupo'),
        estadoGrupo: document.getElementById('estadoGrupo'),

        buscarDocente: document.getElementById('buscarDocente'),
        estadoDocente: document.getElementById('estadoDocente'),
        resultadosDocente: document.getElementById('resultadosDocente'),
        seleccionDocente: document.getElementById('seleccionDocente'),
        seleccionDocenteLabel: document.getElementById('seleccionDocenteLabel'),
        seleccionDocenteExtra: document.getElementById('seleccionDocenteExtra'),
        limpiarDocente: document.getElementById('limpiarDocente')
    };

    const estadoInicial = <?php echo json_encode($estado_inicial, JSON_UNESCAPED_UNICODE); ?>;

    function debounce(key, fn) {
        clearTimeout(state.timers[key]);
        state.timers[key] = setTimeout(fn, DEBOUNCE_MS);
    }

    function setStatus(el, mensaje, tipo = '') {
        el.textContent = mensaje;
        el.classList.remove('err', 'ok');
        if (tipo) {
            el.classList.add(tipo);
        }
    }

    function clearResults(container, mensaje = '') {
        container.innerHTML = mensaje ? `<div class="result-empty">${mensaje}</div>` : '';
    }

    function renderResults(container, items, onPick) {
        container.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            clearResults(container, 'Sin resultados para esa búsqueda.');
            return;
        }

        items.forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'result-item';

            const main = document.createElement('span');
            main.className = 'result-main';
            main.textContent = String(item.label ?? '');

            const extra = document.createElement('span');
            extra.className = 'result-extra';
            extra.textContent = String(item.extra ?? '');

            btn.appendChild(main);
            btn.appendChild(extra);
            btn.addEventListener('click', () => onPick(item));
            container.appendChild(btn);
        });
    }

    function updateSubmitState() {
        const grupoRequerido = state.gruposMateria.length > 0;
        const grupoValido = !grupoRequerido || (state.grupo !== null && state.gruposMateria.includes(state.grupo));
        const habilitado = !!(state.materia && state.docente && grupoValido);
        els.btnAsignar.disabled = !habilitado;
        els.hiddenMateria.value = habilitado ? String(state.materia.id) : (state.materia ? String(state.materia.id) : '');
        els.hiddenDocente.value = state.docente ? String(state.docente.id) : '';
        els.hiddenGrupo.value = state.grupo ? String(state.grupo) : '';
    }

    function paintSelection(tipo, item) {
        if (tipo === 'curso') {
            if (!item) {
                els.seleccionCurso.classList.add('bloque-oculto');
                return;
            }
            els.seleccionCursoLabel.textContent = String(item.label ?? '');
            els.seleccionCursoExtra.textContent = String(item.extra ?? '');
            els.seleccionCurso.classList.remove('bloque-oculto');
            return;
        }

        if (tipo === 'materia') {
            if (!item) {
                els.seleccionMateria.classList.add('bloque-oculto');
                return;
            }
            els.seleccionMateriaLabel.textContent = String(item.label ?? '');
            els.seleccionMateriaExtra.textContent = String(item.extra ?? '');
            els.seleccionMateria.classList.remove('bloque-oculto');
            return;
        }

        if (!item) {
            els.seleccionDocente.classList.add('bloque-oculto');
            return;
        }
        els.seleccionDocenteLabel.textContent = String(item.label ?? '');
        els.seleccionDocenteExtra.textContent = String(item.extra ?? '');
        els.seleccionDocente.classList.remove('bloque-oculto');
    }

    function setMode(modo) {
        state.mode = (modo === 'directo') ? 'directo' : 'curso';
        els.hiddenModo.value = state.mode;

        if (state.mode === 'curso') {
            els.seccionCurso.classList.remove('bloque-oculto');
            els.buscarMateria.disabled = !state.curso;
            if (!state.curso) {
                setStatus(els.estadoMateria, 'Primero seleccioná un curso.', '');
            }
        } else {
            els.seccionCurso.classList.add('bloque-oculto');
            clearCurso();
            els.buscarMateria.disabled = false;
            setStatus(els.estadoMateria, 'Buscá una materia por nombre, turno, grupo o curso.', '');
        }

        clearMateria();
        clearResults(els.resultadosMateria, 'Escribí al menos 2 caracteres para buscar.');
        updateSubmitState();
    }

    function clearCurso() {
        state.curso = null;
        els.buscarCurso.value = '';
        paintSelection('curso', null);
        clearResults(els.resultadosCurso, 'Escribí al menos 2 caracteres para buscar cursos.');
        if (state.mode === 'curso') {
            els.buscarMateria.disabled = true;
        }
    }

    function clearMateria() {
        state.materia = null;
        state.gruposMateria = [];
        els.hiddenMateria.value = '';
        els.buscarMateria.value = '';
        paintSelection('materia', null);
        clearResults(els.resultadosMateria, 'Escribí al menos 2 caracteres para buscar materias.');
        clearGrupo();
        updateSubmitState();
    }

    function clearGrupo() {
        state.grupo = null;
        els.hiddenGrupo.value = '';
        els.selectGrupo.innerHTML = '<option value="">Seleccioná un grupo</option>';
        els.selectGrupo.disabled = true;
        setStatus(els.estadoGrupo, 'Seleccioná una materia para ver los grupos disponibles.', '');
    }

    function renderGruposMateria(grupos, grupoSeleccionado = null) {
        state.gruposMateria = Array.isArray(grupos)
            ? grupos.map((g) => Number(g)).filter((g) => Number.isInteger(g) && g > 0)
            : [];

        els.selectGrupo.innerHTML = '<option value="">Seleccioná un grupo</option>';
        if (state.gruposMateria.length === 0) {
            state.grupo = null;
            els.selectGrupo.disabled = true;
            setStatus(els.estadoGrupo, 'Esta materia no requiere selección de grupo.', '');
            updateSubmitState();
            return;
        }

        state.gruposMateria.forEach((grupo) => {
            const opt = document.createElement('option');
            opt.value = String(grupo);
            opt.textContent = `Grupo ${grupo}`;
            els.selectGrupo.appendChild(opt);
        });

        let grupoFinal = null;
        if (Number.isInteger(grupoSeleccionado) && state.gruposMateria.includes(grupoSeleccionado)) {
            grupoFinal = grupoSeleccionado;
        } else {
            grupoFinal = state.gruposMateria[0];
        }

        state.grupo = grupoFinal;
        els.selectGrupo.value = String(grupoFinal);
        els.selectGrupo.disabled = false;
        setStatus(els.estadoGrupo, `Seleccioná grupo para asignar docente (${state.gruposMateria.join(', ')}).`, '');
        updateSubmitState();
    }

    function clearDocente() {
        state.docente = null;
        els.hiddenDocente.value = '';
        els.buscarDocente.value = '';
        paintSelection('docente', null);
        clearResults(els.resultadosDocente, 'Escribí al menos 2 caracteres para buscar docentes.');
        updateSubmitState();
    }

    function selectCurso(item) {
        state.curso = {
            id: Number(item.id || 0),
            label: String(item.label || ''),
            extra: String(item.extra || '')
        };
        paintSelection('curso', state.curso);
        clearResults(els.resultadosCurso, 'Curso seleccionado. Podés buscar otro si querés cambiarlo.');
        els.buscarMateria.disabled = false;
        setStatus(els.estadoMateria, 'Buscá materias dentro del curso seleccionado.', '');
        clearMateria();
        els.buscarMateria.focus();
    }

    function selectMateria(item) {
        state.materia = {
            id: Number(item.id || 0),
            label: String(item.label || ''),
            extra: String(item.extra || ''),
            grupos: Array.isArray(item.grupos) ? item.grupos.map((g) => Number(g)).filter((g) => g > 0) : []
        };
        els.hiddenMateria.value = String(state.materia.id);
        paintSelection('materia', state.materia);
        renderGruposMateria(state.materia.grupos, state.grupo);
        clearResults(els.resultadosMateria, 'Materia seleccionada.');
        updateSubmitState();
    }

    function selectDocente(item) {
        state.docente = {
            id: Number(item.id || 0),
            label: String(item.label || ''),
            extra: String(item.extra || '')
        };
        els.hiddenDocente.value = String(state.docente.id);
        paintSelection('docente', state.docente);
        clearResults(els.resultadosDocente, 'Docente seleccionado.');
        updateSubmitState();
    }

    async function fetchResultados(key, url, estadoEl, resultadosEl, onPick) {
        const seq = ++state.reqSeq[key];
        setStatus(estadoEl, 'Buscando...', '');

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) {
                throw new Error('Respuesta HTTP ' + response.status);
            }
            const data = await response.json();
            if (seq !== state.reqSeq[key]) {
                return;
            }

            renderResults(resultadosEl, data, onPick);
            if (Array.isArray(data) && data.length > 0) {
                setStatus(estadoEl, `${data.length} resultado(s). Navegá con Tab y Enter para seleccionar.`, 'ok');
            } else {
                setStatus(estadoEl, 'No se encontraron resultados.', '');
            }
        } catch (error) {
            if (seq !== state.reqSeq[key]) {
                return;
            }
            clearResults(resultadosEl, 'Ocurrió un error al buscar. Intentá de nuevo.');
            setStatus(estadoEl, 'No se pudo completar la búsqueda.', 'err');
        }
    }

    function wireSearchCurso() {
        els.buscarCurso.addEventListener('input', () => {
            if (state.mode !== 'curso') {
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

    function wireSearchMateria() {
        els.buscarMateria.addEventListener('input', () => {
            const q = els.buscarMateria.value.trim();

            if (state.mode === 'curso' && !state.curso) {
                setStatus(els.estadoMateria, 'Primero seleccioná un curso.', 'err');
                clearResults(els.resultadosMateria, 'No podés buscar materias sin curso en este modo.');
                return;
            }

            if (q.length < MIN_QUERY) {
                clearResults(els.resultadosMateria, 'Escribí al menos 2 caracteres para buscar materias.');
                setStatus(els.estadoMateria, 'Escribí al menos 2 caracteres.', '');
                return;
            }

            debounce('materia', () => {
                const params = new URLSearchParams({
                    modo: state.mode,
                    q,
                    limit: String(LIMIT),
                    offset: '0'
                });

                if (state.mode === 'curso' && state.curso) {
                    params.set('id_curso', String(state.curso.id));
                }

                fetchResultados('materia', `${ENDPOINTS.materias}?${params.toString()}`, els.estadoMateria, els.resultadosMateria, selectMateria);
            });
        });
    }

    function wireSearchDocente() {
        els.buscarDocente.addEventListener('input', () => {
            const q = els.buscarDocente.value.trim();
            if (q.length < MIN_QUERY) {
                clearResults(els.resultadosDocente, 'Escribí al menos 2 caracteres para buscar docentes.');
                setStatus(els.estadoDocente, 'Escribí al menos 2 caracteres.', '');
                return;
            }

            debounce('docente', () => {
                const params = new URLSearchParams({ q, limit: String(LIMIT), offset: '0' });
                fetchResultados('docente', `${ENDPOINTS.docentes}?${params.toString()}`, els.estadoDocente, els.resultadosDocente, selectDocente);
            });
        });
    }

    function wireGrupoSelect() {
        els.selectGrupo.addEventListener('change', () => {
            const val = Number(els.selectGrupo.value || 0);
            if (state.gruposMateria.length === 0) {
                state.grupo = null;
            } else if (state.gruposMateria.includes(val)) {
                state.grupo = val;
            } else {
                state.grupo = null;
            }
            updateSubmitState();
        });
    }

    function hydrateFromServer() {
        const modoServidor = (estadoInicial.modo === 'directo') ? 'directo' : 'curso';
        if (modoServidor === 'directo') {
            els.modoDirecto.checked = true;
        } else {
            els.modoCurso.checked = true;
        }

        setMode(modoServidor);

        if (estadoInicial.curso && Number(estadoInicial.curso.id || 0) > 0) {
            state.curso = {
                id: Number(estadoInicial.curso.id),
                label: String(estadoInicial.curso.label || ''),
                extra: String(estadoInicial.curso.extra || '')
            };
            paintSelection('curso', state.curso);
            if (state.mode === 'curso') {
                els.buscarMateria.disabled = false;
            }
        }

        if (estadoInicial.materia && Number(estadoInicial.materia.id || 0) > 0) {
            state.materia = {
                id: Number(estadoInicial.materia.id),
                label: String(estadoInicial.materia.label || ''),
                extra: String(estadoInicial.materia.extra || ''),
                grupos: Array.isArray(estadoInicial.materia.grupos)
                    ? estadoInicial.materia.grupos.map((g) => Number(g)).filter((g) => g > 0)
                    : []
            };
            els.hiddenMateria.value = String(state.materia.id);
            paintSelection('materia', state.materia);
            const grupoInicial = Number(estadoInicial.grupo || 0);
            renderGruposMateria(state.materia.grupos, Number.isInteger(grupoInicial) ? grupoInicial : null);
        }

        if (estadoInicial.docente && Number(estadoInicial.docente.id || 0) > 0) {
            state.docente = {
                id: Number(estadoInicial.docente.id),
                label: String(estadoInicial.docente.label || ''),
                extra: String(estadoInicial.docente.extra || '')
            };
            els.hiddenDocente.value = String(state.docente.id);
            paintSelection('docente', state.docente);
        }

        updateSubmitState();
    }

    function wireModeSwitch() {
        els.modoCurso.addEventListener('change', () => {
            if (els.modoCurso.checked) {
                setMode('curso');
            }
        });

        els.modoDirecto.addEventListener('change', () => {
            if (els.modoDirecto.checked) {
                setMode('directo');
            }
        });
    }

    function wireClearButtons() {
        els.limpiarCurso.addEventListener('click', () => {
            clearCurso();
            clearMateria();
            setStatus(els.estadoCurso, 'Curso limpiado. Buscá otro curso.', '');
            if (state.mode === 'curso') {
                setStatus(els.estadoMateria, 'Primero seleccioná un curso.', '');
            }
        });

        els.limpiarMateria.addEventListener('click', () => {
            clearMateria();
            setStatus(els.estadoMateria, 'Materia limpiada. Buscá otra materia.', '');
        });

        els.limpiarDocente.addEventListener('click', () => {
            clearDocente();
            setStatus(els.estadoDocente, 'Docente limpiado. Buscá otro docente.', '');
        });
    }

    function wireSubmitGuard() {
        document.getElementById('formAsignacionDocente').addEventListener('submit', (event) => {
            if (!state.materia || !state.docente) {
                event.preventDefault();
                alert('Seleccioná una materia y un docente antes de asignar.');
                return;
            }

            if (state.gruposMateria.length > 0 && (!state.grupo || !state.gruposMateria.includes(state.grupo))) {
                event.preventDefault();
                alert('Seleccioná un grupo válido para la materia antes de asignar.');
                return;
            }

            els.hiddenMateria.value = String(state.materia.id);
            els.hiddenDocente.value = String(state.docente.id);
            els.hiddenGrupo.value = state.grupo ? String(state.grupo) : '';
            els.hiddenModo.value = state.mode;
        });
    }

    clearResults(els.resultadosCurso, 'Escribí al menos 2 caracteres para buscar cursos.');
    clearResults(els.resultadosMateria, 'Escribí al menos 2 caracteres para buscar materias.');
    clearResults(els.resultadosDocente, 'Escribí al menos 2 caracteres para buscar docentes.');
    clearGrupo();

    wireModeSwitch();
    wireSearchCurso();
    wireSearchMateria();
    wireSearchDocente();
    wireGrupoSelect();
    wireClearButtons();
    wireSubmitGuard();
    hydrateFromServer();
})();
</script>
</body>
</html>
