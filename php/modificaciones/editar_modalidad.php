<?php
include "../conesion.php";

$id = $_GET['id'];

$resultado = mysqli_query($con, "SELECT * from modalidad
WHERE id_modalidad = $id");

$modalidad = mysqli_fetch_assoc($resultado);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moda = $_POST['modalidad'];
    
    mysqli_query($con, "UPDATE modalidad SET moda='$moda'
    WHERE id_modalidad='$id'
    ");
    
    header("Location: http://localhost/Dinamica/practica/php/listados/listar_modalidad.php");
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
    <h2>Editar modalidad</h2>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Modalidad</label>
        <input type="text" name="modalidad" class="form-control" value="<?php echo htmlspecialchars($modalidad['moda']); ?>" required>
      </div>
   
      <button type="submit" class="btn btn-primary">Guardar Cambios</button>
      <a href="../listados/listar_modalidad.php" class="boton-volver ms-2">Cancelar</a>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
