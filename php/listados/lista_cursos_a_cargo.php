<?php
include '../conesion.php'; include '../config.php'; session_start(); exigir_rol(['administrador','preceptor']); $id_persona = (int)($_SESSION['id_persona'] ?? 0); $preceptores = db_fetch_all( $con, "SELECT p.dni, p.apellido, p.nombre, p.id_persona,
            GROUP_CONCAT(c.grado, '°', s.seccion, ' ', m.moda SEPARATOR ' | ') AS cursos_detalle
     FROM personas AS p
     INNER JOIN tipo_persona_x_persona AS ti ON ti.id_persona = p.id_persona
     INNER JOIN tipos_personas AS t ON t.id_tipo_persona = ti.id_tipo_persona
     INNER JOIN preceptor_x_curso AS pc ON pc.id_persona = p.id_persona
     INNER JOIN cursos AS c ON c.id_curso = pc.id_curso
     INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
     INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
     WHERE LOWER(t.tipo) = 'preceptor' AND p.id_persona = ?
     GROUP BY pc.id_persona", 'i', [$id_persona] ); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI - Cursos a cargo</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="fondo-transparente">
	<div class="tarjeta-principal">
		<h2>Cursos a cargo</h2>
		<div class="table-responsive">
			<table class="table table-bordered table-hover align-middle tabla-organizada">
				<thead>
					<tr>
						<th>Cursos a cargo</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($preceptores as $preceptor){ ?>
					<tr>
						<td><?= htmlspecialchars($preceptor['cursos_detalle']) ?></td>
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
