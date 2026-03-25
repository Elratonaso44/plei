<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_rol(['administrador','docente']);

$id_docente = (int)($_SESSION['id_persona'] ?? 0);

$materias = db_fetch_all(
    $con,
    "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo, c.grado, s.seccion, mo.moda, m.id_curso
     FROM materias AS m
     INNER JOIN cursos AS c ON c.id_curso = m.id_curso
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
     INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
     WHERE dm.id_persona = ?
     ORDER BY m.nombre_materia ASC",
    'i',
    [$id_docente]
);

$ids_materias_docente = array_map(static fn($m) => (int)$m['id_materia'], $materias);
$materia_sel = (int)($_POST['materia'] ?? 0);
$q = trim((string)($_POST['q'] ?? ''));
$alumnos = [];
$materia_info = null;
$error_permiso = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['materia'])) {
    http_response_code(405);
    $error_permiso = 'Método no permitido para consultar alumnos. Usá el formulario de la pantalla.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    if ($materia_sel > 0 && !in_array($materia_sel, $ids_materias_docente, true)) {
        http_response_code(403);
        $error_permiso = 'No tenés permisos para consultar alumnos de esa materia.';
    }
}

if ($materia_sel > 0 && $error_permiso === '') {
    foreach ($materias as $materia) {
        if ((int)$materia['id_materia'] === $materia_sel) {
            $materia_info = $materia;
            break;
        }
    }

    if ($materia_info) {
        $sql_alumnos = "SELECT DISTINCT p.id_persona, p.nombre, p.apellido, p.dni, p.mail
                        FROM personas AS p
                        INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
                        INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
                        LEFT JOIN alumnos_x_materia AS axm
                               ON axm.id_persona = p.id_persona
                              AND axm.id_materia = ?
                        LEFT JOIN alumnos_x_curso AS axc
                               ON axc.id_persona = p.id_persona
                              AND axc.id_curso = ?
                        WHERE LOWER(tp.tipo) = 'alumno'
                          AND (axm.id_persona IS NOT NULL OR axc.id_persona IS NOT NULL)";
        $tipos_alumnos = 'ii';
        $parametros_alumnos = [$materia_sel, (int)$materia_info['id_curso']];

        if ($q !== '') {
            $sql_alumnos .= " AND (
                CAST(p.dni AS CHAR) LIKE ? ESCAPE '\\\\'
                OR p.nombre LIKE ? ESCAPE '\\\\'
                OR p.apellido LIKE ? ESCAPE '\\\\'
            )";
            $like = valor_like($q);
            $tipos_alumnos .= 'sss';
            $parametros_alumnos[] = $like;
            $parametros_alumnos[] = $like;
            $parametros_alumnos[] = $like;
        }

        $sql_alumnos .= " ORDER BY p.apellido ASC, p.nombre ASC";
        $alumnos = db_fetch_all($con, $sql_alumnos, $tipos_alumnos, $parametros_alumnos);
    } else {
        http_response_code(403);
        $error_permiso = 'No tenés permisos para consultar alumnos de esa materia.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Mis alumnos</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
<div class="tarjeta-principal">
    <h2><i class="bi bi-person-lines-fill"></i> Alumnos por materia</h2>

    <?php if ($error_permiso !== ''): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?php echo htmlspecialchars($error_permiso); ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <?php campo_csrf(); ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label">Selecciona una materia</label>
                <select name="materia" class="form-select" required>
                    <option value="">Elige una materia</option>
                    <?php foreach ($materias as $mat): ?>
                    <option value="<?= (int)$mat['id_materia'] ?>" <?= ((int)$materia_sel === (int)$mat['id_materia']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mat['nombre_materia'].' - '.$mat['grado'].'° '.$mat['seccion'].' ('.$mat['moda'].') Turno '.$mat['turno']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-9">
                <label class="form-label">Filtrar alumnos por DNI, nombre o apellido</label>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Ej: 44123456, Juan, Pérez">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn-plei-submit w-100" style="padding:.65rem">
                    <i class="bi bi-search"></i> Ver alumnos
                </button>
            </div>
        </div>
    </form>

    <?php if ($materia_sel > 0 && $error_permiso === ''): ?>
    <?php if ($materia_info): ?>
    <div style="margin-bottom:1rem;padding:.75rem 1rem;background:rgba(99,102,241,0.06);border-radius:var(--radius-md);border:1.5px solid rgba(255,255,255,0.15);font-size:.9rem;color:#1a1b2e">
        <i class="bi bi-info-circle-fill me-2" style="color:var(--accent-light)"></i>
        Mostrando alumnos de la materia <strong><?= htmlspecialchars($materia_info['nombre_materia']) ?></strong>
        <br>
        <small style="color:var(--text-muted)">Criterio: alumnos asignados a la materia o alumnos pertenecientes al curso de la materia.</small>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle tabla-organizada">
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Apellido</th>
                    <th>Nombre</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($alumnos) > 0): ?>
                <?php foreach ($alumnos as $alumno): ?>
                <tr>
                    <td><?= htmlspecialchars($alumno['dni']) ?></td>
                    <td><?= htmlspecialchars($alumno['apellido']) ?></td>
                    <td><?= htmlspecialchars($alumno['nombre']) ?></td>
                    <td><?= htmlspecialchars($alumno['mail']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center py-4" style="color:var(--text-muted)">
                        <i class="bi bi-person-x" style="font-size:1.5rem;display:block;margin-bottom:.4rem;opacity:.4"></i>
                        No hay alumnos asignados a esta materia todavía.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="text-end mt-3">
        <a href="<?php echo url('home.php'); ?>" class="boton-volver">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
