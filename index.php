<?php
include './php/config.php'; session_start(); if(isset($_SESSION['id_persona'])){ if(!empty($_SESSION['forzar_cambio_password'])){ redirigir('php/modificaciones/cambiar_password_obligatorio.php'); } redirigir('home.php'); } $errores = [ 'campos' => 'Completá email y contraseña.', 'credenciales' => 'No se pudo iniciar sesión con los datos ingresados.', 'bloqueado' => 'Por seguridad, el acceso fue bloqueado temporalmente. Esperá unos minutos e intentá de nuevo.', 'inactivo' => 'Tu cuenta está inactiva. Contactá a administración.', 'timeout' => 'Tu sesión expiró por inactividad. Iniciá sesión nuevamente.', 'sistema' => 'Error del sistema. Intentá de nuevo.', ]; $error_msg = isset($_GET['error']) ? ($errores[$_GET['error']] ?? '') : ''; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLEI — Iniciar Sesión</title>
    <link rel="stylesheet" href="./bootstrap-5.0.2-dist/css/bootstrap.css">
    <link rel="stylesheet" href="./plei.css">
    <style>
        .login-wrap {
            min-height:100vh; display:flex;
            align-items:center; justify-content:center; padding:2rem;
            position:relative;
        }

        .login-wrap::before {
            content:'';
            position:fixed; inset:0; z-index:0;
            background:
                radial-gradient(ellipse 55% 55% at 30% 30%, rgba(99,102,241,0.22) 0%, transparent 65%),
                radial-gradient(ellipse 50% 50% at 75% 70%, rgba(139,92,246,0.18) 0%, transparent 65%);
            animation: loginOrb 12s ease-in-out infinite alternate;
        }
        @keyframes loginOrb {
            0%   { opacity:0.8; transform:scale(1); }
            100% { opacity:1;   transform:scale(1.06); }
        }

        .login-card {
            position:relative; z-index:1;
            width:100%; max-width:420px;
            background:rgba(7,8,15,0.72);
            backdrop-filter:blur(40px) saturate(2);
            -webkit-backdrop-filter:blur(40px) saturate(2);
            border:1px solid rgba(255,255,255,0.14);
            border-radius:1.75rem;
            padding:2.75rem 2.5rem;
            box-shadow:0 24px 64px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.04) inset;
            animation:fadeInUp 0.55s cubic-bezier(0.175,0.885,0.32,1.275) both;
        }
        .login-card::before {
            content:''; position:absolute; top:0; left:10%; right:10%; height:1px;
            background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            border-radius:999px;
        }

        .input-wrap { position:relative; }
        .input-wrap i {
            position:absolute; left:0.95rem; top:50%;
            transform:translateY(-50%); color:rgba(240,240,250,0.3);
            font-size:0.95rem; pointer-events:none; z-index:1;
            transition:color 0.2s;
        }
        .input-wrap:focus-within i { color:var(--accent-light); }
        .input-wrap .form-control { padding-left:2.6rem !important; }

        .btn-login {
            background:linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
            color:white; border:none; border-radius:0.9rem;
            padding:0.82rem; font-family:'Outfit',sans-serif;
            font-weight:700; font-size:0.97rem; width:100%;
            cursor:pointer; margin-top:0.5rem;
            transition:all 0.25s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow:0 4px 20px rgba(99,102,241,0.35);
            display:flex; align-items:center; justify-content:center; gap:0.5rem;
            position:relative; overflow:hidden;
        }
        .btn-login::after {
            content:''; position:absolute; top:0; left:-100%;
            width:55%; height:100%;
            background:linear-gradient(90deg,transparent,rgba(255,255,255,0.18),transparent);
            transition:left 0.55s ease;
        }
        .btn-login:hover { transform:translateY(-2px); box-shadow:0 8px 30px rgba(99,102,241,0.5); color:white; }
        .btn-login:hover::after { left:150%; }
        .btn-login:active { transform:translateY(0); }
        .btn-login .spinner {
            display:none; width:18px; height:18px;
            border:2px solid rgba(255,255,255,0.35); border-top-color:white;
            border-radius:50%; animation:spin 0.7s linear infinite;
        }
        .btn-login.loading .spinner { display:block; }
        .btn-login.loading .btn-text { display:none; }

        .error-box {
            background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25);
            border-radius:0.75rem; padding:0.75rem 1rem;
            color:#fca5a5; font-size:0.86rem; font-weight:600;
            display:flex; align-items:center; gap:0.5rem; margin-bottom:1.25rem;
            animation:fadeInUp 0.3s ease both;
        }

        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <div class="logo-text">PLEI</div>
            <div class="logo-sub">Sistema de Gestión Escolar</div>
        </div>
        <div class="login-divider"></div>

        <?php if($error_msg): ?>
        <div class="error-box">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
        <?php endif; ?>

        <form action="./php/logicas/index.php" method="POST" id="loginForm" autocomplete="off">
            <?php campo_csrf(); ?>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope-fill"></i>
                    <input type="email" name="email" class="form-control"
                        placeholder="tu@mail.com"
                        value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>"
                        required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-wrap">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="pass" class="form-control"
                        placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn-login" id="btnLogin">
                <div class="spinner"></div>
                <span class="btn-text">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
                </span>
            </button>
        </form>

        <p class="login-footer-link" style="margin-top:1.5rem">
            ¿No tenés cuenta? Solicitá el alta a la administración escolar.
        </p>
    </div>
</div>
<script src="./bootstrap-5.0.2-dist/js/bootstrap.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', function(){
    document.getElementById('btnLogin').classList.add('loading');
});
</script>
</body>
</html>
