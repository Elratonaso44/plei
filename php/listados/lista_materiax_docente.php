<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_rol(['administrador', 'docente']);

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
$es_admin = usuario_tiene_tipo($con, $id_persona, 'administrador');

if ($es_admin) {
    $materias_docente = db_fetch_all(
        $con,
        "SELECT DISTINCT m.nombre_materia, m.turno, m.grupo, m.id_materia, c.grado, mo.moda, s.seccion
         FROM materias AS m
         INNER JOIN cursos AS c ON c.id_curso = m.id_curso
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         ORDER BY c.grado, s.seccion, m.nombre_materia"
    );
} else {
    $materias_docente = db_fetch_all(
        $con,
        "SELECT DISTINCT m.nombre_materia, m.turno, m.grupo, m.id_materia, c.grado, mo.moda, s.seccion
         FROM materias AS m
         INNER JOIN cursos AS c ON c.id_curso = m.id_curso
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
         WHERE dm.id_persona = ?
         ORDER BY c.grado, s.seccion, m.nombre_materia",
        'i',
        [$id_persona]
    );
}

$estado = trim((string)($_GET['estado'] ?? ''));
$msg = trim((string)($_GET['msg'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Materias por docente</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
  <div class="tarjeta-principal">
    <h2><?php echo $es_admin ? 'Materias del sistema' : 'Mis materias asignadas'; ?></h2>

    <?php if ($msg !== ''): ?>
    <div class="<?php echo $estado === 'ok' ? 'alert-ok' : 'alert-err'; ?>">
      <i class="bi <?php echo $estado === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
      <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>Materia</th>
            <th>Turno</th>
            <th>Grupo</th>
            <th>Grado</th>
            <th>Modalidad</th>
            <th>Sección</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($materias_docente)): ?>
          <tr>
            <td colspan="7" class="text-center py-4">No hay materias disponibles para mostrar.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($materias_docente as $materia): ?>
          <tr>
            <td><?php echo htmlspecialchars($materia['nombre_materia']); ?></td>
            <td><?php echo htmlspecialchars($materia['turno']); ?></td>
            <td><?php echo htmlspecialchars($materia['grupo']); ?></td>
            <td><?php echo htmlspecialchars($materia['grado']); ?></td>
            <td><?php echo htmlspecialchars($materia['moda']); ?></td>
            <td><?php echo htmlspecialchars($materia['seccion']); ?></td>
            <td>
              <a href="../listados/lista_mis_materiales.php?id=<?php echo urlencode((string)$materia['id_materia']); ?>" class="btn btn-sm btn-table-edit">Ver materiales</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
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
