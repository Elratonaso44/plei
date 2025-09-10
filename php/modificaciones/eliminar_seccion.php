<?php
include '../conesion.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($id) {
    mysqli_query($con, "DELETE FROM secciones WHERE id_seccion = '$id'");
    header('Location: lista_secciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Sección</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="alert alert-success text-center">
            Sección eliminada correctamente.<br>
            <a href="lista_secciones.php" class="btn btn-primary mt-3">Volver a la lista</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
