<?php
$user = "root";
$pass = "";
$database = "plei_db";
$host = "localhost";

$con=mysqli_connect($host, $user,$pass,$database);

if(mysqli_connect_errno()){
    die("Error al conectar a la base de datos <br>");
} else {

}

?>
