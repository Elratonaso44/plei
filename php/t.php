<?php

$campos = ['dni', 'apellido', 'nombre', 'email', 'user', 'pass'];
foreach ($campos as $campo) {
    if (empty($_POST[$campo])) {
        die("Error: El campo $campo es obligatorio.");
    }
}


$dni      = intval($_POST['dni']);
$apellido = htmlspecialchars(trim($_POST['apellido']));
$nombre   = htmlspecialchars(trim($_POST['nombre']));
$email    = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
$usuario  = htmlspecialchars(trim($_POST['user']));
$pass     = password_hash($_POST['pass'], PASSWORD_BCRYPT); 

if (!$email) {
    die("Error: Email inválido.");
}

$stmt = $conexion->prepare("INSERT INTO usuarios (dni, apellido, nombre, email, user, pass) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $dni, $apellido, $nombre, $email, $usuario, $pass);

if ($stmt->execute()) {
    echo "<script>alert('Registro exitoso'); window.location.href='index.html';</script>";
} else {
    echo "Error al registrar: " . $stmt->error;
}

$stmt->close();
$conexion->close();
?>
