<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_inicio_sesion();

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_persona);
$es_admin = in_array('administrador', $tipos_usuario, true);
$es_preceptor = in_array('preceptor', $tipos_usuario, true);

if (!$es_admin && !$es_preceptor) {
    http_response_code(403);
    exit('Acceso denegado. Solo administración o preceptoría pueden editar materias.');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirigir('php/listados/lista_materias.php');
}

$mat = db_fetch_one(
    $con,
    "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo, m.id_curso,
            c.grado, s.seccion, mo.moda
     FROM materias AS m
     INNER JOIN cursos AS c ON c.id_curso = m.id_curso
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
     WHERE m.id_materia = ?
     LIMIT 1",
    "i",
    [$id]
);
if (!$mat) {
    http_response_code(404);
    exit('Materia no encontrada.');
}

if (!$es_admin) {
    $permiso = db_fetch_one(
        $con,
        "SELECT 1
         FROM preceptor_x_curso
         WHERE id_persona = ? AND id_curso = ?
         LIMIT 1",
        "ii",
        [$id_persona, (int)$mat['id_curso']]
    );
    if (!$permiso) {
        http_response_code(403);
        exit('No tenés permisos para editar materias de este curso.');
    }
}

$grupos_permitidos = grupos_permitidos_por_seccion((string)$mat['seccion']);
if (count($grupos_permitidos) !== 2) {
    http_response_code(422);
    exit('La sección del curso no tiene una configuración de grupos válida.');
}

$grupos_actuales = grupos_de_materia($con, $id);
if ($grupos_actuales === [] && (int)$mat['grupo'] > 0) {
    $grupos_actuales = [(int)$mat['grupo']];
}

