<?php
include "../conesion.php";
include "../config.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirigir('index.php');
}

verificar_csrf();

$email = trim((string)($_POST['email'] ?? ''));
$password = trim((string)($_POST['pass'] ?? ''));

if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirigir('index.php?error=campos');
}

$persona = db_fetch_one(
    $con,
    "SELECT p.id_persona, p.nombre, p.apellido, p.usuario, p.password, r.rol
     FROM personas AS p
     LEFT JOIN roles AS r ON r.id_rol = p.id_rol
     WHERE p.mail = ?
     LIMIT 1",
    's',
    [$email]
);

if (!$persona) {
    redirigir('index.php?error=usuario');
}

$id_persona = (int)$persona['id_persona'];
$password_guardada = (string)$persona['password'];
$forzar_cambio_password = false;
$password_valida = false;

$tiene_flag_cambio = columna_bd_existe($con, 'personas', 'requiere_cambio_password');
$tiene_fecha_cambio = columna_bd_existe($con, 'personas', 'password_actualizada_en');

if (es_hash_password($password_guardada)) {
    $password_valida = password_verify($password, $password_guardada);

    if ($password_valida) {
        if ($tiene_flag_cambio) {
            $estado = db_fetch_one(
                $con,
                "SELECT requiere_cambio_password
                 FROM personas
                 WHERE id_persona = ?
                 LIMIT 1",
                'i',
                [$id_persona]
            );
            if ((int)($estado['requiere_cambio_password'] ?? 0) === 1) {
                $forzar_cambio_password = true;
            }
        }

        if (password_es_debil($password)) {
            $forzar_cambio_password = true;
            if ($tiene_flag_cambio) {
                $sql = "UPDATE personas SET requiere_cambio_password = 1 WHERE id_persona = ?";
                $sentencia = mysqli_prepare($con, $sql);
                if ($sentencia) {
                    mysqli_stmt_bind_param($sentencia, 'i', $id_persona);
                    mysqli_stmt_execute($sentencia);
                    mysqli_stmt_close($sentencia);
                }
            }
        }
    }
} else {
    if (hash_equals($password_guardada, $password)) {
        $password_valida = true;
        $forzar_cambio_password = true;
        $nuevo_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        if ($tiene_flag_cambio && $tiene_fecha_cambio) {
            $sql = "UPDATE personas
                    SET password = ?, requiere_cambio_password = 1, password_actualizada_en = NULL
                    WHERE id_persona = ?";
            $sentencia = mysqli_prepare($con, $sql);
            if ($sentencia) {
                mysqli_stmt_bind_param($sentencia, 'si', $nuevo_hash, $id_persona);
                mysqli_stmt_execute($sentencia);
                mysqli_stmt_close($sentencia);
            }
        } elseif ($tiene_flag_cambio) {
            $sql = "UPDATE personas
                    SET password = ?, requiere_cambio_password = 1
                    WHERE id_persona = ?";
            $sentencia = mysqli_prepare($con, $sql);
            if ($sentencia) {
                mysqli_stmt_bind_param($sentencia, 'si', $nuevo_hash, $id_persona);
                mysqli_stmt_execute($sentencia);
                mysqli_stmt_close($sentencia);
            }
        } else {
            $sql = "UPDATE personas SET password = ? WHERE id_persona = ?";
            $sentencia = mysqli_prepare($con, $sql);
            if ($sentencia) {
                mysqli_stmt_bind_param($sentencia, 'si', $nuevo_hash, $id_persona);
                mysqli_stmt_execute($sentencia);
                mysqli_stmt_close($sentencia);
            }
        }
    }
}

if (!$password_valida) {
    redirigir('index.php?error=password');
}

$tipos = obtener_tipos_usuario($con, $id_persona);

session_regenerate_id(true);
$_SESSION['username'] = (string)$persona['usuario'];
$_SESSION['nombre'] = (string)$persona['nombre'];
$_SESSION['apellido'] = (string)$persona['apellido'];
$_SESSION['id_persona'] = $id_persona;
$_SESSION['rol'] = (string)($persona['rol'] ?? '');
$_SESSION['tipo'] = $tipos[0] ?? '';
$_SESSION['forzar_cambio_password'] = $forzar_cambio_password ? 1 : 0;

mysqli_close($con);

if ($forzar_cambio_password) {
    redirigir('php/modificaciones/cambiar_password_obligatorio.php');
}

redirigir('home.php');
