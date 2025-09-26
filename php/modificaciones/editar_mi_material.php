<?php
include "../conesion.php";

$id = $_GET['id'];

$resultado = mysqli_query($con, "SELECT mat.id_material, mat.tipo_material, mat.unidad, mat.url, mat.id_materia
FROM materiales as mat
INNER JOIN materias as m on m.id_materia=mat.id_materia
WHERE m.id_materia = $id");

$materiales = [];

if ($resultado) {
    while($material = mysqli_fetch_assoc($resultado)){
        $materiales[] = $material;
    }
}

$resultado = mysqli_query($con, "SELECT m.nombre_materia, m.turno, m.grupo, m.id_materia, c.grado, mo.moda, s.seccion, p.id_persona 
from materias as m INNER JOIN cursos as c on c.id_curso=m.id_curso 
INNER JOIN modalidad as mo on mo.id_modalidad=c.id_modalidad 
INNER JOIN secciones as s on s.id_seccion=c.id_seccion
INNER JOIN docentes_x_materia as dm on dm.id_materia=m.id_materia 
INNER JOIN personas as p on p.id_persona=dm.id_persona");

$materias = [];

if ($resultado){
    while($materia = mysqli_fetch_assoc($resultado)){
        $materias[]=$materia;
    }
}


if($_SERVER["REQUEST_METHOD"] === "POST"){
    $id_material = $_POST['id_material'];
    $tipo = $_POST['tipoM'];
    $url = $_POST['url'];
    $materia = $_POST['materia'];
    
    mysqli_query($con, "UPDATE materiales SET tipo_material='$tipo', url='$url', id_materia='$materia' 
      WHERE id_material='$id_material'");
    header("Location: editar_mi_material.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Material</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
  <div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
    <div class="card-body">
      <h2 class="text-center fw-bold mb-4">Editar Material</h2>
      <?php if(count($materiales) === 0){ ?>
        <div class="alert alert-info">No tienes materiales para editar.</div>
      <?php } ?>
        <?php foreach($materiales as $mat) {?>
      <form autocomplete="off" action="" method="post" class="mb-4">
        <input type="hidden" name="id_material" value="<?php echo $mat['id_material']; ?>">
        <div class="mb-3">
          <input type="text" name="tipoM" class="form-control" placeholder="Tipo de material" style="background-color: #b2dfd1; border-radius: 0.5rem;" value="<?php echo htmlspecialchars($mat['tipo_material']); ?>">
        </div>
        <div class="mb-3">
          <input type="text" name="url" class="form-control" placeholder="URL" style="background-color: #b2dfd1; border-radius: 0.5rem;" value="<?php echo htmlspecialchars($mat['url']); ?>">
        </div>
        <?php } ?>
        <div class="mb-4">
          <select name="materia" class="form-select" required>
            <option value="">Materia</option>
            <?php foreach ($materias as $materia){ ?>
              <option value="<?php echo htmlspecialchars($materia['id_materia']); ?>" <?php if($materia['id_materia'] == $mat['id_materia']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($materia['nombre_materia']); ?>
              </option>
            <?php } ?>    
          </select>
        </div>
        <input type="submit" value="Guardar cambios" class="btn w-100 text-white"
          style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"
          onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"
          onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">
      </form>

    </div>
  </div>
  <script src="../bootstrap-5.0.2-dist/js/bootstrap.js"></script>
</body>
</html>
