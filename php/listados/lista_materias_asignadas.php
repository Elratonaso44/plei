<?php
include '../conesion.php'; include '../config.php'; session_start(); exigir_rol('alumno'); $id_alumno = (int)($_SESSION['id_persona'] ?? 0); $materias_asignadas = db_fetch_all( $con, "SELECT DISTINCT m.id_materia, m.nombre_materia, m.turno, m.grupo, c.grado, mo.moda, s.seccion
     FROM materias AS m
     INNER JOIN cursos AS c ON c.id_curso = m.id_curso
     INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     LEFT JOIN alumnos_x_materia AS axm ON axm.id_materia = m.id_materia AND axm.id_persona = ?
     LEFT JOIN alumnos_x_curso AS axc ON axc.id_curso = m.id_curso AND axc.id_persona = ?
     WHERE axm.id_persona IS NOT NULL OR axc.id_persona IS NOT NULL
     ORDER BY m.nombre_materia ASC", 'ii', [$id_alumno, $id_alumno] ); ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Materias asignadas</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
	<div class="tarjeta-principal">
		<h2>Materias asignadas</h2>
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
					<?php foreach ($materias_asignadas as $materia){ ?>
					<tr>
						<td><?php echo htmlspecialchars($materia['id_materia']); ?></td>
						<td><?php echo htmlspecialchars($materia['nombre_materia']); ?></td>
						<td><?php echo htmlspecialchars($materia['turno']); ?></td>
						<td><?php echo htmlspecialchars($materia['grupo']); ?></td>
						<td><?php echo htmlspecialchars($materia['grado']); ?></td>
						<td><?php echo htmlspecialchars($materia['moda']); ?></td>
						<td><?php echo htmlspecialchars($materia['seccion']); ?></td>
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
