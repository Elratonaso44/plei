<?php
include "../conesion.php";

$id = $_GET['id'];

$resultado = mysqli_query($con, "SELECT * from personas 
WHERE id_persona = $id");

$alumno = mysqli_fetch_assoc($resultado);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = $_POST['dni'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $mail = $_POST["mail"];

    mysqli_query($con, "UPDATE personas SET dni='$dni', 
    nombre='$nombre', apellido='$apellido', mail='$mail' WHERE id_persona='$id'
    ");
    
    header("Location: http://localhost/Dinamica/practica/php/listados/lista_alumnos.php");
    exit;
    
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Alumno</title>
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
    <h2>Editar Alumno</h2>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">DNI</label>
        <input type="text" name="dni" class="form-control" value="<?php echo htmlspecialchars($alumno['dni']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($alumno['nombre']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Apellido</label>
        <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($alumno['apellido']); ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="text" name="mail" class="form-control" value="<?php echo htmlspecialchars($alumno['mail']); ?>" required>
      </div>
   
      <button type="submit" class="btn btn-primary">Guardar Cambios</button>
      <a href="../listados/lista_alumnos.php" class="boton-volver ms-2">Cancelar</a>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
