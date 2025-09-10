<?php
include "../conesion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST['nombre'];
    $turno = $_POST['turno'];
    $grupo = $_POST['grupo'];
    $id_curso = $_POST['id_curso'];



    mysqli_query($con, "INSERT INTO materias (nombre_materia, turno, grupo, id_curso) 
                            VALUES ('$nombre', '$turno', '$grupo', '$id_curso')");
 
    header("Location: https://localhost/Dinamica/practica/home.php");
    exit;
    
}

$cursos = mysqli_query($con, "SELECT * FROM cursos");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alta de Materia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100" style="background-color: rgba(57, 74, 75, 0.2);">
  <div class="card w-100 shadow-lg border-1" style="max-width: 500px; background-color: rgba(54, 150, 137, 0.2); border-radius: 1rem;">
    <div class="card-body">
      <h2 class="text-center fw-bold mb-4">Alta de Materia</h2>
      <form autocomplete="off" action="" method="post">
        <div class="mb-3">
          <input type="text" name="nombre" class="form-control" placeholder="Nombre de la materia" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>
        </div>
        <div class="mb-3">
          <input type="text" name="turno" class="form-control" placeholder="Turno" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>
        </div>
        <div class="mb-3">
          <input type="number" name="grupo" class="form-control" placeholder="Grupo" style="background-color: #b2dfd1; border-radius: 0.5rem;" required>
        </div>
        <div class="mb-4">
          <select name="id_curso" class="form-select" required>
            <option value="">Seleccione un curso</option>
            <?php while ($curso = mysqli_fetch_assoc($cursos)) { ?>
                <option value="<?php echo $curso['id_curso']; ?>">
                    <?php echo $curso['grado'] . "° -ID: " . $curso['id_curso']; ?>
                </option>
            <?php } ?>
          </select>
        </div>
        <input type="submit" value="Dar de alta" class="btn w-100 text-white"
          style="background-color: rgba(15, 15, 15, 0.7); border: 2px solid #00004F; transition: all 0.3s ease-in-out;"
          onmouseover="this.style.backgroundColor='rgb(80,0,100)'; this.style.transform='scale(1.05)'"
          onmouseout="this.style.backgroundColor='rgba(15,15,15,0.7)'; this.style.transform='scale(1)'">
      </form>
    </div>
  </div>
  <script src="../bootstrap-5.0.2-dist/js/bootstrap.js"></script>
</body>
</html>
