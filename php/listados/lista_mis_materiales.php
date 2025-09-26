<?php

include '../conesion.php';

session_start();

$id = $_GET['id'];



$resultado=mysqli_query($con,"SELECT mat.id_material, mat.tipo_material, mat.unidad, mat.url, mat.id_materia, m.nombre_materia
FROM materiales as mat
INNER JOIN materias as m on m.id_materia=mat.id_materia
WHERE m.id_materia = $id
");

$materialesxmateria=[];

if($resultado){    
    while($matxmat= mysqli_fetch_assoc($resultado)){      
      $materialesxmateria[] = $matxmat;
    }
  } 


?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Materiales</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>Nombre De la materia</th>
            <th>Unidad</th>
            <th>Tipo Material</th>
            <th>URL</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materialesxmateria as $mat) { ?>
            <tr>
              <td><?= htmlspecialchars($mat['nombre_materia']) ?></td>
              <td><?= htmlspecialchars($mat['unidad']) ?></td>
              <td><?= htmlspecialchars($mat['tipo_material']) ?></td>
              <td><?= htmlspecialchars($mat['url']) ?></td>
              <td>
                <a href="../modificaciones/editar_mi_material.php?id=<?php echo urlencode($mat['id_material']); ?>" class="btn btn-warning btn-sm">Modificar</a>
                <a href="../modificaciones/eliminar_material.php?id=<?php echo urlencode($mat['id_material']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que desea eliminar este material?');">Eliminar</a>
            </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="text-end mt-3">
      <a href="http://localhost/Dinamica/practica/php/listados/lista_materiax_docente.php" class="boton-volver">Volver</a>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>