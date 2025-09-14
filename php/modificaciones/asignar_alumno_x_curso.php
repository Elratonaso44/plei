<?php
include "../conesion.php";

$resultado = mysqli_query($con, "SELECT p.id_persona, p.nombre, p.apellido FROM personas as p
INNER JOIN tipo_persona_x_persona as ti on ti.id_persona=p.id_persona 
INNER JOIN tipos_personas as t on t.id_tipo_persona=ti.id_tipo_persona WHERE t.tipo = 'Alumno'");
$alumnos = [];
if($resultado){
	while($alumno = mysqli_fetch_assoc($resultado)){
		$alumnos[] = $alumno;
	}
}

$resultado = mysqli_query($con, "SELECT id_curso, grado, m.moda, s.seccion FROM cursos as c INNER JOIN modalidad as m on m.id_modalidad = c.id_modalidad INNER JOIN secciones as s on s.id_seccion = c.id_seccion");
$cursos = [];
if($resultado){
	while($curso = mysqli_fetch_assoc($resultado)){
		$cursos[] = $curso;
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id_alumno = $_POST['alumno'];
	$id_curso = $_POST['curso'];
	mysqli_query($con, "DELETE FROM alumnos_x_curso WHERE id_persona = '$id_alumno'");

	mysqli_query($con, "INSERT INTO alumnos_x_curso (id_curso, id_persona) VALUES ('$id_curso', '$id_alumno')");

	header("Location:http://localhost/Dinamica/practica/home.php");
	exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Asignar Alumno a Curso</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
		<style>
			body { background-color: #f8f9fa; }
			.tarjeta-principal { max-width: 500px; margin: 40px auto; background-color: #b2dfd1; border-radius: 1rem; box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 30px; }
			.tarjeta-principal h2 { margin-bottom: 25px; font-weight: 600; color: #343a40; }
			.boton-volver { background-color: rgba(15, 15, 15, 0.7); color: #fff; border: none; padding: 10px 20px; border-radius: 0.5rem; transition: background 0.3s, transform 0.2s; }
			.boton-volver:hover { background-color: #00004F; transform: scale(1.05); color: #fff; }
		</style>
</head>
<body>
	<div class="tarjeta-principal">
		<h2>Asignar Alumno a Curso</h2>
		<form method="post">
			<div class="mb-3">
				<label class="form-label">Alumno</label>
				<select name="alumno" class="form-select" required>
					<option value="">Seleccione un alumno</option>
					<?php foreach ($alumnos as $alumno){ ?>
						<option value="<?php echo htmlspecialchars($alumno['id_persona']); ?>">
							<?php echo htmlspecialchars($alumno['nombre']." ".$alumno['apellido']); ?>
						</option>
					<?php }?>    
				</select>
			</div>
			<div class="mb-3">
				<label class="form-label">Curso</label>
				<select name="curso" class="form-select" required>
					<option value="">Seleccione un curso</option>
					<?php foreach ($cursos as $curso){ ?>
						<option value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
							<?php echo htmlspecialchars($curso['grado']." ".$curso['seccion']." ". $curso['moda']); ?>
						</option>
					<?php }?>    
				</select>
			</div>
			<button type="submit" class="btn btn-primary">Asignar</button>
			<a href="http://localhost/Dinamica/practica/home.php" class="boton-volver ms-2">Cancelar</a>
		</form>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
