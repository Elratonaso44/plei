<?php


include "../conesion.php";

$id = $_GET['id'];

if ($id > 0) {
    mysqli_query($con, "DELETE FROM preceptor_x_curso where id_preceptor_x_curso='$id'");
}

header("Location: http://localhost/Dinamica/practica/php/listados/lista_alumnos.php");
exit;

?>