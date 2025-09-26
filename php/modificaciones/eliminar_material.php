<?php


include "../conesion.php";

$id = $_GET['id'];

if ($id > 0) {
    mysqli_query($con, "DELETE FROM materiales where id_material='$id'");
}

header("Location: http://localhost/Dinamica/practica/php/listados/lista_materiax_docente.php");
exit;

?>