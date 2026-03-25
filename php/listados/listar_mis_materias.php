<?php
include '../conesion.php'; include '../config.php'; session_start(); exigir_rol(['administrador','docente']); $id_docente = (int)($_SESSION['id_persona'] ?? 0); $materias_docente = db_fetch_all( $con, "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo, c.grado, mo.moda, s.seccion
     FROM materias AS m
     INNER JOIN cursos AS c ON c.id_curso = m.id_curso
     INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
     WHERE dm.id_persona = ?
     ORDER BY m.nombre_materia ASC", 'i', [$id_docente] ); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Mis materias</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
  <div class="tarjeta-principal">
    <h2>Mis materias asignadas</h2>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>ID Materia</th>
            <th>Nombre</th>
            <th>Turno</th>
            <th>Grupo</th>
            <th>Grado</th>
            <th>Modalidad</th>
            <th>Seccion</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materias_docente as $materia) { ?>
            <tr>
              <td><?= htmlspecialchars($materia['id_materia']) ?></td>
              <td><?= htmlspecialchars($materia['nombre_materia']) ?></td>
              <td><?= htmlspecialchars($materia['turno']) ?></td>
              <td><?= htmlspecialchars($materia['grupo']) ?></td>
              <td><?= htmlspecialchars($materia['grado']) ?></td>
              <td><?= htmlspecialchars($materia['moda']) ?></td>
              <td><?= htmlspecialchars($materia['seccion']) ?></td>
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
