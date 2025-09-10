<?php

include "../conesion.php";



  $resultado = mysqli_query($con, "SELECT * FROM tipos_personas");  
  $tipos_personas = [];

  if($resultado){    
    while($tipo = mysqli_fetch_assoc($resultado)){      
      $tipos_personas[] = $tipo;
    }
  } 


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Tipos de Persona</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body { background-color: #f8f9fa; }
      .tarjeta-principal { max-width: 700px; margin: 40px auto; background-color: #b2dfd1; border-radius: 1rem; box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 30px; }
      .tarjeta-principal h2 { margin-bottom: 25px; font-weight: 600; color: #343a40; }
      .tabla-organizada thead { background: rgba(15, 15, 15, 0.7); color: #fff; }
      .tabla-organizada tbody tr:hover { background-color: rgba(0, 0, 0, 0.05); }
      .boton-volver { background-color: rgba(15, 15, 15, 0.7); color: #fff; border: none; padding: 10px 20px; border-radius: 0.5rem; transition: background 0.3s, transform 0.2s; }
      .boton-volver:hover { background-color: #00004F; transform: scale(1.05); color: #fff; }
    </style>
</head>
<body>
  <div class="tarjeta-principal">
    <h2>Tipos de Persona</h2>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle tabla-organizada">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tipo</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tipos_personas as $tipo){ ?>
          <tr>
            <td><?php echo htmlspecialchars($tipo['id_tipo_persona']); ?></td>
            <td><?php echo htmlspecialchars($tipo['tipo']); ?></td>
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