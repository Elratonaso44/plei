<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_rol('administrador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PLEI - Alta rol</title>
  <link rel="stylesheet" href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
  <div class="form-card">
    <h2>Alta de rol</h2>
    <form autocomplete="off" action="../logicas/roles.php" method="post">
      <?php campo_csrf(); ?>
      <div class="mb-3">
        <label class="form-label">Rol</label>
        <input type="text" name="rol" class="form-control" placeholder="Ingresá un rol" required maxlength="30">
      </div>
      <button type="submit" class="btn-plei-submit">Dar de alta</button>
    </form>
    <a href="../../home.php" class="btn-plei-cancel mt-2 w-100">Cancelar</a>
  </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
