<?php 

//Docentes_x_materia, le puse AD.php para que el nombre sea mas corto.

include "../conesion.php";

$resultado = mysqli_query($con, "SELECT id_persona, nombre, apellido, t.tipo 
    FROM personas AS p 
    INNER JOIN tipo_persona_x_persona AS t ON t.id_tipo_persona = p.id_persona 
    WHERE t.tipo = 'Docente'");
$docentes = [];

if ($resultado){
    while($docente = mysqli_fetch_assoc($resultado)){
        $docentes[] = $docente;
    }
}


$resultado = mysqli_query($con, 
"SELECT m.id_materia, m.nombre_materia, c.grado, s.seccion, mo.moda, m.grupo, m.turno
 FROM materias AS m
 INNER JOIN cursos AS c ON m.id_curso = c.id_curso
 INNER JOIN secciones AS s ON c.id_seccion = s.id_seccion
 INNER JOIN modalidad AS mo ON c.id_modalidad = mo.id_modalidad
 ");

$materias = [];

if ($resultado){
    while($materia = mysqli_fetch_assoc($resultado)){
        $materias[] = $materia;
    }
}



if($_SERVER["REQUEST_METHOD"] === "POST"){
    $materia = $_POST["materia"];
    $docente = $_POST["docente"];
    

    mysqli_query($con,"INSERT INTO docentes_x_materia (id_materia, id_persona) VALUES ('$materia', '$docente')");
    mysqli_close($con);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar materia a docente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
  <div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
    <div class="card-body">
      <h2 class="text-center fw-bold mb-4">Asignar materia a docente</h2>
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
          <select name="docente" class="form-select" required>
            <option value="">Docente</option>
            <?php foreach ($docentes as $docente){ ?>
              <option value="<?php echo htmlspecialchars($docente['id_persona']); ?>">
                <?php echo htmlspecialchars($docente['nombre']." ".$docente['apellido']); ?>
              </option>
            <?php } ?>    
          </select>
        </div>
        <input type="submit" value="Asignar" class="btn w-100 text-white"
          style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"
          onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"
          onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">
      </form>
    </div>
  </div>
  <script src="../bootstrap-5.0.2-dist/js/bootstrap.js"></script>
</body>
</html>
