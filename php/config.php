<?php
define('BASE_URL', '/Dinamica/practica');

function url_ruta(string $ruta = ''): string {
    return BASE_URL . ($ruta ? '/' . ltrim($ruta, '/') : '');
}

function url(string $path = ''): string {
    return url_ruta($path);
}

function redirigir(string $ruta): void {
    header('Location: ' . url_ruta($ruta));
    exit;
}

function redirect(string $path): void {
    redirigir($path);
}

function exigir_inicio_sesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['id_persona'])) {
        redirigir('index.php');
    }
    redirigir_si_requiere_cambio_password();
}

function require_login(): void {
    exigir_inicio_sesion();
}

function exigir_rol($roles_permitidos): void {
    exigir_inicio_sesion();
    global $con;

    if (is_string($roles_permitidos)) {
        $roles_permitidos = [$roles_permitidos];
    }

    $roles_normalizados = array_map(
        static fn($rol) => strtolower(trim((string)$rol)),
        (array)$roles_permitidos
    );

    $id_persona = (int)($_SESSION['id_persona'] ?? 0);
    if ($id_persona <= 0) {
        http_response_code(403);
        redirigir('index.php');
    }

    $consulta = mysqli_prepare(
        $con,
        "SELECT t.tipo
         FROM tipo_persona_x_persona AS tp
         INNER JOIN tipos_personas AS t ON t.id_tipo_persona = tp.id_tipo_persona
         WHERE tp.id_persona = ?"
    );

    if (!$consulta) {
        http_response_code(500);
        die('No se pudo validar el acceso.');
    }

    mysqli_stmt_bind_param($consulta, 'i', $id_persona);
    mysqli_stmt_execute($consulta);
    $resultado = mysqli_stmt_get_result($consulta);

    $tipos_usuario = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $tipos_usuario[] = strtolower(trim((string)$fila['tipo']));
    }
    mysqli_stmt_close($consulta);

    foreach ($roles_normalizados as $rol) {
        if (in_array($rol, $tipos_usuario, true)) {
            return;
        }
    }

    http_response_code(403);
    redirigir('home.php');
}

function require_role($roles_permitidos): void {
    exigir_rol($roles_permitidos);
}

function token_csrf(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function csrf_token(): string {
    return token_csrf();
}

function campo_csrf(): void {
    echo '<input type="hidden" name="csrf_token" value="' .
         htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8') .
         '">';
}

function csrf_field(): void {
    campo_csrf();
}

function verificar_csrf(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token_formulario = $_POST['csrf_token'] ?? '';
    $token_sesion = $_SESSION['csrf_token'] ?? '';

    if (
        empty($token_formulario) ||
        empty($token_sesion) ||
        !hash_equals((string)$token_sesion, (string)$token_formulario)
    ) {
        http_response_code(403);
        die('Solicitud invalida. Recarga la pagina e intenta de nuevo.');
    }
}

function csrf_verify(): void {
    verificar_csrf();
}

function verificar_origen_mismo_sitio(): bool {
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referencia = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    $urls = [];
    if (!empty($origen)) {
        $urls[] = $origen;
    }
    if (!empty($referencia)) {
        $urls[] = $referencia;
    }
    if (empty($urls)) {
        return false;
    }

    foreach ($urls as $url_actual) {
        $host_url = parse_url($url_actual, PHP_URL_HOST);
        if (!empty($host_url) && strcasecmp((string)$host_url, (string)$host) === 0) {
            return true;
        }
    }

    return false;
}

function verificar_csrf_o_origen(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die('Metodo no permitido.');
    }

    $token_formulario = $_POST['csrf_token'] ?? '';
    if (!empty($token_formulario)) {
        verificar_csrf();
        return;
    }

    if (!verificar_origen_mismo_sitio()) {
        http_response_code(403);
        die('Solicitud invalida por origen no permitido.');
    }
}

function db_query(mysqli $conexion, string $sql, string $tipos = '', array $parametros = []): mysqli_stmt|false {
    $sentencia = mysqli_prepare($conexion, $sql);
    if (!$sentencia) {
        return false;
    }
    if (!empty($parametros)) {
        mysqli_stmt_bind_param($sentencia, $tipos, ...$parametros);
    }
    mysqli_stmt_execute($sentencia);
    return $sentencia;
}

