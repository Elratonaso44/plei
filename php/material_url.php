<?php
function material_base64url_encode(string $valor): string {
    return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
}

function material_base64url_decode(string $valor): ?string {
    if ($valor === '') {
        return null;
    }
    $normalizado = strtr($valor, '-_', '+/');
    $padding = strlen($normalizado) % 4;
    if ($padding > 0) {
        $normalizado .= str_repeat('=', 4 - $padding);
    }
    $decodificado = base64_decode($normalizado, true);
    if ($decodificado === false) {
        return null;
    }
    return $decodificado;
}

function extension_material_desde_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $ruta = (string)(parse_url($url, PHP_URL_PATH) ?? '');
    if ($ruta === '') {
        $ruta = $url;
    }
    return strtolower((string)pathinfo($ruta, PATHINFO_EXTENSION));
}

function material_local_es_previsualizable(string $url): bool {
    if (!es_ruta_local_material_valida($url)) {
        return false;
    }
    $extension = extension_material_desde_url($url);
    return in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true);
}

function material_token_clave_usuario(int $id_persona): string {
    if (function_exists('plei_iniciar_sesion')) {
        plei_iniciar_sesion();
    } elseif (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $seed = '';
    if (function_exists('token_csrf')) {
        $seed = token_csrf();
    }
    if ($seed === '') {
        $seed = session_id();
    }
    if ($seed === '') {
        $seed = 'plei-material-fallback-seed';
    }

    $base_url = defined('BASE_URL') ? (string)BASE_URL : '';
    $material_base = hash('sha256', 'plei-material|' . $base_url . '|' . $seed, true);
    return hash_hmac('sha256', (string)$id_persona, $material_base, true);
}

function material_token_generar(int $id_material, int $id_persona, string $accion = 'ver', int $ttl_segundos = 300): string {
    if ($id_material <= 0 || $id_persona <= 0) {
        return '';
    }
    $accion = strtolower(trim($accion)) === 'descargar' ? 'descargar' : 'ver';
    $ttl = max(30, min(1800, $ttl_segundos));

    $payload = [
        'v' => 1,
        'mid' => $id_material,
        'uid' => $id_persona,
        'act' => $accion,
        'exp' => time() + $ttl,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || $json === '') {
        return '';
    }

    $payload_b64 = material_base64url_encode($json);
    $firma = hash_hmac('sha256', $payload_b64, material_token_clave_usuario($id_persona), true);
    $firma_b64 = material_base64url_encode($firma);
    return $payload_b64 . '.' . $firma_b64;
}

function material_token_validar(string $token, int $id_persona): ?array {
    $token = trim($token);
    if ($token === '' || $id_persona <= 0) {
        return null;
    }

    $partes = explode('.', $token, 2);
    if (count($partes) !== 2) {
        return null;
    }
    [$payload_b64, $firma_b64] = $partes;
    if ($payload_b64 === '' || $firma_b64 === '') {
        return null;
    }

    $firma_esperada = material_base64url_encode(
        hash_hmac('sha256', $payload_b64, material_token_clave_usuario($id_persona), true)
    );
    if (!hash_equals($firma_esperada, $firma_b64)) {
        return null;
    }

    $json = material_base64url_decode($payload_b64);
    if ($json === null || $json === '') {
        return null;
    }
    $payload = json_decode($json, true);
    if (!is_array($payload)) {
        return null;
    }

    $version = (int)($payload['v'] ?? 0);
    $id_material = (int)($payload['mid'] ?? 0);
    $uid = (int)($payload['uid'] ?? 0);
    $accion = strtolower(trim((string)($payload['act'] ?? '')));
    $expira = (int)($payload['exp'] ?? 0);

    if ($version !== 1 || $id_material <= 0 || $uid <= 0 || $expira <= 0) {
        return null;
    }
    if ($uid !== $id_persona) {
        return null;
    }
    if (!in_array($accion, ['ver', 'descargar'], true)) {
        return null;
    }
    if ($expira < time()) {
        return null;
    }

    return [
        'id_material' => $id_material,
        'id_persona' => $uid,
        'accion' => $accion,
        'expira' => $expira,
    ];
}

function es_url_externa_material_valida(string $url): bool {
    $url = trim($url);
    if ($url === '') {
        return false;
    }

    $url_valida = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url_valida) {
        return false;
    }

    $esquema = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($esquema, ['http', 'https'], true);
}

function es_ruta_local_material_valida(string $url): bool {
    $ruta = ltrim(trim($url), '/');
    if ($ruta === '') {
        return false;
    }
    if (!str_starts_with($ruta, 'materiales/')) {
        return false;
    }
    if (strpos($ruta, '..') !== false || strpos($ruta, '//') !== false || strpos($ruta, "\0") !== false) {
        return false;
    }
    return true;
}

function material_url_es_valida(string $url): bool {
    return es_url_externa_material_valida($url) || es_ruta_local_material_valida($url);
}

function url_material(string $url, bool $descargar = false, int $id_material = 0): string {
    $url = trim($url);
    if ($url === '') {
        return '#';
    }

    if (es_url_externa_material_valida($url)) {
        return $url;
    }

    if (!es_ruta_local_material_valida($url)) {
        return '#';
    }

    $parametros = [];
    if ($id_material > 0) {
        $id_persona = (int)($_SESSION['id_persona'] ?? 0);
        if ($id_persona > 0) {
            $accion = $descargar ? 'descargar' : 'ver';
            $token = material_token_generar($id_material, $id_persona, $accion, 300);
            if ($token !== '') {
                return url('ver_material.php') . '?' . http_build_query(['t' => $token]);
            }
        }
        $parametros['id'] = $id_material;
    } else {
        $parametros['f'] = ltrim($url, '/');
        if ($descargar) {
            $parametros['descargar'] = '1';
        }
    }

    return url('ver_material.php') . '?' . http_build_query($parametros);
}
