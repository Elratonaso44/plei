<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_rol('administrador');

$modalidades = db_fetch_all($con, "SELECT id_modalidad, moda FROM modalidad ORDER BY moda");
$secciones = db_fetch_all($con, "SELECT id_seccion, seccion FROM secciones ORDER BY seccion");
$msg_curso = '';
$error_form = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificar_csrf();

    $grado = (int)($_POST["grado"] ?? 0);
    $id_modalidad = (int)($_POST["modalidad"] ?? 0);
    $id_seccion = (int)($_POST["seccion"] ?? 0);

    if ($grado <= 0 || $id_modalidad <= 0 || $id_seccion <= 0) {
        $error_form = 'Completá grado, modalidad y sección con valores válidos.';
    } else {
        $sentencia = mysqli_prepare($con, "INSERT INTO cursos (grado, id_modalidad, id_seccion) VALUES (?, ?, ?)");
        if (!$sentencia) {
            $error_form = 'No se pudo preparar el alta del curso.';
        } else {
            mysqli_stmt_bind_param($sentencia, "iii", $grado, $id_modalidad, $id_seccion);
            $ok = mysqli_stmt_execute($sentencia);
            $errno = mysqli_errno($con);
            mysqli_stmt_close($sentencia);

            if ($ok) {
                $msg_curso = 'Curso creado correctamente.';
            } elseif ($errno === 1452) {
                $error_form = 'La modalidad o sección seleccionada no existe.';
            } else {
                $error_form = 'No se pudo crear el curso.';
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
  <div class="form-card">
      <h2 class="text-center fw-bold mb-4">Alta de Curso</h2>

      <?php if ($msg_curso !== ''): ?>
      <div class="alert-ok">
        <i class="bi bi-check-circle-fill"></i><?php echo htmlspecialchars($msg_curso); ?>
      </div>
      <?php endif; ?>

      <?php if ($error_form !== ''): ?>
      <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill"></i><?php echo htmlspecialchars($error_form); ?>
      </div>
      <?php endif; ?>

      <form autocomplete="off" method="POST">
        <?php campo_csrf(); ?>
        <div class="mb-3">
          <label class="form-label">Grado</label>
          <input type="number" name="grado" class="form-control" placeholder="Ingrese el grado" required min="1">
        </div>
        <div class="mb-3">
          <label class="form-label">Modalidad</label>
          <select name="modalidad" class="form-select" required>
            <option value="">Seleccione una modalidad</option>
            <?php foreach ($modalidades as $modalidad): ?>
            <option value="<?php echo (int)$modalidad['id_modalidad']; ?>">
              <?php echo htmlspecialchars($modalidad['moda']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Sección</label>
          <select name="seccion" class="form-select" required>
            <option value="">Seleccione una sección</option>
            <?php foreach ($secciones as $seccion): ?>
            <option value="<?php echo (int)$seccion['id_seccion']; ?>">
              <?php echo htmlspecialchars($seccion['seccion']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn-plei-submit">Dar de alta</button>
      </form>
      <div class="text-center mt-3">
        <a href="<?php echo url('home.php'); ?>" class="btn-plei-cancel">Volver</a>
      </div>
    </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