function db_fetch_all(mysqli $conexion, string $sql, string $tipos = '', array $parametros = []): array {
    $sentencia = db_query($conexion, $sql, $tipos, $parametros);
    if (!$sentencia) {
        return [];
    }
    $resultado = mysqli_stmt_get_result($sentencia);
    $filas = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $filas[] = $fila;
    }
    mysqli_stmt_close($sentencia);
    return $filas;
}

function db_fetch_one(mysqli $conexion, string $sql, string $tipos = '', array $parametros = []): ?array {
    $sentencia = db_query($conexion, $sql, $tipos, $parametros);
    if (!$sentencia) {
        return null;
    }
    $resultado = mysqli_stmt_get_result($sentencia);
    $fila = mysqli_fetch_assoc($resultado) ?: null;
    mysqli_stmt_close($sentencia);
    return $fila;
}

function obtener_tipos_usuario(mysqli $conexion, int $id_persona): array {
    if ($id_persona <= 0) {
        return [];
    }
    $filas = db_fetch_all(
        $conexion,
        "SELECT LOWER(TRIM(t.tipo)) AS tipo
         FROM tipo_persona_x_persona AS tp
         INNER JOIN tipos_personas AS t ON t.id_tipo_persona = tp.id_tipo_persona
         WHERE tp.id_persona = ?",
        'i',
        [$id_persona]
    );
    return array_map(static fn($fila) => (string)$fila['tipo'], $filas);
}

function usuario_tiene_tipo(mysqli $conexion, int $id_persona, string $tipo): bool {
    $tipos = obtener_tipos_usuario($conexion, $id_persona);
    return in_array(strtolower(trim($tipo)), $tipos, true);
}

function valor_like(string $texto): string {
    $texto = trim($texto);
    $texto = str_replace('\\', '\\\\', $texto);
    $texto = str_replace('%', '\\%', $texto);
    $texto = str_replace('_', '\\_', $texto);
    return '%' . $texto . '%';
}

function columna_bd_existe(mysqli $conexion, string $tabla, string $columna): bool {
    static $cache = [];
    $clave = $tabla . '.' . $columna;
    if (array_key_exists($clave, $cache)) {
        return (bool)$cache[$clave];
    }

    $fila = db_fetch_one(
        $conexion,
        "SELECT COUNT(*) AS total
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?",
        'ss',
        [$tabla, $columna]
    );

    $existe = ((int)($fila['total'] ?? 0)) > 0;
    $cache[$clave] = $existe;
    return $existe;
}

function es_hash_password(string $valor): bool {
    $info = password_get_info($valor);
    return (int)($info['algo'] ?? 0) !== 0;
}

function password_es_debil(string $password): bool {
    $valor = strtolower(trim($password));
    if ($valor === '') {
        return true;
    }

    $comunes = [
        '1234',
        '12345',
        '123456',
        '12345678',
        'password',
        'admin',
        'qwerty',
        'abc123',
        '111111',
        '000000',
    ];

    if (in_array($valor, $comunes, true)) {
        return true;
    }

    if (strlen($password) < 8) {
        return true;
    }

    if (preg_match('/^\d+$/', $password)) {
        return true;
    }

    return false;
}

function redirigir_si_requiere_cambio_password(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['forzar_cambio_password'])) {
        return;
    }

    $archivo_actual = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $permitidos = ['cambiar_password_obligatorio.php', 'cerrar_sesion.php', 'index.php'];
    if (in_array($archivo_actual, $permitidos, true)) {
        return;
    }

    redirigir('php/modificaciones/cambiar_password_obligatorio.php');
}

function persona_tiene_tipo(mysqli $conexion, int $id_persona, string $tipo): bool {
    if ($id_persona <= 0) {
        return false;
    }
    $fila = db_fetch_one(
        $conexion,
        "SELECT 1
         FROM tipo_persona_x_persona AS tp
         INNER JOIN tipos_personas AS t ON t.id_tipo_persona = tp.id_tipo_persona
         WHERE tp.id_persona = ? AND LOWER(TRIM(t.tipo)) = ?
         LIMIT 1",
        'is',
        [$id_persona, strtolower(trim($tipo))]
    );
    return (bool)$fila;
}

