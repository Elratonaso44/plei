<?php

include "../conesion.php";

$tipo = $_POST['tipo'];


mysqli_query($con, "INSERT INTO tipos_personas (tipo) VALUES ('$tipo')");
mysqli_close($con);

header("Location:http://localhost/Dinamica/practica/home.html");










?>
