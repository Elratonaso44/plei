<?php
include '../conesion.php';
include '../config.php';
session_start();
exigir_rol('administrador');

$tipos_personas = db_fetch_all($con, "SELECT id_tipo_persona, tipo FROM tipos_personas ORDER BY tipo");

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $dni = (int)($_POST['dni'] ?? 0);
    $apellido = trim((string)($_POST['apellido'] ?? ''));
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $usuario = trim((string)($_POST['user'] ?? ''));
    $password = trim((string)($_POST['pass'] ?? ''));
    $id_tipo = (int)($_POST['tipo'] ?? 0);

    if (!$dni || $apellido === '' || $nombre === '' || $email === '' || $usuario === '' || $password === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no tiene un formato válido.';
    } elseif (strlen($usuario) < 3) {
        $error = 'El nombre de usuario debe tener al menos 3 caracteres.';
    } else {
        $tipos_validos = ids_tipo_persona_existentes($con, [$id_tipo]);
        if (!in_array($id_tipo, $tipos_validos, true)) {
            $error = 'Seleccioná un tipo de persona válido.';
        }
    }

    if ($error === '') {
        $id_rol = resolver_rol_id_desde_tipos($con, [$id_tipo]);
        if ($id_rol === null || $id_rol <= 0) {
            $error = 'No se pudo resolver un rol compatible para el tipo seleccionado.';
        }
    }

    if ($error === '') {
        $existe = db_fetch_one(
            $con,
            "SELECT id_persona
             FROM personas
             WHERE dni = ? OR mail = ? OR usuario = ?
             LIMIT 1",
            'iss',
            [$dni, $email, $usuario]
        );

        if ($existe) {
            $error = 'Ya existe un usuario con ese DNI, email o nombre de usuario.';
        } else {
            mysqli_begin_transaction($con);
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $sentencia = mysqli_prepare(
                    $con,
                    "INSERT INTO personas (dni, apellido, nombre, mail, usuario, password, id_rol)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                if (!$sentencia) {
                    throw new RuntimeException('No se pudo registrar el usuario.');
                }

                mysqli_stmt_bind_param(
                    $sentencia,
                    'isssssi',
                    $dni,
                    $apellido,
                    $nombre,
                    $email,
                    $usuario,
                    $hash,
                    $id_rol
                );
                if (!mysqli_stmt_execute($sentencia)) {
                    $errno = mysqli_errno($con);
                    mysqli_stmt_close($sentencia);
                    if ($errno === 1062) {
                        throw new RuntimeException('Ya existe un usuario con ese DNI, email o nombre de usuario.');
                    }
                    throw new RuntimeException('No se pudo registrar el usuario.');
                }
                $nuevo_id = (int)mysqli_insert_id($con);
                mysqli_stmt_close($sentencia);

                if ($nuevo_id <= 0) {
                    throw new RuntimeException('No se pudo registrar el usuario.');
                }

                $sentencia_tipo = mysqli_prepare(
                    $con,
                    "INSERT INTO tipo_persona_x_persona (id_persona, id_tipo_persona)
                     VALUES (?, ?)"
                );
                if (!$sentencia_tipo) {
                    throw new RuntimeException('Usuario creado, pero no se pudo asignar el tipo.');
                }

                mysqli_stmt_bind_param($sentencia_tipo, 'ii', $nuevo_id, $id_tipo);
                if (!mysqli_stmt_execute($sentencia_tipo)) {
                    mysqli_stmt_close($sentencia_tipo);
                    throw new RuntimeException('Usuario creado, pero no se pudo asignar el tipo.');
                }
                mysqli_stmt_close($sentencia_tipo);

                if (!sincronizar_rol_persona_desde_tipos($con, $nuevo_id, [$id_tipo])) {
                    throw new RuntimeException('Usuario creado, pero no se pudo sincronizar el rol con el tipo.');
                }

                mysqli_commit($con);
                $mensaje = 'Usuario registrado correctamente.';
            } catch (Throwable $e) {
                mysqli_rollback($con);
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Registrar usuario</title>
    <link href="../../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../plei.css">
</head>
<body class="form-page-body">
    <div class="form-card form-card-wide">
        <div class="login-brand">
            <div class="logo-text" style="font-size:2rem">PLEI</div>
            <div class="logo-sub">Registrar nuevo usuario</div>
        </div>
        <div class="login-divider"></div>

        <?php if ($mensaje !== ''): ?>
            <div class="alert-ok"><i class="bi bi-check-circle-fill"></i><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?php campo_csrf(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">DNI</label>
                    <input type="number" name="dni" class="form-control" placeholder="Ej: 44123456" required min="1000000" max="99999999">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="tu@mail.com" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellido</label>
                    <input type="text" name="apellido" class="form-control" placeholder="Apellido" required maxlength="40">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Nombre" required maxlength="30">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="user" class="form-control" placeholder="Nombre de usuario" required minlength="3" maxlength="30">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contraseña <span class="texto-opcional">(mín. 6 caracteres)</span></label>
                    <input type="password" name="pass" class="form-control" placeholder="••••••••" required minlength="6">
                </div>

                <div class="col-12" style="margin-top:.5rem">
                    <div style="height:1px;background:var(--divider);margin-bottom:.75rem"></div>
                    <p style="font-family:Outfit,sans-serif;font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--accent-light);margin-bottom:.75rem">
                        <i class="bi bi-shield-lock-fill me-1"></i>Perfil del usuario
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipo de persona</label>
                    <select name="tipo" class="form-select" required>
                        <option value="">Seleccioná un tipo</option>
                        <?php foreach ($tipos_personas as $tipo_persona): ?>
                            <option value="<?php echo (int)$tipo_persona['id_tipo_persona']; ?>">
                                <?php echo htmlspecialchars($tipo_persona['tipo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="texto-opcional">El rol técnico se sincroniza automáticamente según el tipo.</small>
                </div>

                <div class="col-12 mt-2">
                    <button type="submit" class="btn-plei-submit">
                        <i class="bi bi-person-plus-fill me-2"></i>Registrar usuario
                    </button>
                </div>
            </div>
        </form>

        <div class="text-center mt-3">
            <a href="<?php echo url('home.php'); ?>" class="btn-plei-cancel">Volver al inicio</a>
        </div>
    </div>
    <script src="../../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