function persona_tipos_ids_validos(mysqli $conexion, int $id_persona): array {
    if ($id_persona <= 0) {
        return [];
    }
    $filas = db_fetch_all(
        $conexion,
        "SELECT id_tipo_persona
         FROM tipo_persona_x_persona
         WHERE id_persona = ?",
        'i',
        [$id_persona]
    );
    return array_map(static fn($fila) => (int)$fila['id_tipo_persona'], $filas);
}

function obtener_nombres_tipos_por_ids(mysqli $conexion, array $ids_tipos): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids_tipos), static fn($id) => $id > 0)));
    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $tipos_param = str_repeat('i', count($ids));
    $filas = db_fetch_all(
        $conexion,
        "SELECT LOWER(TRIM(tipo)) AS tipo
         FROM tipos_personas
         WHERE id_tipo_persona IN ($placeholders)",
        $tipos_param,
        $ids
    );

    $tipos = array_map(static fn($fila) => (string)$fila['tipo'], $filas);
    return array_values(array_unique($tipos));
}

function obtener_rol_id_por_nombre(mysqli $conexion, string $nombre_rol): ?int {
    $nombre = strtolower(trim($nombre_rol));
    if ($nombre === '') {
        return null;
    }

    $fila = db_fetch_one(
        $conexion,
        "SELECT id_rol
         FROM roles
         WHERE LOWER(TRIM(rol)) = ?
         LIMIT 1",
        's',
        [$nombre]
    );

    return $fila ? (int)$fila['id_rol'] : null;
}

function resolver_rol_id_desde_tipos(mysqli $conexion, array $ids_tipos): ?int {
    $tipos = obtener_nombres_tipos_por_ids($conexion, $ids_tipos);
    if (in_array('administrador', $tipos, true)) {
        return obtener_rol_id_por_nombre($conexion, 'administrador');
    }
    if (in_array('docente', $tipos, true)) {
        return obtener_rol_id_por_nombre($conexion, 'docente');
    }
    return obtener_rol_id_por_nombre($conexion, 'usuario');
}

function sincronizar_rol_persona_desde_tipos(mysqli $conexion, int $id_persona, array $ids_tipos): bool {
    if ($id_persona <= 0) {
        return false;
    }
    $id_rol = resolver_rol_id_desde_tipos($conexion, $ids_tipos);
    if ($id_rol === null || $id_rol <= 0) {
        return false;
    }

    $sentencia = mysqli_prepare(
        $conexion,
        "UPDATE personas
         SET id_rol = ?
         WHERE id_persona = ?"
    );
    if (!$sentencia) {
        return false;
    }
    mysqli_stmt_bind_param($sentencia, 'ii', $id_rol, $id_persona);
    $ok = mysqli_stmt_execute($sentencia);
    mysqli_stmt_close($sentencia);
    return (bool)$ok;
}

function cursos_a_cargo_preceptor(mysqli $conexion, int $id_persona): array {
    if ($id_persona <= 0) {
        return [];
    }
    $filas = db_fetch_all(
        $conexion,
        "SELECT id_curso
         FROM preceptor_x_curso
         WHERE id_persona = ?",
        'i',
        [$id_persona]
    );
    return array_values(array_unique(array_map(static fn($fila) => (int)$fila['id_curso'], $filas)));
}

function normalizar_letra_seccion(string $seccion): ?string {
    $texto = trim($seccion);
    if ($texto === '') {
        return null;
    }
    $texto = strtoupper($texto);
    if (preg_match('/[A-Z]/', $texto, $coincidencia) !== 1) {
        return null;
    }
    return $coincidencia[0];
}

function grupos_permitidos_por_seccion(string $seccion): array {
    $letra = normalizar_letra_seccion($seccion);
    if ($letra === null) {
        return [];
    }
    $posicion = ord($letra) - ord('A') + 1;
    if ($posicion <= 0) {
        return [];
    }
    $grupo_base = ($posicion * 2) - 1;
    return [$grupo_base, $grupo_base + 1];
}

function grupos_permitidos_por_curso(mysqli $conexion, int $id_curso): array {
    if ($id_curso <= 0) {
        return [];
    }
    $fila = db_fetch_one(
        $conexion,
        "SELECT s.seccion
         FROM cursos AS c
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         WHERE c.id_curso = ?
         LIMIT 1",
        "i",
        [$id_curso]
    );
    if (!$fila) {
        return [];
    }
    return grupos_permitidos_por_seccion((string)($fila['seccion'] ?? ''));
}

