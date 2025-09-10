<?php

include "../conesion.php";


$resultado = mysqli_query($con, "SELECT p.dni, p.apellido, p.nombre, p.id_persona, t.tipo, ti.id_tipo_persona
FROM personas AS p
INNER JOIN tipo_persona_x_persona as ti on ti.id_persona=p.id_persona
INNER JOIN tipos_personas as t on t.id_tipo_persona=ti.id_tipo_persona
WHERE t.tipo = 'preceptor'
" );

$preceptores = [];

if($resultado){
    while($preceptor = mysqli_fetch_assoc($resultado)){
        $preceptores[]=$preceptor;
    }
}

$resultado = mysqli_query($con, "SELECT c.id_curso, c.grado, c.id_seccion, c.id_modalidad, s.seccion, m.moda
from cursos as c
INNER JOIN secciones as s on s.id_seccion = c.id_seccion
INNER JOIN modalidad as m on m.id_modalidad = c.id_modalidad");

$cursos = [];

if($resultado){
    while($curso = mysqli_fetch_assoc($resultado)){
        $cursos[] = $curso;
    }
}

if($_SERVER["REQUEST_METHOD"] === "POST"){
  $preceptor = $_POST["preceptor"];
  $cursos = $_POST["curso"];

  if ($cursos) {
    foreach ($cursos as $curso) {
      mysqli_query($con, "INSERT INTO preceptor_x_curso 
      (id_persona, id_curso) VALUES ($preceptor, $curso)");
    }
  }
  header("Location:http://localhost/Dinamica/practica/php/listados/lista_preceptores.php");
}



?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta preceptores por curso</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
  <div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
    <div class="card-body">
      <h2 class="text-center fw-bold mb-4">Alta de preceptores por curso</h2>
      <form autocomplete="off" action="" method="post">
        <div class="mb-3">
          <select name="preceptor"class="form-control" required >
            <option value="">Preceptor</option>
            <?php foreach($preceptores as $preceptor){ ?>
                <option value="<?php echo htmlspecialchars($preceptor['id_persona'])?>">
                <?php echo htmlspecialchars($preceptor['nombre']." ".$preceptor['apellido']) ?>
                </option>
                <?php } ?>
          </select>
        </div>
        <div class="mb-4">
          <select name="curso[]" class="form-select" multiple required>
            <?php foreach ($cursos as $curso){ ?>
              <option value="<?php echo htmlspecialchars($curso['id_curso']); ?>">
                <?php echo htmlspecialchars($curso['grado'].$curso['seccion']." ".$curso['moda']); ?>
              </option>
            <?php } ?>    
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