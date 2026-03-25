<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirigir('php/listados/lista_preceptores.php');
}

$preceptor = db_fetch_one(
    $con,
    "SELECT p.id_persona, p.dni, p.nombre, p.apellido, p.mail
     FROM personas AS p
     INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
     INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
     WHERE p.id_persona = ? AND LOWER(tp.tipo) = 'preceptor'
     LIMIT 1",
    "i",
    [$id]
);

if (!$preceptor) {
    http_response_code(404);
    exit('Preceptor no encontrado.');
}

$cursos = db_fetch_all(
    $con,
    "SELECT c.id_curso, c.grado, s.seccion, m.moda
     FROM cursos AS c
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
     ORDER BY c.grado, s.seccion"
);

$cursos_asignados = array_map(
    static fn($fila) => (int)$fila['id_curso'],
    db_fetch_all($con, "SELECT id_curso FROM preceptor_x_curso WHERE id_persona = ?", "i", [$id])
);
$ids_cursos_validos = array_map(static fn($fila) => (int)$fila['id_curso'], $cursos);
$error_form = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $dni = (int)($_POST['dni'] ?? 0);
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $apellido = trim((string)($_POST['apellido'] ?? ''));
    $mail = trim((string)($_POST['mail'] ?? ''));
    $ids_cursos = array_values(array_unique(array_filter(
        array_map('intval', (array)($_POST['cursos'] ?? [])),
        static fn($id_curso) => $id_curso > 0
    )));

    $preceptor['dni'] = $dni;
    $preceptor['nombre'] = $nombre;
    $preceptor['apellido'] = $apellido;
    $preceptor['mail'] = $mail;

    if ($dni <= 0 || $nombre === '' || $apellido === '') {
        $error_form = "DNI, nombre y apellido son obligatorios.";
    } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $error_form = "El email no tiene un formato válido.";
    } elseif ($ids_cursos === []) {
        $error_form = "Seleccioná al menos un curso a cargo.";
    } else {
        foreach ($ids_cursos as $id_curso) {
            if (!in_array($id_curso, $ids_cursos_validos, true)) {
                $error_form = "Uno de los cursos seleccionados no es válido.";
                break;
            }
        }
    }

    if ($error_form === '') {
        mysqli_begin_transaction($con);
        try {
            $stmt = mysqli_prepare($con, "UPDATE personas SET dni = ?, nombre = ?, apellido = ?, mail = ? WHERE id_persona = ?");
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la actualización.');
            }
            mysqli_stmt_bind_param($stmt, "isssi", $dni, $nombre, $apellido, $mail, $id);
            if (!mysqli_stmt_execute($stmt)) {
                $errno = mysqli_errno($con);
                mysqli_stmt_close($stmt);
                if ($errno === 1062) {
                    throw new RuntimeException('Ya existe otro usuario con ese DNI o email.');
                }
                throw new RuntimeException('No se pudo actualizar el preceptor.');
            }
            mysqli_stmt_close($stmt);

            $del = mysqli_prepare($con, "DELETE FROM preceptor_x_curso WHERE id_persona = ?");
            if (!$del) {
                throw new RuntimeException('No se pudieron actualizar los cursos del preceptor.');
            }
            mysqli_stmt_bind_param($del, "i", $id);
            if (!mysqli_stmt_execute($del)) {
                mysqli_stmt_close($del);
                throw new RuntimeException('No se pudieron actualizar los cursos del preceptor.');
            }
            mysqli_stmt_close($del);

            $ins = mysqli_prepare($con, "INSERT INTO preceptor_x_curso (id_persona, id_curso) VALUES (?, ?)");
            if (!$ins) {
                throw new RuntimeException('No se pudo asignar cursos al preceptor.');
            }
            foreach ($ids_cursos as $id_curso) {
                mysqli_stmt_bind_param($ins, "ii", $id, $id_curso);
                if (!mysqli_stmt_execute($ins)) {
                    mysqli_stmt_close($ins);
                    throw new RuntimeException('No se pudo asignar uno de los cursos seleccionados.');
                }
            }
            mysqli_stmt_close($ins);

            mysqli_commit($con);
            redirigir('php/listados/lista_preceptores.php');
        } catch (Throwable $e) {
            mysqli_rollback($con);
            $error_form = $e->getMessage();
        }
    }

    $cursos_asignados = $ids_cursos;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Editar Preceptor</title>
    <script>document.documentElement.classList.add('js-enabled');</script>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="cuerpo-edicion">
<div class="tarjeta-principal ancho-600">
    <h2>Editar Preceptor</h2>
    <?php if ($error_form !== ''): ?>
    <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error_form) ?></div>
    <?php endif; ?>
    <form method="post">
        <?php campo_csrf(); ?>
        <div class="mb-3">
            <label class="form-label">DNI</label>
            <input type="text" name="dni" class="form-control" value="<?= htmlspecialchars((string)$preceptor['dni']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars((string)$preceptor['nombre']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars((string)$preceptor['apellido']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="mail" class="form-control" value="<?= htmlspecialchars((string)$preceptor['mail']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Cursos a cargo</label>
            <div class="js-only-multi" data-multi-select>
                <div class="multi-choice-block">
                    <div class="multi-choice-header">
                        <span class="texto-opcional">Podés seleccionar más de una opción.</span>
                        <span class="multi-choice-count" data-multi-count>0 seleccionados</span>
                    </div>
                    <div class="multi-choice-grid">
                        <?php foreach ($cursos as $curso): ?>
                        <?php $id_curso = (int)$curso['id_curso']; ?>
                        <label class="multi-choice-item">
                            <input
                                type="checkbox"
                                class="multi-choice-input"
                                name="cursos[]"
                                value="<?= $id_curso ?>"
                                <?= in_array($id_curso, $cursos_asignados, true) ? 'checked' : '' ?>
                            >
                            <span class="multi-choice-card">
                                <span>
                                    <span class="multi-choice-title"><?= htmlspecialchars($curso['grado'] . '° ' . $curso['seccion']) ?></span>
                                    <span class="multi-choice-extra"><?= htmlspecialchars($curso['moda']) ?></span>
                                </span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <noscript>
                <div class="multi-choice-fallback">
                    <select name="cursos[]" class="form-select alto-120" multiple required>
                        <?php foreach ($cursos as $curso): ?>
                        <option value="<?= (int)$curso['id_curso'] ?>" <?= in_array((int)$curso['id_curso'], $cursos_asignados, true) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($curso['grado'] . '° ' . $curso['seccion'] . ' ' . $curso['moda']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="texto-opcional">Podés seleccionar más de una opción.</small>
                </div>
            </noscript>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn-plei-submit boton-accion-corta">Guardar</button>
            <a href="<?php echo url('php/listados/lista_preceptores.php'); ?>" class="btn-plei-cancel">Cancelar</a>
        </div>
    </form>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-multi-select]').forEach((bloque) => {
    const checks = Array.from(bloque.querySelectorAll('.multi-choice-input'));
    const countNode = bloque.querySelector('[data-multi-count]');
    if (!checks.length || !countNode) return;
    const sync = () => {
        const total = checks.filter((check) => check.checked).length;
        countNode.textContent = total === 1 ? '1 seleccionado' : `${total} seleccionados`;
        checks[0].required = total === 0;
    };
    checks.forEach((check) => check.addEventListener('change', sync));
    sync();
});
</script>
</body>
</html>
