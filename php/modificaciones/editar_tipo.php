<?php
include "../conesion.php"; include "../config.php"; session_start(); exigir_rol('administrador'); $id = intval($_GET['id'] ?? 0); if ($id <= 0) redirigir('php/listados/ver_tipos.php'); $tipo = db_fetch_one($con, "SELECT * FROM tipos_personas WHERE id_tipo_persona = ?", "i", [$id]); if (!$tipo) redirigir('php/listados/ver_tipos.php'); if ($_SERVER['REQUEST_METHOD'] === 'POST') { verificar_csrf(); $tipo_val = trim($_POST['tipo'] ?? ''); $stmt = mysqli_prepare($con, "UPDATE tipos_personas SET tipo=? WHERE id_tipo_persona=?"); if ($stmt) { mysqli_stmt_bind_param($stmt, "si", $tipo_val, $id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); } redirigir('php/listados/ver_tipos.php'); } ?>
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
    <h2>Editar tipo</h2>
    <form method="post">
      <?php campo_csrf(); ?>
      <div class="mb-3">
        <label class="form-label">Tipo</label>
        <input type="text" name="tipo" class="form-control" value="<?php echo htmlspecialchars($tipo['tipo']); ?>" required>
      </div>
   
      <button type="submit" class="btn-plei-submit">Guardar Cambios</button>
      <a href="../listados/ver_tipos.php" class="boton-volver ms-2">Cancelar</a>
    </form>
  </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
