<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_rol('administrador');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirigir('php/listados/lista_docentes.php');
}

$doc = db_fetch_one(
    $con,
    "SELECT p.id_persona, p.dni, p.nombre, p.apellido, p.mail
     FROM personas AS p
     INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
     INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
     WHERE p.id_persona = ? AND LOWER(tp.tipo) = 'docente'
     LIMIT 1",
    'i',
    [$id]
);

if (!$doc) {
    http_response_code(404);
    exit('Docente no encontrado.');
}

$error_form = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $dni = (int)($_POST['dni'] ?? 0);
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $apellido = trim((string)($_POST['apellido'] ?? ''));
    $mail = trim((string)($_POST['mail'] ?? ''));

    if ($dni <= 0 || $nombre === '' || $apellido === '') {
        $error_form = 'DNI, nombre y apellido son obligatorios.';
    } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $error_form = 'El email no tiene un formato válido.';
    } else {
        $stmt = mysqli_prepare($con, "UPDATE personas SET dni=?, nombre=?, apellido=?, mail=? WHERE id_persona=?");
        if (!$stmt) {
            $error_form = 'No se pudo preparar la actualización del docente.';
        } else {
            mysqli_stmt_bind_param($stmt, "isssi", $dni, $nombre, $apellido, $mail, $id);
            $ok = mysqli_stmt_execute($stmt);
            $errno = mysqli_errno($con);
            mysqli_stmt_close($stmt);

            if ($ok) {
                redirigir('php/listados/lista_docentes.php');
            } elseif ($errno === 1062) {
                $error_form = 'Ya existe otro usuario con ese DNI o email.';
            } else {
                $error_form = 'No se pudo guardar el docente.';
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
    <title>PLEI</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
  <div class="tarjeta-principal">
    <h2>Editar Docente</h2>

    <?php if ($error_form !== ''): ?>
    <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error_form) ?></div>
    <?php endif; ?>

    <form method="post">
      <?php campo_csrf(); ?>
      <div class="mb-3">
        <label class="form-label">DNI</label>
        <input type="text" name="dni" class="form-control" value="<?php echo htmlspecialchars($doc['dni']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($doc['nombre']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Apellido</label>
        <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($doc['apellido']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="text" name="mail" class="form-control" value="<?php echo htmlspecialchars($doc['mail']); ?>" required>
      </div>

      <button type="submit" class="btn-plei-submit">Guardar Cambios</button>
      <a href="../listados/lista_docentes.php" class="boton-volver ms-2">Cancelar</a>
    </form>
  </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
