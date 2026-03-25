<?php
include "../conesion.php"; include "../config.php"; session_start(); exigir_rol('administrador'); $id = intval($_GET['id'] ?? 0); if ($id <= 0) redirigir('php/listados/listar_modalidad.php'); $modalidad = db_fetch_one($con, "SELECT * FROM modalidad WHERE id_modalidad = ?", "i", [$id]); if (!$modalidad) redirigir('php/listados/listar_modalidad.php'); if ($_SERVER['REQUEST_METHOD'] === 'POST') { verificar_csrf(); $moda = trim($_POST['modalidad'] ?? ''); $stmt = mysqli_prepare($con, "UPDATE modalidad SET moda=? WHERE id_modalidad=?"); if ($stmt) { mysqli_stmt_bind_param($stmt, "si", $moda, $id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); } redirigir('php/listados/listar_modalidad.php'); } ?>
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
    <h2>Editar modalidad</h2>
    <form method="post">
      <?php campo_csrf(); ?>
      <div class="mb-3">
        <label class="form-label">Modalidad</label>
        <input type="text" name="modalidad" class="form-control" value="<?php echo htmlspecialchars($modalidad['moda']); ?>" required>
      </div>
   
      <button type="submit" class="btn-plei-submit">Guardar Cambios</button>
      <a href="../listados/listar_modalidad.php" class="boton-volver ms-2">Cancelar</a>
    </form>
  </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
