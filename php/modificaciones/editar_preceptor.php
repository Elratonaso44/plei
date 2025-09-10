<?php
include "../conesion.php";

$id = $_GET['id'];

$resultado = mysqli_query($con, "SELECT * from personas 
WHERE id_persona = $id");

$preceptor = mysqli_fetch_assoc($resultado);

$cursos_asignados = [];
$cursos = mysqli_query($con, "SELECT id_curso 
FROM preceptor_x_curso WHERE id_persona = $id");
if ($cursos) {
    while ($curso = mysqli_fetch_assoc($cursos)) {
        $cursos_asignados[] = $curso['id_curso'];
    }
}

$resultado = mysqli_query($con, "SELECT c.grado, c.id_curso, c.id_modalidad, c.id_seccion, s.seccion, m.moda, m.id_modalidad
FROM cursos as c
INNER JOIN secciones as s on s.id_seccion = c.id_seccion
INNER JOIN modalidad as m on m.id_modalidad = c.id_modalidad

");

$cursos = [];

if($resultado){
    while($curso = mysqli_fetch_assoc($resultado)){
        $cursos[]=$curso;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = $_POST['dni'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $mail = $_POST["mail"];
    $cursos = $_POST["cursos"];

    mysqli_query($con, "UPDATE personas 
    SET dni='$dni', nombre='$nombre', apellido='$apellido', mail='$mail' 
    WHERE id_persona='$id'");

    mysqli_query($con, "DELETE FROM preceptor_x_curso WHERE id_persona='$id'");

    if ($cursos) {
        foreach ($cursos as $curso) {
            mysqli_query($con, "INSERT INTO preceptor_x_curso 
            (id_persona, id_curso) VALUES ('$id', '$curso')");
        }
    }
    
    header("Location: http://localhost/Dinamica/practica/php/listados/lista_preceptores.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Preceptor</title>
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
    <h2>Editar Preceptor</h2>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">DNI</label>
        <input type="text" name="dni" class="form-control" value="<?php echo htmlspecialchars($preceptor['dni']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($preceptor['nombre']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Apellido</label>
        <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($preceptor['apellido']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="text" name="mail" class="form-control" value="<?php echo htmlspecialchars($preceptor['mail']); ?>" required>
      </div>
       <div class="mb-3">
        <label class="form-label">Cursos a cargo</label>
        <select name="cursos[]" class="form-control" multiple>
            <?php foreach($cursos as $curso){ ?>
            <option value="<?php echo htmlspecialchars($curso['id_curso'])?>"
                <?php echo in_array($curso['id_curso'], $cursos_asignados) ? 'selected class=\'bg-info fw-bold\'' : '' ?>>
                <?php echo htmlspecialchars($curso['grado']."° ".$curso['seccion']." ".$curso['moda']) ?>
            </option>
            <?php } ?>
        </select>
      </div>
      
   
      <button type="submit" class="btn btn-primary">Guardar Cambios</button>
      <a href="../listados/lista_preceptores.php" class="boton-volver ms-2">Cancelar</a>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
