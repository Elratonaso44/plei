<?php

include "../conesion.php";

$seccion = $_POST['seccion'];

mysqli_query($con, "INSERT INTO secciones (seccion) VALUES ('$seccion')");
mysqli_close($con);

header("Location:http://localhost/Dinamica/practica/home.php");















?>
