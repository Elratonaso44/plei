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

$con->query("DROP TRIGGER IF EXISTS asignar_alumno");

$sql = "
CREATE TRIGGER asignar_alumno
AFTER INSERT ON personas
FOR EACH ROW
BEGIN
    IF NEW.id_rol = 2 THEN
        INSERT INTO tipo_persona_x_persona (id_persona, id_tipo_persona)
        VALUES (NEW.id_persona, 7);
    END IF;
END;
";

?>
