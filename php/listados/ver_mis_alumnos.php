<?php
include '../conesion.php';
session_start();
if(!isset($_SESSION['id_persona'])){
		echo "<script>alert('Sesion no iniciada');window.location.replace('https://localhost/Dinamica/practica/index.html');</script>";
		exit;
}
$id_docente = $_SESSION['id_persona'];



$materias = [];
$resultado = mysqli_query($con, "SELECT * FROM materias 
WHERE id_materia 
IN (SELECT id_materia FROM docentes_x_materia WHERE id_persona = '$id_docente')");
if($resultado){
	while($materia = mysqli_fetch_assoc($resultado)){
		$materias[] = $materia;
	}
}

$alumnos = [];
$materia_seleccionada = $_POST['materia'];
if($materia_seleccionada){
		$resultado = mysqli_query($con, "SELECT p.dni, p.apellido, p.nombre, p.id_persona 
        FROM alumnos_x_curso as axc 
        INNER JOIN personas p ON axc.id_persona = p.id_persona 
        INNER JOIN tipo_persona_x_persona tpp ON tpp.id_persona = p.id_persona 
        INNER JOIN tipos_personas tp ON tp.id_tipo_persona = tpp.id_tipo_persona 
        WHERE tp.tipo = 'alumno'");
		if($resultado){
				while($alumno = mysqli_fetch_assoc($resultado)){
						$alumnos[] = $alumno;
				}
		}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Alumnos por Materia</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
		<style>
			body { background-color: #f8f9fa; }
			.tarjeta-principal { max-width: 1100px; margin: 40px auto; background-color: #b2dfd1; border-radius: 1rem; box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 30px; }
			.tarjeta-principal h2 { margin-bottom: 25px; font-weight: 600; color: #343a40; }
			.tabla-organizada thead { background: rgba(15, 15, 15, 0.7); color: #fff; }
			.tabla-organizada tbody tr:hover { background-color: rgba(0, 0, 0, 0.05); }
			.boton-volver { background-color: rgba(15, 15, 15, 0.7); color: #fff; border: none; padding: 10px 20px; border-radius: 0.5rem; transition: background 0.3s, transform 0.2s; }
			.boton-volver:hover { background-color: #00004F; transform: scale(1.05); color: #fff; }
		</style>
</head>
<body>
	<div class="tarjeta-principal">
		<h2>Alumnos por Materia</h2>
		<form method="POST" class="mb-4">
			<div class="row g-2 align-items-center">
				<div class="col-md-8">
					<select name="materia" class="form-select" required>
						<option value="">Seleccione una materia</option>
						<?php foreach($materias as $mat){ ?>
							<option value="<?= htmlspecialchars($mat['id_materia']) ?>" <?= ($materia_seleccionada == $mat['id_materia']) ? 'selected' : '' ?>>
								<?= htmlspecialchars($mat['nombre_materia']." - ".$mat['grado']."°".$mat['seccion']." G".$mat['grupo']." (".$mat['moda'].") Turno ".$mat['turno']) ?>
							</option>
						<?php } ?>
					</select>
				</div>
				<div class="col-md-4">
					<button type="submit" class="btn btn-dark w-100">Ver alumnos</button>
				</div>
			</div>
		</form>
		<?php if($materia_seleccionada){ ?>
		<div class="table-responsive">
			<table class="table table-bordered table-hover align-middle tabla-organizada">
				<thead>
					<tr>
						<th>DNI</th>
						<th>Nombre</th>
						<th>Apellido</th>
					</tr>
				</thead>
				<tbody>
					<?php if(count($alumnos) > 0){ foreach ($alumnos as $alumno){ ?>
					<tr>
						<td><?= htmlspecialchars($alumno['dni']) ?></td>
						<td><?= htmlspecialchars($alumno['nombre']) ?></td>
						<td><?= htmlspecialchars($alumno['apellido']) ?></td>
					</tr>
					<?php }}else{ ?>
					<tr><td colspan="3" class="text-center">No hay alumnos en esta materia.</td></tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?php } ?>
		<div class="text-end mt-3">
			<a href="http://localhost/Dinamica/practica/home.php" class="boton-volver">Volver</a>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
