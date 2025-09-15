<?php 


include "../conesion.php";



$resultado = mysqli_query($con, "SELECT id_curso, grado, m.moda, s.seccion 
FROM cursos as c INNER JOIN modalidad as m on m.id_modalidad = c.id_modalidad 
INNER JOIN secciones as s on s.id_seccion = c.id_seccion");
$cursos = [];
if($resultado){
	while($curso = mysqli_fetch_assoc($resultado)){
		$cursos[] = $curso;
	}
}

  if($_SERVER['REQUEST_METHOD'] === 'GET'){

$curso_seleccionado = $_GET['curso'];

if($curso_seleccionado){
  $resultado = mysqli_query($con, 
"SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo, m.id_curso 
FROM materias as m 
INNER JOIN cursos as c on m.id_curso = c.id_curso
WHERE m.id_curso = '$curso_seleccionado'");

$materias = [];

if ($resultado){
    while($materia = mysqli_fetch_assoc($resultado)){
        $materias[] = $materia;
    }
}

$resultado = mysqli_query($con, "SELECT p.dni, p.apellido, p.nombre, t.tipo, t.id_tipo_persona, p.id_persona
FROM personas AS p
INNER JOIN tipo_persona_x_persona as ti on ti.id_persona=p.id_persona
INNER JOIN tipos_personas as t on t.id_tipo_persona=ti.id_tipo_persona
INNER JOIN alumnos_x_materia as axm on axm.id_persona = p.id_persona
INNER JOIN materias as m on m.id_materia = axm.id_materia
WHERE t.tipo = 'alumno' and m.id_materia = 1 and not axm.id_persona = 1");
$alumnos = [];

if ($resultado){
    while($alumno = mysqli_fetch_assoc($resultado)){
        $alumnos[] = $alumno;
    }
}

}

}




?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar materia a alumno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
  <div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
    <div class="card-body">
      <h2 class="text-center fw-bold mb-4">Asignar materia a alumno</h2>
      <form autocomplete="off" action="" method="GET">
          <div class="mb-3">
				  <select name="curso" class="form-select" required>
					<option value="">Seleccione un curso</option>
					<?php foreach ($cursos as $curso){ ?>
						<option value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
							<?php echo htmlspecialchars($curso['grado']." ".$curso['seccion']." ". $curso['moda']); ?>
						</option>
					<?php }?>    
				  </select>
			    </div>
              <input type="submit" value="Seleccionar curso" class="btn w-100 text-white"
          style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"
          onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"
          onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">
          
      </form>
      
  <?php if($curso_seleccionado){ ?>
      <form autocomplete="off" action="" method="POST">
      
        <div class="mb-3">
          <select name="materia" class="form-select" required>
            <option value="">Materia</option>
            <?php foreach ($materias as $materia){ ?>
              <option value="<?php echo htmlspecialchars($materia['id_materia']); ?>">
                <?php echo htmlspecialchars($materia['nombre_materia']." - ".$materia['grado']."°".$materia['seccion']." g".$materia['grupo']. " | " ."(".$materia['moda'].")"." | Turno ".$materia['turno']); ?>
              </option>
            <?php } ?>    
          </select>
        </div>
        <div class="mb-4">
          <select name="alumno" class="form-select" required>
            <option value="">Alumno</option>
            <?php foreach ($alumnos as $alumno){ ?>
              <option value="<?php echo htmlspecialchars($alumno['id_persona']); ?>">
                <?php echo htmlspecialchars($alumno['nombre']." ".$alumno['apellido']); ?>
              </option>
            <?php } ?>    
          </select>
        </div>
        <input type="submit" value="Asignar" class="btn w-100 text-white"
        style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"
        onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"
        onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">
      </form>
      <?php } ?>
    </div>
  </div>
  <script src="../bootstrap-5.0.2-dist/js/bootstrap.js"></script>
</body>
</html>
