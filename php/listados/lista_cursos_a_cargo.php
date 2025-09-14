<?php
include '../conesion.php';
session_start();
if(!isset($_SESSION['id_persona'])){
	echo "<script>
	alert('Sesion no iniciada');
	window.location.replace('https://localhost/Dinamica/practica/index.html');
	</script>";
	exit;
}
$id = $_SESSION['id_persona'];

$resultado = mysqli_query($con, "SELECT p.dni, p.apellido, p.nombre, p.id_persona, t.tipo, ti.id_tipo_persona,  
GROUP_CONCAT(c.grado,'°',  s.seccion, ' ' ,m.moda SEPARATOR ' | ')  as c_detalle
FROM personas AS p
INNER JOIN tipo_persona_x_persona as ti on ti.id_persona=p.id_persona
INNER JOIN tipos_personas as t on t.id_tipo_persona=ti.id_tipo_persona
INNER JOIN preceptor_x_curso as pc on pc.id_persona = p.id_persona
INNER JOIN cursos as c on c.id_curso =  pc.id_curso
INNER JOIN modalidad AS m ON m.id_modalidad = c.id_modalidad
INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
WHERE t.tipo = 'preceptor' AND p.id_persona = '$id'
GROUP BY pc.id_persona");

$preceptores = [];
if($resultado){
		while($preceptor = mysqli_fetch_assoc($resultado)){
				$preceptores[]=$preceptor;
		}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Cursos a cargo del Preceptor</title>
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
								<td><?= $preceptor["c_detalle"] ?></td>
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
