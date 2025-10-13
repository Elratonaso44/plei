<?php
include "../conesion.php";

$id = $_GET['id'];

$resultado = mysqli_query($con, "SELECT p.id_persona, p.dni, p.apellido, p.nombre, p.usuario, r.rol,
GROUP_CONCAT(t.tipo SEPARATOR ' | ') AS tipo
FROM personas as p
INNER JOIN tipo_persona_x_persona as tp on tp.id_persona = p.id_persona
INNER JOIN tipos_personas as t on tp.id_tipo_persona = t.id_tipo_persona
INNER JOIN roles as r on r.id_rol = p.id_rol
WHERE p.id_persona = $id");

$personas = mysqli_fetch_assoc($resultado);




$roles = [];
$resultado = mysqli_query($con, "SELECT id_rol, rol FROM roles");

if ($resultado) {
  while ($rol = mysqli_fetch_assoc($resultado)) {
    $roles[] = $rol;
  }
}

$roles_asignados = [];
$resultado = mysqli_query($con, "SELECT p.id_rol, r.id_rol,r.rol from personas as p 
INNER JOIN roles as r on r.id_rol = p.id_rol
WHERE p.id_persona = '$id'");


if ($resultado) {
  while ($rol = mysqli_fetch_assoc($resultado)) {
    $roles_asignados[] = $rol['id_rol'];
  }
}


$resultados = mysqli_query($con , "SELECT t.id_tipo_persona, t.tipo 
FROM tipos_personas as t");

$tipos_asignados = [];
$resultado = mysqli_query($con, "SELECT id_tipo_persona 
FROM tipo_persona_x_persona WHERE id_persona = '$id'");

if ($resultado) {
  while ($asignado = mysqli_fetch_assoc($resultado)) {
    $tipos_asignados[] = $asignado['id_tipo_persona'];
  }
}

$tipo = [];
if ($resultados){
  while ($tipos = mysqli_fetch_assoc($resultados)) {
    $tipo[] = $tipos;
  }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $dni = $_POST['dni'];
  $nombre = $_POST['nombre'];
  $apellido = $_POST['apellido'];
  $mail = $_POST['usuario'];
  $tipos = $_POST['tipo'];
  $rol = $_POST['rol'];

  mysqli_query($con, "UPDATE personas SET 
  dni='$dni', nombre='$nombre', apellido='$apellido', usuario='$mail', 
  id_rol='$rol' WHERE id_persona='$id'");

  mysqli_query($con, "DELETE FROM tipo_persona_x_persona WHERE id_persona='$id'");
  if ($tipos) {
    foreach ($tipos as $tipo) {
      mysqli_query($con, "INSERT INTO tipo_persona_x_persona (id_persona, id_tipo_persona) VALUES ('$id', '$tipo')");
    }
  }

  header("Location: http://localhost/Dinamica/practica/php/listados/lista_personas.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Persona</title>
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
    <h2>Editar Persona</h2>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">DNI</label>
        <input type="text" name="dni" class="form-control" value="<?php echo htmlspecialchars($personas['dni']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($personas['nombre']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Apellido</label>
        <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($personas['apellido']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input type="text" name="usuario" class="form-control" value="<?php echo htmlspecialchars($personas['usuario']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Rol</label>
        <select name="rol" class="form-control">
          <?php foreach($roles as $r){ ?>
            <option value="<?php echo htmlspecialchars($r['id_rol']) ?>" 
            <?php echo in_array($r['id_rol'], $roles_asignados) 
            ? 'selected class="bg-info fw-bold"' : '' ?>>
            <?php echo htmlspecialchars($r['rol']) ?>
          </option>
          <?php } ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Tipo persona</label>
        <select name="tipo[]" class="form-control" multiple>
          <?php foreach($tipo as $t){ ?>
            <option value="<?php echo htmlspecialchars($t['id_tipo_persona']) ?>" 
            <?php echo in_array($t['id_tipo_persona'], $tipos_asignados) 
            ? 'selected class="bg-info fw-bold"' : '' ?>>
            <?php echo htmlspecialchars($t['tipo']) ?>
          </option>
          <?php } ?>
        </select>
      </div>
      
   
      <button type="submit" class="btn btn-primary">Guardar Cambios</button>
      <a href="../listados/lista_personas.php" class="boton-volver ms-2">Cancelar</a>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
