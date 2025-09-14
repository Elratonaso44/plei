<?php
include '../conesion.php';
session_start();
$id_docente = $_SESSION['id_persona'];

$materias_docente = [];
if ($id_docente) {
  $resultado = mysqli_query($con, "SELECT m.nombre_materia, m.turno, m.grupo, c.grado, mo.moda, s.seccion
    FROM materias AS m
    INNER JOIN cursos AS c ON c.id_curso = m.id_curso
    INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
    INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
    INNER JOIN docentes_x_materia AS dm ON dm.id_materia = m.id_materia
    WHERE dm.id_persona = '$id_docente'");
    
  if ($resultado) {
    while ($materia = mysqli_fetch_assoc($resultado)) {
      $materias_docente[] = $materia;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Materias Asignadas</title>
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
    <h2>Mis Materias Asignadas</h2>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>ID Materia</th>
            <th>Nombre de la materia</th>
            <th>Turno</th>
            <th>Grupo</th>
            <th>ID Curso</th>
          </tr>
        <tbody>
          <?php foreach ($materias_docente as $mat) { ?>
            <tr>
              <td><?php htmlspecialchars($mat['nombre_materia']) ?></td>
              <td><?php htmlspecialchars($mat['turno']) ?></td>
              <td><?php htmlspecialchars($mat['grupo']) ?></td>
            <td><?php htmlspecialchars($mat['grado']) ?></td>
            <td><?php htmlspecialchars($mat['moda']) ?></td>
            <td><?php htmlspecialchars($mat['seccion']) ?></td>
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
