<?php


include "../conesion.php";

$id = $_GET['id'];

if ($id > 0) {
    mysqli_query($con, "DELETE FROM modalidad where id_modalidad='$id'");
}

header("Location: http://localhost/Dinamica/practica/php/listados/lista_alumnos.php");
exit;

?>