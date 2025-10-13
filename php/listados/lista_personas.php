<?php

include'../conesion.php';

$resultado = mysqli_query($con, "SELECT p.id_persona, p.dni, p.apellido, p.nombre, p.usuario, r.rol,
GROUP_CONCAT(t.tipo SEPARATOR ' | ') AS tipo
FROM personas as p
INNER JOIN tipo_persona_x_persona as tp on tp.id_persona = p.id_persona
INNER JOIN tipos_personas as t on tp.id_tipo_persona = t.id_tipo_persona
INNER JOIN roles as r on r.id_rol = p.id_rol
GROUP BY p.id_persona
ORDER BY p.apellido ASC
");

$personas = [];

if($resultado){
    while($persona = mysqli_fetch_assoc($resultado)){
        $personas[] = $persona;
    }
}



?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Personas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body { background-color: #f8f9fa; }
      .tarjeta-principal { max-width: 1200px; margin: 40px auto; background-color: #b2dfd1; border-radius: 1rem; box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 30px; }
      .tarjeta-principal h2 { margin-bottom: 25px; font-weight: 600; color: #343a40; }
      .tabla-organizada thead { background: rgba(15, 15, 15, 0.7); color: #fff; }
      .tabla-organizada tbody tr:hover { background-color: rgba(0, 0, 0, 0.05); }
      .tabla-organizada {
        font-size: 0.8rem;
      }
      .boton-volver { background-color: rgba(15, 15, 15, 0.7); color: #fff; border: none; padding: 10px 20px; border-radius: 0.5rem; transition: background 0.3s, transform 0.2s; }
      .boton-volver:hover { background-color: #00004F; transform: scale(1.05); color: #fff; }
    </style>
</head>
<body>
  <div class="tarjeta-principal">
    <h2>Lista de personas</h2>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>DNI</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Usuario</th>
            <th>Tipo</th>
            <th>Rol</th>
            <th>Accion</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($personas as $persona){ ?>
          <tr>
            <td><?php echo htmlspecialchars($persona['dni']); ?></td>
            <td><?php echo htmlspecialchars($persona['nombre']); ?></td>
            <td><?php echo htmlspecialchars($persona['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($persona['usuario']); ?></td>

            <td><?php echo htmlspecialchars($persona['tipo']); ?></td>
            <td><?php echo htmlspecialchars($persona['rol']); ?></td>
            <td>
              <a href="../modificaciones/editar_persona.php?id=<?php echo urlencode($persona['id_persona']); ?>" class="btn btn-warning btn-sm">Modificar</a>
              <a href="../modificaciones/eliminar_persona.php?id=<?php echo urlencode($persona['id_persona']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que desea eliminar a esta persona?');">Eliminar</a>
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