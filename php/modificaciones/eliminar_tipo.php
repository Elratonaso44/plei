<?php


include "../conesion.php";

$id = $_GET['id'];

if ($id > 0) {
    mysqli_query($con, "DELETE FROM tipos_personas where id_tipo_persona='$id'");
}

header("Location: http://localhost/Dinamica/practica/php/listados/listar_modalidad.php");
exit;

?>