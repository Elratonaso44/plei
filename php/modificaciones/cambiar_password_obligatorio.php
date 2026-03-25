<?php
include "../conesion.php";
include "../config.php";
session_start();
exigir_inicio_sesion();

$id_persona = (int)($_SESSION['id_persona'] ?? 0);
if (empty($_SESSION['forzar_cambio_password'])) {
    redirigir('home.php');
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $password_nueva = trim((string)($_POST['password_nueva'] ?? ''));
    $password_confirmar = trim((string)($_POST['password_confirmar'] ?? ''));

    if ($password_nueva === '' || $password_confirmar === '') {
        $error = 'Completá ambos campos de contraseña.';
    } elseif ($password_nueva !== $password_confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (password_es_debil($password_nueva)) {
        $error = 'La nueva contraseña es débil. Usá al menos 8 caracteres y evitá claves comunes.';
    } else {
        $hash_nuevo = password_hash($password_nueva, PASSWORD_BCRYPT, ['cost' => 12]);
        $tiene_flag_cambio = columna_bd_existe($con, 'personas', 'requiere_cambio_password');
        $tiene_fecha_cambio = columna_bd_existe($con, 'personas', 'password_actualizada_en');

        if ($tiene_flag_cambio && $tiene_fecha_cambio) {
            $sql = "UPDATE personas
                    SET password = ?, requiere_cambio_password = 0, password_actualizada_en = NOW()
                    WHERE id_persona = ?";
        } elseif ($tiene_flag_cambio) {
            $sql = "UPDATE personas
                    SET password = ?, requiere_cambio_password = 0
                    WHERE id_persona = ?";
        } else {
            $sql = "UPDATE personas SET password = ? WHERE id_persona = ?";
        }

        $sentencia = mysqli_prepare($con, $sql);
        if (!$sentencia) {
            $error = 'No se pudo actualizar la contraseña.';
        } else {
            mysqli_stmt_bind_param($sentencia, 'si', $hash_nuevo, $id_persona);
            mysqli_stmt_execute($sentencia);
            mysqli_stmt_close($sentencia);

            $_SESSION['forzar_cambio_password'] = 0;
            $mensaje = 'Contraseña actualizada correctamente. Ya podés usar el sistema.';
            redirigir('home.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Cambio de contraseña obligatorio</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
    <div class="form-card" style="max-width:560px">
        <h2>Cambio de contraseña obligatorio</h2>
        <p class="form-subtitle">
            Por seguridad, necesitás definir una contraseña nueva antes de continuar.
        </p>

        <?php if ($mensaje !== ''): ?>
            <div class="alert-ok"><i class="bi bi-check-circle-fill"></i><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?php campo_csrf(); ?>
            <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" name="password_nueva" class="form-control" required minlength="8" maxlength="100" placeholder="Ingresá una contraseña segura">
            </div>
            <div class="mb-4">
                <label class="form-label">Repetir nueva contraseña</label>
                <input type="password" name="password_confirmar" class="form-control" required minlength="8" maxlength="100" placeholder="Repetí la contraseña">
            </div>
            <button type="submit" class="btn-plei-submit">Guardar contraseña</button>
        </form>

        <div class="text-center mt-3">
            <a href="<?php echo url('php/cerrar_sesion.php'); ?>" class="btn-plei-cancel">Cerrar sesión</a>
        </div>
    </div>
    <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
