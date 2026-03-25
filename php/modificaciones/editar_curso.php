<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol(['administrador', 'preceptor']);

$id_operador = (int)($_SESSION['id_persona'] ?? 0);
$tipos_usuario = obtener_tipos_usuario($con, $id_operador);
$es_admin = in_array('administrador', $tipos_usuario, true);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirigir('php/listados/lista_cursos.php');
}

$curso = db_fetch_one(
    $con,
    "SELECT c.id_curso, c.grado, c.id_modalidad, c.id_seccion, m.moda, s.seccion
     FROM cursos AS c
     INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     WHERE c.id_curso = ?",
    'i',
    [$id]
);

if (!$curso) {
    redirigir('php/listados/lista_cursos.php');
}

if (!$es_admin) {
    $cursos_habilitados = cursos_a_cargo_preceptor($con, $id_operador);
    if (!in_array($id, $cursos_habilitados, true)) {
        http_response_code(403);
        exit('Acceso denegado. Solo podés editar cursos que tengas a cargo.');
    }
}

$modalidades = db_fetch_all($con, "SELECT * FROM modalidad ORDER BY moda");
$secciones = db_fetch_all($con, "SELECT * FROM secciones ORDER BY seccion");
$error_form = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $grado = (int)($_POST['grado'] ?? 0);
    $id_modalidad = (int)($_POST['id_modalidad'] ?? 0);
    $id_seccion = (int)($_POST['id_seccion'] ?? 0);

    if ($grado <= 0 || $id_modalidad <= 0 || $id_seccion <= 0) {
        $error_form = 'Completá grado, modalidad y sección con valores válidos.';
    } else {
        $stmt = mysqli_prepare($con, "UPDATE cursos SET grado=?, id_modalidad=?, id_seccion=? WHERE id_curso=?");
        if (!$stmt) {
            $error_form = 'No se pudo preparar la actualización del curso.';
        } else {
            mysqli_stmt_bind_param($stmt, "iiii", $grado, $id_modalidad, $id_seccion, $id);
            $ok = mysqli_stmt_execute($stmt);
            $errno = mysqli_errno($con);
            mysqli_stmt_close($stmt);

            if ($ok) {
                redirigir('php/listados/lista_cursos.php');
            } elseif ($errno === 1452) {
                $error_form = 'La modalidad o sección seleccionada no existe.';
            } else {
                $error_form = 'No se pudo guardar el curso.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Editar Curso</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="cuerpo-edicion">
<div class="tarjeta-principal ancho-500">
    <h2>Editar Curso</h2>

    <?php if ($error_form !== ''): ?>
    <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error_form) ?></div>
    <?php endif; ?>

    <form method="post">
        <?php campo_csrf(); ?>
        <div class="mb-3"><label class="form-label">Grado</label>
            <input type="number" name="grado" class="form-control" value="<?= (int)$curso['grado'] ?>" required min="1"></div>
        <div class="mb-3">
            <label class="form-label">Modalidad</label>
            <select name="id_modalidad" class="form-select" required>
                <?php foreach ($modalidades as $m): ?>
                <option value="<?= (int)$m['id_modalidad'] ?>" <?= ((int)$m['id_modalidad']===(int)$curso['id_modalidad']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['moda']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Sección</label>
            <select name="id_seccion" class="form-select" required>
                <?php foreach ($secciones as $s): ?>
                <option value="<?= (int)$s['id_seccion'] ?>" <?= ((int)$s['id_seccion']===(int)$curso['id_seccion']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['seccion']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn-plei-submit boton-accion-corta">Guardar</button>
            <a href="<?php echo url('php/listados/lista_cursos.php'); ?>" class="btn-plei-cancel">Cancelar</a>
        </div>
    </form>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