function grupos_de_materia(mysqli $conexion, int $id_materia): array {
    if ($id_materia <= 0) {
        return [];
    }
    $filas = db_fetch_all(
        $conexion,
        "SELECT id_grupo
         FROM materias_x_grupo
         WHERE id_materia = ?
         ORDER BY id_grupo ASC",
        "i",
        [$id_materia]
    );
    $grupos = array_map(static fn($fila) => (int)$fila['id_grupo'], $filas);
    $grupos = array_values(array_unique(array_filter($grupos, static fn($g) => $g > 0)));
    return $grupos;
}

function grupos_de_materia_texto(mysqli $conexion, int $id_materia): string {
    $grupos = grupos_de_materia($conexion, $id_materia);
    if ($grupos === []) {
        return '-';
    }
    return implode(',', $grupos);
}

function alumno_pertenece_a_curso(mysqli $conexion, int $id_alumno, int $id_curso): bool {
    if ($id_alumno <= 0 || $id_curso <= 0) {
        return false;
    }
    $fila = db_fetch_one(
        $conexion,
        "SELECT 1
         FROM alumnos_x_curso
         WHERE id_persona = ? AND id_curso = ?
         LIMIT 1",
        'ii',
        [$id_alumno, $id_curso]
    );
    return (bool)$fila;
}

function ids_tipo_persona_existentes(mysqli $conexion, array $ids_tipos): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids_tipos), static fn($id) => $id > 0)));
    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $tipos_param = str_repeat('i', count($ids));
    $filas = db_fetch_all(
        $conexion,
        "SELECT id_tipo_persona
         FROM tipos_personas
         WHERE id_tipo_persona IN ($placeholders)",
        $tipos_param,
        $ids
    );

    return array_map(static fn($fila) => (int)$fila['id_tipo_persona'], $filas);
}

function ruta_local_material_relativa_valida(string $ruta): bool {
    $ruta_normalizada = ltrim(trim($ruta), '/');
    if ($ruta_normalizada === '') {
        return false;
    }
    if (!str_starts_with($ruta_normalizada, 'materiales/')) {
        return false;
    }
    if (strpos($ruta_normalizada, '..') !== false || strpos($ruta_normalizada, '//') !== false || strpos($ruta_normalizada, "\0") !== false) {
        return false;
    }
    return true;
}

function mime_material_permitido(string $extension, string $mime_real): bool {
    $extension = strtolower(trim($extension));
    $mime_real = strtolower(trim($mime_real));

    $permitidos = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/octet-stream', 'application/cdfv2'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
            'application/octet-stream',
        ],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
    ];

    if (!isset($permitidos[$extension]) || $mime_real === '') {
        return false;
    }

    return in_array($mime_real, $permitidos[$extension], true);
}

function contar_referencias_material_url(mysqli $conexion, string $url, int $id_material_excluir = 0): int {
    $url = trim($url);
    if ($url === '') {
        return 0;
    }

    $fila = db_fetch_one(
        $conexion,
        "SELECT COUNT(*) AS total
         FROM materiales
         WHERE url = ?
           AND (? <= 0 OR id_material <> ?)",
        'sii',
        [$url, $id_material_excluir, $id_material_excluir]
    );

    return (int)($fila['total'] ?? 0);
}

function eliminar_archivo_material_local_si_huerfano(mysqli $conexion, string $url, int $id_material_excluir = 0): bool {
    $ruta_relativa = ltrim(trim($url), '/');
    if (!ruta_local_material_relativa_valida($ruta_relativa)) {
        return false;
    }
    if (contar_referencias_material_url($conexion, $ruta_relativa, $id_material_excluir) > 0) {
        return false;
    }

    $base_materiales = realpath(__DIR__ . '/../materiales');
    $ruta_candidata = __DIR__ . '/../' . $ruta_relativa;

    if ($base_materiales === false || !file_exists($ruta_candidata)) {
        return false;
    }

    $ruta_real = realpath($ruta_candidata);
    if ($ruta_real === false || !is_file($ruta_real)) {
        return false;
    }

    $prefijo_base = rtrim($base_materiales, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncmp($ruta_real, $prefijo_base, strlen($prefijo_base)) !== 0) {
        return false;
    }

    return @unlink($ruta_real);
}
