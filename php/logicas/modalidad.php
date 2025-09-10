<?php

include "../conesion.php";

$moda = $_POST['moda'];

mysqli_query($con, "INSERT INTO modalidad (moda) VALUES ('$moda')");
mysqli_close($con);

header("Location:http://localhost/Dinamica/practica/home.html");








?>