$error_form = '';
$valores = [
    'nombre_materia' => (string)$mat['nombre_materia'],
    'turno' => (string)$mat['turno'],
    'es_taller' => count($grupos_actuales) > 1,
    'grupos' => $grupos_actuales !== [] ? $grupos_actuales : [(int)$grupos_permitidos[0]],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $valores['nombre_materia'] = trim((string)($_POST['nombre_materia'] ?? ''));
    $valores['turno'] = trim((string)($_POST['turno'] ?? ''));
    $valores['es_taller'] = isset($_POST['es_taller']) && (string)($_POST['es_taller'] === '1');
    $valores['grupos'] = array_values(array_unique(array_filter(
        array_map('intval', (array)($_POST['grupos'] ?? [])),
        static fn($g) => $g > 0
    )));

    if ($valores['nombre_materia'] === '' || $valores['turno'] === '') {
        $error_form = 'Nombre y turno son obligatorios.';
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
            $grupos_viejos = $grupos_actuales;
            $grupos_nuevos = array_values(array_unique($grupos_finales));
            sort($grupos_viejos);
            sort($grupos_nuevos);

            $grupos_a_eliminar = array_values(array_diff($grupos_viejos, $grupos_nuevos));
            $grupos_a_agregar = array_values(array_diff($grupos_nuevos, $grupos_viejos));

            if ($grupos_a_eliminar !== []) {
                $placeholders = implode(',', array_fill(0, count($grupos_a_eliminar), '?'));
                $tipos_del = 'i' . str_repeat('i', count($grupos_a_eliminar));
                $params_del = array_merge([$id], $grupos_a_eliminar);

                $stmt_del_doc = mysqli_prepare(
                    $con,
                    "DELETE FROM docentes_x_materia
                     WHERE id_materia = ?
                       AND id_grupo IN ($placeholders)"
                );
                if (!$stmt_del_doc) {
                    throw new RuntimeException('No se pudieron limpiar asignaciones de docentes en grupos removidos.');
                }
                mysqli_stmt_bind_param($stmt_del_doc, $tipos_del, ...$params_del);
                if (!mysqli_stmt_execute($stmt_del_doc)) {
                    mysqli_stmt_close($stmt_del_doc);
                    throw new RuntimeException('No se pudieron limpiar asignaciones de docentes en grupos removidos.');
                }
                mysqli_stmt_close($stmt_del_doc);

                $stmt_del_mxg = mysqli_prepare(
                    $con,
                    "DELETE FROM materias_x_grupo
                     WHERE id_materia = ?
                       AND id_grupo IN ($placeholders)"
                );
                if (!$stmt_del_mxg) {
                    throw new RuntimeException('No se pudieron actualizar los grupos de la materia.');
                }
                mysqli_stmt_bind_param($stmt_del_mxg, $tipos_del, ...$params_del);
                if (!mysqli_stmt_execute($stmt_del_mxg)) {
                    mysqli_stmt_close($stmt_del_mxg);
                    throw new RuntimeException('No se pudieron actualizar los grupos de la materia.');
                }
                mysqli_stmt_close($stmt_del_mxg);
            }

            if ($grupos_a_agregar !== []) {
                $stmt_add = mysqli_prepare(
                    $con,
                    "INSERT INTO materias_x_grupo (id_materia, id_grupo) VALUES (?, ?)"
                );
                if (!$stmt_add) {
                    throw new RuntimeException('No se pudieron agregar nuevos grupos a la materia.');
                }
                foreach ($grupos_a_agregar as $id_grupo) {
                    mysqli_stmt_bind_param($stmt_add, "ii", $id, $id_grupo);
                    if (!mysqli_stmt_execute($stmt_add)) {
                        mysqli_stmt_close($stmt_add);
                        throw new RuntimeException('No se pudieron agregar nuevos grupos a la materia.');
                    }
                }
                mysqli_stmt_close($stmt_add);
            }

            $grupo_legacy = (int)$grupos_nuevos[0];
            $stmt_update = mysqli_prepare(
                $con,
                "UPDATE materias
                 SET nombre_materia = ?, turno = ?, grupo = ?
                 WHERE id_materia = ?"
            );
            if (!$stmt_update) {
                throw new RuntimeException('No se pudo actualizar la materia.');
            }
            mysqli_stmt_bind_param($stmt_update, "ssii", $valores['nombre_materia'], $valores['turno'], $grupo_legacy, $id);
            if (!mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);
                throw new RuntimeException('No se pudo actualizar la materia.');
            }
            mysqli_stmt_close($stmt_update);

            mysqli_commit($con);
            redirigir('php/listados/lista_materias.php?estado=ok&msg=' . urlencode('Materia actualizada correctamente.'));
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
    <title>PLEI — Editar materia</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
<div class="form-card form-card-wide">
    <h2>Editar materia</h2>

    <?php if ($error_form !== ''): ?>
    <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?php echo htmlspecialchars($error_form); ?></div>
    <?php endif; ?>

    <form method="post" id="formEditarMateria">
        <?php campo_csrf(); ?>
        <div class="mb-3">
            <label class="form-label">Curso</label>
            <input
                type="text"
                class="form-control"
                value="<?php echo htmlspecialchars($mat['grado'] . '° ' . $mat['seccion'] . ' — ' . $mat['moda']); ?>"
                disabled
            >
        </div>
        <div class="mb-3">
            <label class="form-label">Nombre de la materia</label>
            <input type="text" name="nombre_materia" class="form-control" value="<?php echo htmlspecialchars($valores['nombre_materia']); ?>" required maxlength="50">
        </div>
        <div class="mb-3">
            <label class="form-label">Turno</label>
            <input type="text" name="turno" class="form-control" value="<?php echo htmlspecialchars($valores['turno']); ?>" required maxlength="30">
        </div>
        <div class="mb-3 form-check">
            <input class="form-check-input" type="checkbox" value="1" id="esTaller" name="es_taller" <?php echo $valores['es_taller'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="esTaller">
                Materia de taller (permite elegir 1 o 2 grupos)
            </label>
        </div>
        <div class="mb-4">
            <label class="form-label">Grupos de la materia</label>
            <div class="multi-choice-block">
                <div class="multi-choice-helper" id="gruposAyuda"></div>
                <div class="multi-choice-grid mt-2" id="gruposGrid"></div>
            </div>
        </div>

        <button type="submit" class="btn-plei-submit">Guardar cambios</button>
        <a href="<?php echo url('php/listados/lista_materias.php'); ?>" class="btn-plei-cancel mt-2 w-100">Cancelar</a>
    </form>
</div>

<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const esTaller = document.getElementById('esTaller');
    const ayuda = document.getElementById('gruposAyuda');
    const grid = document.getElementById('gruposGrid');
    const gruposPermitidos = <?php echo json_encode(array_values(array_map('intval', $grupos_permitidos))); ?>;
    const gruposPost = <?php echo json_encode(array_values(array_map('intval', $valores['grupos']))); ?>;

    function render() {
        grid.innerHTML = '';
        const modoTaller = esTaller.checked;

        ayuda.textContent = modoTaller
            ? `Seleccioná 1 o 2 grupos (${gruposPermitidos.join(' y ')}).`
            : `No taller: se usará automáticamente el grupo ${gruposPermitidos[0]}.`;

        gruposPermitidos.forEach((grupo, idx) => {
            const label = document.createElement('label');
            label.className = 'multi-choice-item';

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'grupos[]';
            input.value = String(grupo);
            input.className = 'multi-choice-input';
            input.disabled = !modoTaller;

            if (modoTaller) {
                if (gruposPost.length > 0) {
                    input.checked = gruposPost.includes(grupo);
                } else {
                    input.checked = idx === 0;
                }
            } else {
                input.checked = idx === 0;
            }

            input.addEventListener('change', () => {
                const activos = Array.from(grid.querySelectorAll('input:checked'));
                if (activos.length > 2) {
                    input.checked = false;
                }
            });

            const card = document.createElement('span');
            card.className = 'multi-choice-card';
            card.innerHTML = `<span><span class="multi-choice-title">Grupo ${grupo}</span></span>`;

            label.appendChild(input);
            label.appendChild(card);
            grid.appendChild(label);
        });
    }

    esTaller.addEventListener('change', render);
    render();
})();
</script>
</body>
</html>
