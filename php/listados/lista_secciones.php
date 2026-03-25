<?php
 include '../conesion.php'; include '../config.php'; session_start(); exigir_rol(['administrador']); $resultado = mysqli_query($con,"SELECT * FROM secciones"); $secciones = []; if($resultado){ while($seccion = mysqli_fetch_assoc($resultado)){ $secciones[]=$seccion; } } ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
  <div class="tarjeta-principal">
    <h2>Lista de Secciones</h2>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>Sección</th>
            <th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($secciones as $fila) { ?>
            <tr>
              <td><?= htmlspecialchars($fila['seccion']) ?></td>
              <td class="text-center">
                <div class="acciones-tabla justify-content-center">
                  <a href="../modificaciones/editar_seccion.php?id=<?= urlencode($fila['id_seccion']) ?>" class="btn btn-sm btn-table-edit">Modificar</a>
                  <form method="post" action="../modificaciones/eliminar_seccion.php" class="form-inline-delete" onsubmit="return confirm('¿Seguro que deseas eliminar esta seccion?');">
                    <?php campo_csrf(); ?>
                    <input type="hidden" name="id" value="<?= (int)$fila['id_seccion'] ?>">
                    <button type="submit" class="btn btn-sm btn-table-del">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="text-end mt-3">
      <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver al inicio</a>
    </div>
  </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
