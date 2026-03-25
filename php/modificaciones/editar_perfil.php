<?php
include "../conesion.php"; include "../config.php"; session_start(); exigir_inicio_sesion(); $id = (int) $_SESSION['id_persona']; $perfil = db_fetch_one($con, "SELECT * FROM personas WHERE id_persona = ?", "i", [$id]); if ($_SERVER['REQUEST_METHOD'] === 'POST') { verificar_csrf(); $dni = intval($_POST['dni'] ?? 0); $nombre = trim($_POST['nombre'] ?? ''); $apellido = trim($_POST['apellido'] ?? ''); $mail = trim($_POST['mail'] ?? ''); $error_form = ''; if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) { $error_form = "El email no tiene un formato válido."; } elseif (empty($nombre) || empty($apellido)) { $error_form = "Nombre y apellido son obligatorios."; } if (empty($error_form)) { $stmt = mysqli_prepare($con, "UPDATE personas SET dni=?, nombre=?, apellido=?, mail=? WHERE id_persona=?"); if ($stmt) { mysqli_stmt_bind_param($stmt, "isssi", $dni, $nombre, $apellido, $mail, $id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt); } session_unset(); session_destroy(); redirigir('index.php'); } } ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Editar Perfil</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
<div class="form-card">
    <h2>Editar Perfil</h2>
    <?php if (!empty($error_form)): ?>
    <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error_form) ?></div>
    <?php endif; ?>
    <form method="post">
        <?php campo_csrf(); ?>
        <div class="mb-3">
            <label class="form-label">DNI</label>
            <input type="text" name="dni" class="form-control" value="<?= htmlspecialchars($perfil['dni']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($perfil['nombre']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($perfil['apellido']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="mail" class="form-control" value="<?= htmlspecialchars($perfil['mail']) ?>" required>
        </div>
        <button type="submit" class="btn-plei-submit">Guardar cambios</button>
        <a href="<?php echo url('home.php'); ?>" class="btn-plei-cancel mt-2 w-100">Cancelar</a>
    </form>
</div>
<script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
