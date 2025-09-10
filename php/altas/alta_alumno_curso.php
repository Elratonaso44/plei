<?php 

include "../conesion.php";

$resultado = mysqli_query($con, "SELECT COUNT(axc.id_persona_x_curso) as a_detalle, p.id_persona,nombre,apellido,t.tipo 
FROM personas AS p 
INNER JOIN tipo_persona_x_persona as ti on ti.id_persona=p.id_persona
INNER JOIN tipos_personas as t on t.id_tipo_persona=ti.id_tipo_persona
LEFT JOIN alumnos_x_curso as axc on axc.id_persona = p.id_persona
WHERE t.tipo = 'Alumno' 
GROUP BY p.id_persona
HAVING a_detalle = 0");
$personas = [];

if($resultado){
    while($persona = mysqli_fetch_assoc($resultado)){
        $personas[] = $persona;
    }
}

$resultado = mysqli_query($con, "SELECT id_curso,grado , m.moda,s.seccion from cursos as c inner join modalidad as m on m.id_modalidad = c.id_modalidad inner join secciones as s on s.id_seccion = c.id_seccion");
$cursos = [];

if ($resultado){
    while($curso = mysqli_fetch_assoc($resultado)){
        $cursos[] = $curso; 
    }
}

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $curso = $_POST["curso"];
    $personas = $_POST["persona"];
    echo "<script>
        console.log($curso);
    </script>
    ";

    mysqli_query($con,"INSERT INTO alumnos_x_curso (id_curso,id_persona) VALUES ('$curso', '$personas')");
    mysqli_close($con);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta Alumno a Curso</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
  <div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
    <div class="card-body">
      <h2 class="text-center fw-bold mb-4">Alta de Alumno a Curso</h2>
      <form autocomplete="off" action="" method="POST">
        <div class="mb-3">
          <select name="curso" class="form-select" required>
            <option value="">Seleccione un curso</option>
            <?php foreach ($cursos as $curso){ ?>
              <option value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
                <?php echo htmlspecialchars($curso['grado']." ".$curso['seccion']. " ". $curso['moda']); ?>
              </option><?php }?>    
          </select>
        </div>
        <div class="mb-4">
          <select name="persona" class="form-select" required>
            <option value="">Seleccione un alumno</option>
            <?php foreach ($personas as $persona){ ?>
              <option value="<?php echo htmlspecialchars($persona['id_persona']); ?>">
                <?php echo htmlspecialchars($persona['nombre']." ".$persona['apellido']); ?>
              </option><?php }?>    
          </select>
        </div>
        <input type="submit" value="Dar de alta" class="btn w-100 text-white"
          style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"
          onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"
          onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">
      </form>
    </div>
  </div>
  <script src="../bootstrap-5.0.2-dist/js/bootstrap.js"></script>
</body>
</html>