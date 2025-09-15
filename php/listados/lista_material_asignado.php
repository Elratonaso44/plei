<?php
include '../conesion.php';
session_start();
if(!isset($_SESSION['id_persona'])){
		echo "<script>alert('Sesion no iniciada');
        window.location.replace('https://localhost/Dinamica/practica/index.html');
        </script>";
		exit;
}
$id_alumno = $_SESSION['id_persona'];


$resultado= mysqli_query($con, "SELECT m.id_materia 
FROM materias AS m 
INNER JOIN alumnos_x_curso AS axc ON axc.id_curso = m.id_curso ");
//WHERE axc.id_persona = '$id_alumno' Capaz sea asi?

$materias = [];
if($resultado){
		while($materia = mysqli_fetch_assoc($resultado)){
				$materias[] = $materia['id_materia'];
		}
}

$materiales = [];

$materiales = [];
$query = "SELECT mat.id_material, mat.tipo_material, mat.unidad, mat.url, mat.id_materia, m.nombre_materia 
FROM materiales AS mat 
INNER JOIN materias AS m ON m.id_materia = mat.id_materia 
INNER JOIN alumnos_x_curso AS axc ON axc.id_curso = m.id_curso 
WHERE axc.id_persona = '$id_alumno'"; 
$resultado = mysqli_query($con, $query);
if($resultado){
	while($material = mysqli_fetch_assoc($resultado)){
		$materiales[] = $material;
	}
}

$materiales = [];
if($materias){
	
	$resultado = mysqli_query($con, "SELECT mat.id_material, mat.tipo_material, mat.unidad, mat.url, mat.id_materia, m.nombre_materia 
    FROM materiales AS mat 
    INNER JOIN materias AS m ON m.id_materia = mat.id_materia");
	if($resultado){
		while($material = mysqli_fetch_assoc($resultado)){
			$materiales[] = $material;
		}
	}
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Materiales Asignados</title>
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
		<h2>Materiales Asignados por el Profesor</h2>
		<div class="table-responsive">
			<table class="table table-bordered table-hover align-middle tabla-organizada">
				<thead>
					<tr>
						<th>ID Material</th>
						<th>Materia</th>
						<th>Tipo</th>
						<th>Unidad</th>
						<th>URL</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($materiales as $material){ ?>
					<tr>
						<td><?php echo htmlspecialchars($material['id_material']); ?></td>
						<td><?php echo htmlspecialchars($material['nombre_materia']); ?></td>
						<td><?php echo htmlspecialchars($material['tipo_material']); ?></td>
						<td><?php echo htmlspecialchars($material['unidad']); ?></td>
						<td><a href="<?php echo htmlspecialchars($material['url']); ?>" target="_blank">Ver material</a></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="text-end mt-3">
			<a href="http://localhost/Dinamica/practica/home.php" class="boton-volver">Volver</a>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
