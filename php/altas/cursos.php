<?php

include '../conesion.php';

echo mysqli_error($con);

$resultado = mysqli_query($con, "SELECT * FROM modalidad");
$modalidades = [];

if($resultado){
    while($modalidad = mysqli_fetch_assoc($resultado)){
        $modalidades[] = $modalidad;
    }
}

$resultado = mysqli_query($con, "SELECT * FROM secciones");
$secciones = [];

if($resultado){
    while($seccion = mysqli_fetch_assoc($resultado)){
        $secciones[] = $seccion;
    }
}




if($_SERVER["REQUEST_METHOD"] === "POST"){
    $grado = $_POST["grado"];
    $modalidad = $_POST["modalidad"];
    $seccion = $_POST["seccion"];

    
    mysqli_query($con,"INSERT INTO cursos (grado,id_modalidad,id_seccion) 
    VALUES ('$grado','$modalidad','$seccion')");
    mysqli_close($con);

}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta Curso</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
  <div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
    <div class="card-body">
      <h2 class="text-center fw-bold mb-4">Alta de Curso</h2>
      <form autocomplete="off" action="" method="POST">
        <div class="mb-3">
          <input type="number" name="grado" class="form-control" placeholder="Ingrese el grado" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>
        </div>
        <div class="mb-3">
          <select name="modalidad" class="form-select" required>
            <option value="">Seleccione una modalidad</option>
            <?php foreach ($modalidades as $modalidad){ ?>
              <option value="<?php echo htmlspecialchars($modalidad['id_modalidad']); ?>">
                <?php echo htmlspecialchars($modalidad['moda']); ?>
              </option><?php }?>    
          </select>
        </div>
        <div class="mb-3">
          <select name="seccion" class="form-select" required>
            <option value="">Seleccione una sección</option>
            <?php foreach ($secciones as $seccion){ ?>
              <option value="<?php echo htmlspecialchars($seccion['id_seccion']); ?>">
                <?php echo htmlspecialchars($seccion['seccion']); ?>
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