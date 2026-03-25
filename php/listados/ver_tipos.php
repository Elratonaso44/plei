<?php
 include "../conesion.php"; include "../config.php"; session_start(); exigir_rol('administrador'); $resultado = mysqli_query($con, "SELECT * FROM tipos_personas"); $tipos_personas = []; if($resultado){ while($tipo = mysqli_fetch_assoc($resultado)){ $tipos_personas[] = $tipo; } } ?>

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
    <h2>Tipos de Persona</h2>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tipos_personas as $tipo){ ?>
          <tr>
            <td><?php echo htmlspecialchars($tipo['id_tipo_persona']); ?></td>
            <td><?php echo htmlspecialchars($tipo['tipo']); ?></td>
            <td>
              <div class="acciones-tabla">
                <a href="../modificaciones/editar_tipo.php?id=<?php echo urlencode($tipo['id_tipo_persona']); ?>" class="btn btn-sm btn-table-edit">Modificar</a>
                <form method="post" action="../modificaciones/eliminar_tipo.php" class="form-inline-delete" onsubmit="return confirm('¿Seguro que deseas eliminar este tipo de persona?');">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="id" value="<?php echo (int)$tipo['id_tipo_persona']; ?>">
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
      <a href="<?php echo url('home.php'); ?>" class="boton-volver">Volver</a>
    </div>
  </div>
  <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
