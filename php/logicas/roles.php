<?php

include "../conesion.php";

$rol = $_POST['rol'];

mysqli_query($con, "INSERT INTO roles (rol) VALUES ('$rol')");

mysqli_close($con);

header("Location: http://localhost/Dinamica/practica/home.php");
exit;
?>
