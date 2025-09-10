<?php

include "../conesion.php";




$resultado = mysqli_query($con, "SELECT * FROM  materias");

$materias = [];

if ($resultado){
    while($materia = mysqli_fetch_assoc($resultado)){
        $materias[]=$materia;
    }
}


?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Materias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body { background-color: #f8f9fa; }
      .tarjeta-principal { max-width: 1100px; margin: 40px auto; background-color: #b2dfd1; border-radius: 1rem; box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 30px; }
      .tarjeta-principal h2 { margin-bottom: 25px; font-weight: 600; color: #343a40; }
      .tabla-organizada thead { background: rgba(15, 15, 15, 0.7); color: #fff; }
      .tabla-organizada tbody tr:hover { background-color: rgba(0, 0, 0, 0.05); }
      .boton-volver { background-color: rgba(15, 15, 15, 0.7); color: #fff; border: none; padding: 10px 20px; border-radius: 0.5rem; transition: background 0.3s, transform 0.2s; }
      .boton-volver:hover { background-color: #00004F; transform: scale(1.05); color: #fff; }
    </style>
</head>
<body>
  <div class="tarjeta-principal">
    <h2>Lista de Materias</h2>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Turno</th>
            <th>Grupo</th>
            <th>ID Curso</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materias as $materia){ ?>
          <tr>
            <td><?php echo htmlspecialchars($materia['id_materia']); ?></td>
            <td><?php echo htmlspecialchars($materia['nombre_materia']); ?></td>
            <td><?php echo htmlspecialchars($materia['turno']); ?></td>
            <td><?php echo htmlspecialchars($materia['grupo']); ?></td>
            <td><?php echo htmlspecialchars($materia['id_curso']); ?></td>
            <td>
              <a href="../modificaciones/editar_materia.php?id=<?php echo urlencode($materia['id_materia']); ?>" class="btn btn-warning btn-sm">Modificar</a>
              <a href="../modificaciones/eliminar_materia.php?id=<?php echo urlencode($materia['id_materia']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que desea eliminar este docente?');">Eliminar</a>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="text-end mt-3">
      <a href="http://localhost/Dinamica/practica/home.php" class="boton-volver">Volver</a>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>