<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol(['administrador', 'preceptor']);

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);

if ($es_admin) {
    $cursos = db_fetch_all(
        $con,
        "SELECT c.id_curso, c.grado, m.moda, s.seccion
         FROM cursos AS c
         INNER JOIN modalidad AS m ON c.id_modalidad = m.id_modalidad
         INNER JOIN secciones AS s ON c.id_seccion = s.id_seccion
         ORDER BY c.grado ASC, s.seccion ASC"
    );
} else {
    $cursos = db_fetch_all(
        $con,
        "SELECT DISTINCT c.id_curso, c.grado, m.moda, s.seccion
         FROM cursos AS c
         INNER JOIN modalidad AS m ON c.id_modalidad = m.id_modalidad
         INNER JOIN secciones AS s ON c.id_seccion = s.id_seccion
         INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso
         WHERE pc.id_persona = ?
         ORDER BY c.grado ASC, s.seccion ASC",
        "i",
        [$id_persona]
    );
}

$ids_cursos_habilitados = array_map(static fn($fila) => (int)$fila['id_curso'], $cursos);
$error_form = '';

$valores = [
    'nombre' => '',
    'turno' => '',
    'id_curso' => 0,
    'es_taller' => false,
    'grupos' => [],
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificar_csrf();

    $valores['nombre'] = trim((string)($_POST['nombre'] ?? ''));
    $valores['turno'] = trim((string)($_POST['turno'] ?? ''));
    $valores['id_curso'] = (int)($_POST['id_curso'] ?? 0);
    $valores['es_taller'] = isset($_POST['es_taller']) && (string)($_POST['es_taller'] === '1');
    $valores['grupos'] = array_values(array_unique(array_filter(
        array_map('intval', (array)($_POST['grupos'] ?? [])),
        static fn($g) => $g > 0
    )));

    if ($valores['nombre'] === '' || $valores['turno'] === '' || $valores['id_curso'] <= 0) {
        $error_form = 'Nombre, turno y curso son obligatorios.';
    } elseif (!in_array($valores['id_curso'], $ids_cursos_habilitados, true)) {
        $error_form = 'No tenés permisos para operar ese curso.';
    }

    $grupos_permitidos = [];
    if ($error_form === '') {
        $grupos_permitidos = grupos_permitidos_por_curso($con, $valores['id_curso']);
        if (count($grupos_permitidos) !== 2) {
            $error_form = 'La sección de este curso no tiene una configuración de grupos válida.';
        }
    }

    $grupos_finales = [];
    if ($error_form === '') {
        if ($valores['es_taller']) {
            if ($valores['grupos'] === []) {
                $error_form = 'Para materias de taller debés seleccionar al menos un grupo.';
            } elseif (count($valores['grupos']) > 2) {
                $error_form = 'Solo podés seleccionar hasta 2 grupos.';
            } else {
                foreach ($valores['grupos'] as $g) {
                    if (!in_array($g, $grupos_permitidos, true)) {
                        $error_form = 'Seleccionaste un grupo que no corresponde a la sección del curso.';
                        break;
                    }
                }
            }
            $grupos_finales = $valores['grupos'];
        } else {
            $grupos_finales = [(int)$grupos_permitidos[0]];
        }
    }

    if ($error_form === '' && $grupos_finales !== []) {
        mysqli_begin_transaction($con);
        try {
            $grupo_legacy = (int)$grupos_finales[0];

            $stmt = mysqli_prepare(
                $con,
                "INSERT INTO materias (nombre_materia, turno, grupo, id_curso) VALUES (?, ?, ?, ?)"
            );
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar el alta de materia.');
            }
            mysqli_stmt_bind_param($stmt, "ssii", $valores['nombre'], $valores['turno'], $grupo_legacy, $valores['id_curso']);
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                throw new RuntimeException('No se pudo guardar la materia.');
            }
            mysqli_stmt_close($stmt);

            $id_materia = (int)mysqli_insert_id($con);
            if ($id_materia <= 0) {
                throw new RuntimeException('No se pudo obtener la materia creada.');
            }

            $stmt_grupo = mysqli_prepare(
                $con,
                "INSERT INTO materias_x_grupo (id_materia, id_grupo) VALUES (?, ?)"
            );
            if (!$stmt_grupo) {
                throw new RuntimeException('No se pudo preparar la relación de grupos.');
            }

            foreach ($grupos_finales as $id_grupo) {
                mysqli_stmt_bind_param($stmt_grupo, "ii", $id_materia, $id_grupo);
                if (!mysqli_stmt_execute($stmt_grupo)) {
                    mysqli_stmt_close($stmt_grupo);
                    throw new RuntimeException('No se pudo guardar uno de los grupos de la materia.');
                }
            }
            mysqli_stmt_close($stmt_grupo);

            mysqli_commit($con);
            redirigir('php/listados/lista_materias.php?estado=ok&msg=' . urlencode('Materia creada correctamente.'));
        } catch (Throwable $e) {
            mysqli_rollback($con);
            $error_form = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Alta de Materia</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
<div class="form-card form-card-wide">
    <h2 class="text-center fw-bold mb-4">Alta de Materia</h2>

    <?php if ($error_form !== ''): ?>
    <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?php echo htmlspecialchars($error_form); ?></div>
    <?php endif; ?>

    <form autocomplete="off" method="post" id="formAltaMateria">
        <?php campo_csrf(); ?>
        <input type="hidden" name="grupo" id="grupoLegacyInput" value="">

        <div class="mb-3">
            <label class="form-label">Nombre de la materia</label>
            <input
                type="text"
                name="nombre"
                class="form-control"
                placeholder="Ej: Matemática"
                required
                maxlength="50"
                value="<?php echo htmlspecialchars($valores['nombre']); ?>"
            >
        </div>
        <div class="mb-3">
            <label class="form-label">Turno</label>
            <input
                type="text"
                name="turno"
                class="form-control"
                placeholder="Mañana / Tarde"
                required
                maxlength="30"
                value="<?php echo htmlspecialchars($valores['turno']); ?>"
            >
        </div>
        <div class="mb-3">
            <label class="form-label">Curso</label>
            <select name="id_curso" id="cursoMateria" class="form-select" required>
                <option value="">Seleccioná un curso</option>
                <?php foreach ($cursos as $c): ?>
                <?php
                $grupos = grupos_permitidos_por_seccion((string)$c['seccion']);
                $g1 = $grupos[0] ?? 0;
                $g2 = $grupos[1] ?? 0;
                ?>
                <option
                    value="<?php echo (int)$c['id_curso']; ?>"
                    data-seccion="<?php echo htmlspecialchars((string)$c['seccion']); ?>"
                    data-g1="<?php echo (int)$g1; ?>"
                    data-g2="<?php echo (int)$g2; ?>"
                    <?php echo (int)$valores['id_curso'] === (int)$c['id_curso'] ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars($c['grado'] . '° ' . $c['seccion'] . ' — ' . $c['moda']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3 form-check">
            <input
                class="form-check-input"
                type="checkbox"
                value="1"
                id="esTaller"
                name="es_taller"
                <?php echo $valores['es_taller'] ? 'checked' : ''; ?>
            >
            <label class="form-check-label" for="esTaller">
                Materia de taller (permite elegir 1 o 2 grupos)
            </label>
        </div>
        <div class="mb-4">
            <label class="form-label">Grupos de la materia</label>
            <div id="gruposMateriaBox" class="multi-choice-block">
                <div class="multi-choice-helper" id="gruposAyuda">Seleccioná un curso para ver los grupos disponibles.</div>
                <div class="multi-choice-grid mt-2" id="gruposMateriaGrid"></div>
            </div>
        </div>
        <button type="submit" class="btn-plei-submit">Dar de alta</button>
    </form>
    <div class="text-center mt-3">
        <a href="<?php echo url('home.php'); ?>" class="btn-plei-cancel">Volver</a>
    </div>
</div>

<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const cursoSel = document.getElementById('cursoMateria');
    const esTaller = document.getElementById('esTaller');
    const grid = document.getElementById('gruposMateriaGrid');
    const ayuda = document.getElementById('gruposAyuda');
    const grupoLegacy = document.getElementById('grupoLegacyInput');
    const gruposPost = <?php echo json_encode(array_map('intval', $valores['grupos'])); ?>;

    function getGruposCurso() {
        const opt = cursoSel.options[cursoSel.selectedIndex];
        if (!opt || !opt.value) return [];
        const g1 = Number(opt.dataset.g1 || 0);
        const g2 = Number(opt.dataset.g2 || 0);
        return [g1, g2].filter((n) => Number.isInteger(n) && n > 0);
    }

    function renderGrupos() {
        grid.innerHTML = '';
        const grupos = getGruposCurso();
        if (!grupos.length) {
            ayuda.textContent = 'La sección de este curso no tiene una configuración de grupos válida.';
            grupoLegacy.value = '';
            return;
        }

        const enModoTaller = esTaller.checked;
        ayuda.textContent = enModoTaller
            ? `Seleccioná 1 o 2 grupos (${grupos.join(' y ')}).`
            : `No taller: se usará automáticamente el grupo ${grupos[0]}.`;

        grupos.forEach((grupo, idx) => {
            const label = document.createElement('label');
            label.className = 'multi-choice-item';

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'grupos[]';
            input.value = String(grupo);
            input.className = 'multi-choice-input';
            input.disabled = !enModoTaller;

            if (enModoTaller) {
                if (gruposPost.length > 0) {
                    input.checked = gruposPost.includes(grupo);
                } else if (idx === 0) {
                    input.checked = true;
                }
            } else {
                input.checked = idx === 0;
            }

            input.addEventListener('change', () => {
                const activos = Array.from(grid.querySelectorAll('input:checked'));
                if (activos.length > 2) {
                    input.checked = false;
                }
                syncLegacy();
            });

            const card = document.createElement('span');
            card.className = 'multi-choice-card';
            card.innerHTML = `<span><span class="multi-choice-title">Grupo ${grupo}</span></span>`;

            label.appendChild(input);
            label.appendChild(card);
            grid.appendChild(label);
        });

        syncLegacy();
    }

    function syncLegacy() {
        const activos = Array.from(grid.querySelectorAll('input:checked')).map((el) => Number(el.value));
        if (activos.length > 0) {
            grupoLegacy.value = String(activos[0]);
            return;
        }
        const grupos = getGruposCurso();
        grupoLegacy.value = grupos.length ? String(grupos[0]) : '';
    }

    cursoSel.addEventListener('change', renderGrupos);
    esTaller.addEventListener('change', renderGrupos);

    renderGrupos();
})();
</script>
</body>
</html>
