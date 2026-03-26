<?php
include '../conesion.php';
include '../config.php';
include './helpers.php';
session_start();
exigir_rol(['administrador', 'preceptor']);

if (!boletin_modulo_disponible($con)) {
    http_response_code(500);
    exit('El modulo de boletin no esta disponible.');
}

$id_usuario = (int)($_SESSION['id_persona'] ?? 0);
$tipos = obtener_tipos_usuario($con, $id_usuario);
$es_admin = in_array('administrador', $tipos, true);

if ($es_admin) {
    $cursos = db_fetch_all(
        $con,
        "SELECT c.id_curso, c.grado, s.seccion, mo.moda
         FROM cursos AS c
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         ORDER BY c.grado ASC, s.seccion ASC"
    );
} else {
    $cursos = db_fetch_all(
        $con,
        "SELECT c.id_curso, c.grado, s.seccion, mo.moda
         FROM cursos AS c
         INNER JOIN preceptor_x_curso AS pc ON pc.id_curso = c.id_curso
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         WHERE pc.id_persona = ?
         ORDER BY c.grado ASC, s.seccion ASC",
        'i',
        [$id_usuario]
    );
}

$ids_cursos = array_map(static fn($c) => (int)$c['id_curso'], $cursos);
$curso_sel = (int)($_REQUEST['curso'] ?? 0);
if ($curso_sel <= 0 && $cursos !== []) {
    $curso_sel = (int)$cursos[0]['id_curso'];
}
if ($curso_sel > 0 && !in_array($curso_sel, $ids_cursos, true)) {
    http_response_code(403);
    exit('No tenes permisos para ese curso.');
}

$mensaje_tipo = strtolower(trim((string)($_GET['estado'] ?? '')));
if (!in_array($mensaje_tipo, ['ok', 'err'], true)) {
    $mensaje_tipo = '';
}
$mensaje_texto = trim((string)($_GET['msg'] ?? ''));

$materias_grupo = [];
if ($curso_sel > 0) {
    $materias_grupo = db_fetch_all(
        $con,
        "SELECT m.id_materia, m.nombre_materia,
                GROUP_CONCAT(mxg.id_grupo ORDER BY mxg.id_grupo SEPARATOR ',') AS grupos
         FROM materias AS m
         INNER JOIN materias_x_grupo AS mxg ON mxg.id_materia = m.id_materia
         WHERE m.id_curso = ?
         GROUP BY m.id_materia, m.nombre_materia
         HAVING COUNT(mxg.id_grupo) > 1
         ORDER BY m.nombre_materia ASC",
        'i',
        [$curso_sel]
    );
}

