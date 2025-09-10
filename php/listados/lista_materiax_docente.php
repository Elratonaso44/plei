<?php

include '../conesion.php';

session_start();

$idp = $_SESSION['id_persona'];



$resultado=mysqli_query($con,"SELECT nombre_materia, turno, grupo, c.grado, mo.moda, s.seccion, p.id_persona 
from materias as m INNER JOIN cursos as c on c.id_curso=m.id_curso 
INNER JOIN modalidad as mo on mo.id_modalidad=c.id_modalidad 
INNER JOIN secciones as s on s.id_seccion=c.id_seccion
INNER JOIN docentes_x_materia as dm on dm.id_materia=m.id_materia 
INNER JOIN personas as p on p.id_persona=dm.id_persona
;
");

$materiasxdocente=[];

if($resultado){    
    while($matxdoc = mysqli_fetch_assoc($resultado)){      
      $materiasxdocente[] = $matxdoc;
    }
  } 


?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Materias por Docente</title>
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
            <th>Turno</th>
            <th>Grupo</th>
            <th>Grado</th>
            <th>Modalidad</th>
            <th>Seccion</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materiasxdocente as $mat) { ?>
            <tr>
              <td><?= htmlspecialchars($mat['nombre_materia']) ?></td>
              <td><?= htmlspecialchars($mat['turno']) ?></td>
              <td><?= htmlspecialchars($mat['grupo']) ?></td>
              <td><?= htmlspecialchars($mat['grado']) ?></td>
              <td><?= htmlspecialchars($mat['moda']) ?></td>
              <td><?= htmlspecialchars($mat['seccion']) ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="text-end mt-3">
      <a href="http://localhost/Dinamica/practica/home.php" class="boton-volver">Volver a Docentes</a>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>