$ids_materias = array_map(static fn($m) => (int)$m['id_materia'], $materias_grupo);
$materia_sel = (int)($_REQUEST['materia'] ?? 0);
if ($materia_sel <= 0 && $materias_grupo !== []) {
    $materia_sel = (int)$materias_grupo[0]['id_materia'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = trim((string)($_POST['accion'] ?? ''));
    $curso_post = (int)($_POST['curso'] ?? 0);
    $materia_post = (int)($_POST['materia'] ?? 0);
    $curso_sel = $curso_post > 0 ? $curso_post : $curso_sel;
    $materia_sel = $materia_post > 0 ? $materia_post : $materia_sel;

    if ($accion === 'guardar') {
        if (!in_array($curso_sel, $ids_cursos, true)) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Curso invalido.';
        } elseif (!in_array($materia_sel, $ids_materias, true)) {
            $mensaje_tipo = 'err';
            $mensaje_texto = 'Materia invalida para asignar grupos.';
        } else {
            $grupos_validos = grupos_de_materia($con, $materia_sel);
            $asignaciones = (array)($_POST['grupo'] ?? []);
            $filtro_activo_guardado = condicion_persona_activa($con, 'p');
            $alumnos_curso = db_fetch_all(
                $con,
                "SELECT p.id_persona
                 FROM alumnos_x_curso AS axc
                 INNER JOIN personas AS p ON p.id_persona = axc.id_persona
                 WHERE axc.id_curso = ?
                   $filtro_activo_guardado",
                'i',
                [$curso_sel]
            );
            $ids_alumnos = array_map(static fn($a) => (int)$a['id_persona'], $alumnos_curso);

            mysqli_begin_transaction($con);
            try {
                $stmt_up = mysqli_prepare(
                    $con,
                    "INSERT INTO alumnos_x_materia (id_persona, id_materia, id_grupo)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE id_grupo = VALUES(id_grupo)"
                );
                $stmt_del = mysqli_prepare(
                    $con,
                    "DELETE FROM alumnos_x_materia
                     WHERE id_persona = ? AND id_materia = ?"
                );
                if (!$stmt_up || !$stmt_del) {
                    throw new RuntimeException('No se pudo preparar la actualizacion de grupos.');
                }

                foreach ($ids_alumnos as $id_alumno) {
                    $grupo = isset($asignaciones[$id_alumno]) ? (int)$asignaciones[$id_alumno] : 0;
                    if ($grupo > 0) {
                        if (!in_array($grupo, $grupos_validos, true)) {
                            throw new RuntimeException('Se detecto un grupo invalido en la carga.');
                        }
                        mysqli_stmt_bind_param($stmt_up, 'iii', $id_alumno, $materia_sel, $grupo);
                        if (!mysqli_stmt_execute($stmt_up)) {
                            throw new RuntimeException('No se pudo guardar una asignacion de grupo.');
                        }
                    } else {
                        mysqli_stmt_bind_param($stmt_del, 'ii', $id_alumno, $materia_sel);
                        if (!mysqli_stmt_execute($stmt_del)) {
                            throw new RuntimeException('No se pudo limpiar una asignacion vacia.');
                        }
                    }
                }

                mysqli_stmt_close($stmt_up);
                mysqli_stmt_close($stmt_del);
                mysqli_commit($con);
                $mensaje_tipo = 'ok';
                $mensaje_texto = 'Asignaciones por grupo guardadas.';
            } catch (Throwable $e) {
                if (isset($stmt_up) && $stmt_up instanceof mysqli_stmt) {
                    mysqli_stmt_close($stmt_up);
                }
                if (isset($stmt_del) && $stmt_del instanceof mysqli_stmt) {
                    mysqli_stmt_close($stmt_del);
                }
                mysqli_rollback($con);
                $mensaje_tipo = 'err';
                $mensaje_texto = $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mensaje_texto !== '') {
    $estado_redir = $mensaje_tipo === 'ok' ? 'ok' : 'err';
    $params_redir = [
        'curso' => $curso_sel,
        'materia' => $materia_sel,
        'estado' => $estado_redir,
        'msg' => $mensaje_texto,
    ];
    redirigir('php/boletin/preceptor_asignar_grupos.php?' . http_build_query($params_redir));
}

$grupo_map = [];
foreach ($materias_grupo as $m) {
    $grupo_map[(int)$m['id_materia']] = array_values(array_filter(array_map('intval', explode(',', (string)$m['grupos'])), static fn($g) => $g > 0));
}
$grupos_sel = $grupo_map[$materia_sel] ?? [];

$alumnos = [];
if ($curso_sel > 0 && $materia_sel > 0) {
    $filtro_activo_alumnos = condicion_persona_activa($con, 'p');
    $alumnos = db_fetch_all(
        $con,
        "SELECT p.id_persona, p.apellido, p.nombre, p.dni,
                axm.id_grupo
         FROM alumnos_x_curso AS axc
         INNER JOIN personas AS p ON p.id_persona = axc.id_persona
         LEFT JOIN alumnos_x_materia AS axm
                ON axm.id_persona = p.id_persona
               AND axm.id_materia = ?
         WHERE axc.id_curso = ?
           $filtro_activo_alumnos
         ORDER BY p.apellido ASC, p.nombre ASC",
        'ii',
        [$materia_sel, $curso_sel]
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Asignacion por grupos</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-diagram-3-fill"></i> Asignar alumnos por grupo</h2>

    <?php if ($mensaje_texto !== ''): ?>
    <div class="<?php echo $mensaje_tipo === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
        <i class="bi <?php echo $mensaje_tipo === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
        <?php echo htmlspecialchars($mensaje_texto); ?>
    </div>
    <?php endif; ?>

    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-5">
            <label class="form-label">Curso</label>
            <select name="curso" class="form-select" required>
                <?php foreach ($cursos as $c): ?>
                <option value="<?php echo (int)$c['id_curso']; ?>" <?php echo (int)$curso_sel === (int)$c['id_curso'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string)$c['grado'] . '° ' . $c['seccion'] . ' (' . $c['moda'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">Materia de taller</label>
            <select name="materia" class="form-select" required>
                <?php foreach ($materias_grupo as $m): ?>
                <option value="<?php echo (int)$m['id_materia']; ?>" <?php echo (int)$materia_sel === (int)$m['id_materia'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string)$m['nombre_materia'] . ' (Grupos ' . $m['grupos'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn-plei-submit w-100" type="submit">Cargar</button>
        </div>
    </form>

    <?php if ($materias_grupo === []): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Este curso no tiene materias con mas de un grupo.
    </div>
    <?php else: ?>
    <form method="post">
        <?php campo_csrf(); ?>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="curso" value="<?php echo (int)$curso_sel; ?>">
        <input type="hidden" name="materia" value="<?php echo (int)$materia_sel; ?>">

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle tabla-organizada">
                <thead>
                <tr>
                    <th>DNI</th>
                    <th>Alumno</th>
                    <th>Grupo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($alumnos as $a): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$a['dni']); ?></td>
                    <td><?php echo htmlspecialchars((string)$a['apellido'] . ', ' . $a['nombre']); ?></td>
                    <td>
                        <select class="form-select" name="grupo[<?php echo (int)$a['id_persona']; ?>]">
                            <option value="">Sin asignar</option>
                            <?php foreach ($grupos_sel as $g): ?>
                            <option value="<?php echo (int)$g; ?>" <?php echo (int)($a['id_grupo'] ?? 0) === (int)$g ? 'selected' : ''; ?>>
                                Grupo <?php echo (int)$g; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn-plei-submit">Guardar asignaciones</button>
    </form>
    <?php endif; ?>

    <div class="text-end mt-3">
        <a href="<?php echo url('php/boletin/preceptor_boletines.php?curso=' . (int)$curso_sel); ?>" class="boton-volver">Volver a boletines</a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
