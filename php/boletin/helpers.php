<?php

function boletin_tabla_existe(mysqli $conexion, string $tabla): bool {
    $fila = db_fetch_one(
        $conexion,
        "SELECT COUNT(*) AS total
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = ?",
        's',
        [$tabla]
    );
    return ((int)($fila['total'] ?? 0)) > 0;
}

function boletin_modulo_disponible(mysqli $conexion): bool {
    return columna_bd_existe($conexion, 'ciclos_lectivos', 'id_ciclo')
        && columna_bd_existe($conexion, 'boletin_periodos', 'id_periodo')
        && columna_bd_existe($conexion, 'boletin_curso_periodo', 'id_boletin_curso_periodo')
        && columna_bd_existe($conexion, 'boletin_periodos', 'codigo_pdf')
        && columna_bd_existe($conexion, 'boletin_complementos_anuales', 'id_complemento')
        && columna_bd_existe($conexion, 'boletin_institucion_config', 'id_config');
}

function boletin_calcular_sigla(int $nota): string {
    if ($nota >= 1 && $nota <= 3) {
        return 'TED';
    }
    if ($nota >= 4 && $nota <= 6) {
        return 'TEP';
    }
    return 'TEA';
}

function boletin_ciclo_activo(mysqli $conexion): ?array {
    return db_fetch_one(
        $conexion,
        "SELECT id_ciclo, anio, nombre, estado, creado_en, cerrado_en
         FROM ciclos_lectivos
         WHERE estado = 'activo'
         ORDER BY id_ciclo DESC
         LIMIT 1"
    );
}

function boletin_periodos_por_ciclo(mysqli $conexion, int $id_ciclo, bool $solo_activos = false): array {
    if ($id_ciclo <= 0) {
        return [];
    }
    $where_activo = $solo_activos ? ' AND bp.activo = 1' : '';
    return db_fetch_all(
        $conexion,
        "SELECT bp.id_periodo, bp.id_ciclo, bp.nombre, bp.orden, bp.codigo_pdf, bp.activo,
                COALESCE(total_cp.total, 0) AS total_cursos_configurados
         FROM boletin_periodos AS bp
         LEFT JOIN (
             SELECT id_periodo, COUNT(*) AS total
             FROM boletin_curso_periodo
             GROUP BY id_periodo
         ) AS total_cp ON total_cp.id_periodo = bp.id_periodo
         WHERE bp.id_ciclo = ? $where_activo
         ORDER BY bp.orden ASC, bp.id_periodo ASC",
        'i',
        [$id_ciclo]
    );
}

function boletin_periodo_por_id(mysqli $conexion, int $id_periodo): ?array {
    if ($id_periodo <= 0) {
        return null;
    }
    return db_fetch_one(
        $conexion,
        "SELECT bp.id_periodo, bp.id_ciclo, bp.nombre, bp.orden, bp.activo,
                cl.anio, cl.nombre AS ciclo_nombre, cl.estado AS ciclo_estado
         FROM boletin_periodos AS bp
         INNER JOIN ciclos_lectivos AS cl ON cl.id_ciclo = bp.id_ciclo
         WHERE bp.id_periodo = ?
         LIMIT 1",
        'i',
        [$id_periodo]
    );
}

function boletin_config_institucion(mysqli $conexion): array {
    $fila = db_fetch_one(
        $conexion,
        "SELECT id_config, nombre_escuela, direccion, ciudad, codigo_postal, telefono
         FROM boletin_institucion_config
         ORDER BY id_config ASC
         LIMIT 1"
    );
    if ($fila) {
        return $fila;
    }
    return [
        'nombre_escuela' => 'Escuela de Educacion Secundaria Tecnica Nro 1 "Brig. Gral. Bartolome Mitre"',
        'direccion' => 'Av. de Mayo 1425',
        'ciudad' => 'Pergamino',
        'codigo_postal' => '2700',
        'telefono' => '02477-322031',
    ];
}

function boletin_codigos_pdf_validos(): array {
    return ['INF1', 'CUAT1', 'INF2', 'CUAT2'];
}

function boletin_periodos_mapeados_pdf_por_curso(mysqli $conexion, int $id_ciclo, int $id_curso): array {
    if ($id_ciclo <= 0 || $id_curso <= 0) {
        return [];
    }
    $filas = db_fetch_all(
        $conexion,
        "SELECT bp.id_periodo, bp.nombre, bp.codigo_pdf,
                cp.estado AS estado_curso
         FROM boletin_periodos AS bp
         LEFT JOIN boletin_curso_periodo AS cp
                ON cp.id_periodo = bp.id_periodo
               AND cp.id_curso = ?
         WHERE bp.id_ciclo = ?
           AND bp.codigo_pdf IS NOT NULL
         ORDER BY bp.orden ASC, bp.id_periodo ASC",
        'ii',
        [$id_curso, $id_ciclo]
    );
    $mapeo = [];
    foreach ($filas as $fila) {
        $codigo = strtoupper(trim((string)($fila['codigo_pdf'] ?? '')));
        if (!in_array($codigo, boletin_codigos_pdf_validos(), true)) {
            continue;
        }
        $mapeo[$codigo] = $fila;
    }
    return $mapeo;
}

function boletin_formatear_nota_sigla(?int $nota, ?string $sigla): string {
    if ($nota === null || $nota <= 0 || $sigla === null || trim($sigla) === '') {
        return '';
    }
    return $nota . ' ' . strtoupper(trim($sigla));
}

function boletin_descripcion_curso(mysqli $conexion, int $id_curso): ?array {
    if ($id_curso <= 0) {
        return null;
    }
    return db_fetch_one(
        $conexion,
        "SELECT c.id_curso, c.grado, s.seccion, mo.moda
         FROM cursos AS c
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         WHERE c.id_curso = ?
         LIMIT 1",
        'i',
        [$id_curso]
    );
}

function boletin_nombre_curso_corto(array $curso): string {
    return (string)($curso['grado'] . '° ' . $curso['seccion'] . ' (' . $curso['moda'] . ')');
}

function boletin_obtener_curso_periodo(mysqli $conexion, int $id_curso, int $id_periodo): ?array {
    if ($id_curso <= 0 || $id_periodo <= 0) {
        return null;
    }
    return db_fetch_one(
        $conexion,
        "SELECT id_boletin_curso_periodo, id_curso, id_periodo, estado,
                abierto_por, abierto_en, publicado_por, publicado_en,
                reabierto_por, reabierto_en, version_publicada
         FROM boletin_curso_periodo
         WHERE id_curso = ? AND id_periodo = ?
         LIMIT 1",
        'ii',
        [$id_curso, $id_periodo]
    );
}

function boletin_obtener_curso_periodo_for_update(mysqli $conexion, int $id_curso, int $id_periodo): ?array {
    if ($id_curso <= 0 || $id_periodo <= 0) {
        return null;
    }
    boletin_asegurar_curso_periodo($conexion, $id_curso, $id_periodo);
    return db_fetch_one(
        $conexion,
        "SELECT id_boletin_curso_periodo, id_curso, id_periodo, estado,
                abierto_por, abierto_en, publicado_por, publicado_en,
                reabierto_por, reabierto_en, version_publicada
         FROM boletin_curso_periodo
         WHERE id_curso = ? AND id_periodo = ?
         LIMIT 1
         FOR UPDATE",
        'ii',
        [$id_curso, $id_periodo]
    );
}

function boletin_asegurar_curso_periodo(mysqli $conexion, int $id_curso, int $id_periodo): ?array {
    $actual = boletin_obtener_curso_periodo($conexion, $id_curso, $id_periodo);
    if ($actual) {
        return $actual;
    }

    $stmt = mysqli_prepare(
        $conexion,
        "INSERT INTO boletin_curso_periodo (id_curso, id_periodo, estado)
         VALUES (?, ?, 'cerrado')"
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id_curso, $id_periodo);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        return boletin_obtener_curso_periodo($conexion, $id_curso, $id_periodo);
    }
    return boletin_obtener_curso_periodo($conexion, $id_curso, $id_periodo);
}

function boletin_cambiar_estado_curso_periodo(mysqli $conexion, int $id_curso, int $id_periodo, string $estado, int $id_usuario): bool {
    $estado = strtolower(trim($estado));
    if (!in_array($estado, ['cerrado', 'carga_docente', 'publicado'], true)) {
        return false;
    }

    $actual = boletin_asegurar_curso_periodo($conexion, $id_curso, $id_periodo);
    if (!$actual) {
        return false;
    }

    if ($estado === 'carga_docente') {
        if ((string)$actual['estado'] === 'publicado') {
            $sql = "UPDATE boletin_curso_periodo
                    SET estado = 'carga_docente',
                        reabierto_por = ?,
                        reabierto_en = NOW()
                    WHERE id_curso = ? AND id_periodo = ?";
        } else {
            $sql = "UPDATE boletin_curso_periodo
                    SET estado = 'carga_docente',
                        abierto_por = COALESCE(abierto_por, ?),
                        abierto_en = COALESCE(abierto_en, NOW())
                    WHERE id_curso = ? AND id_periodo = ?";
        }
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'iii', $id_usuario, $id_curso, $id_periodo);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool)$ok;
    }

    if ($estado === 'publicado') {
        $sql = "UPDATE boletin_curso_periodo
                SET estado = 'publicado',
                    publicado_por = ?,
                    publicado_en = NOW(),
                    version_publicada = version_publicada + 1
                WHERE id_curso = ? AND id_periodo = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'iii', $id_usuario, $id_curso, $id_periodo);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool)$ok;
    }

    $stmt = mysqli_prepare(
        $conexion,
        "UPDATE boletin_curso_periodo
         SET estado = 'cerrado'
         WHERE id_curso = ? AND id_periodo = ?"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id_curso, $id_periodo);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (bool)$ok;
}

function boletin_materias_por_curso(mysqli $conexion, int $id_curso): array {
    if ($id_curso <= 0) {
        return [];
    }
    return db_fetch_all(
        $conexion,
        "SELECT m.id_materia, m.nombre_materia, m.turno, m.grupo, m.id_curso,
                COALESCE(mg.cant_grupos, 0) AS cant_grupos,
                COALESCE(mg.grupos_txt, CAST(m.grupo AS CHAR)) AS grupos_txt,
                COALESCE(dm.total_docentes, 0) AS total_docentes
         FROM materias AS m
         LEFT JOIN (
            SELECT id_materia, COUNT(*) AS cant_grupos, GROUP_CONCAT(id_grupo ORDER BY id_grupo SEPARATOR ',') AS grupos_txt
            FROM materias_x_grupo
            GROUP BY id_materia
         ) AS mg ON mg.id_materia = m.id_materia
         LEFT JOIN (
            SELECT dm.id_materia, COUNT(DISTINCT dm.id_persona) AS total_docentes
            FROM docentes_x_materia AS dm
            INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = dm.id_persona
            INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
            WHERE LOWER(tp.tipo) = 'docente'
            GROUP BY dm.id_materia
         ) AS dm ON dm.id_materia = m.id_materia
         WHERE m.id_curso = ?
         ORDER BY m.nombre_materia ASC",
        'i',
        [$id_curso]
    );
}

function boletin_total_alumnos_curso(mysqli $conexion, int $id_curso): int {
    if ($id_curso <= 0) {
        return 0;
    }
    $filtro_activo = condicion_persona_activa($conexion, 'p');
    $fila = db_fetch_one(
        $conexion,
        "SELECT COUNT(*) AS total
         FROM alumnos_x_curso AS axc
         INNER JOIN personas AS p ON p.id_persona = axc.id_persona
         INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
         INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
         WHERE axc.id_curso = ?
           AND LOWER(tp.tipo) = 'alumno'
           $filtro_activo",
        'i',
        [$id_curso]
    );
    return (int)($fila['total'] ?? 0);
}

function boletin_validar_materias_con_grupos(mysqli $conexion, int $id_curso): array {
    $materias = boletin_materias_por_curso($conexion, $id_curso);
    $problemas = [];
    $total_curso = boletin_total_alumnos_curso($conexion, $id_curso);

    foreach ($materias as $materia) {
        $id_materia = (int)$materia['id_materia'];
        $cant_grupos = (int)$materia['cant_grupos'];
        if ($cant_grupos <= 1) {
            continue;
        }
        $filtro_activo = condicion_persona_activa($conexion, 'p');

        $fila_asignados = db_fetch_one(
            $conexion,
            "SELECT COUNT(DISTINCT axm.id_persona) AS total
             FROM alumnos_x_materia AS axm
             INNER JOIN personas AS p ON p.id_persona = axm.id_persona
             INNER JOIN materias_x_grupo AS mxg
                     ON mxg.id_materia = axm.id_materia
                    AND mxg.id_grupo = axm.id_grupo
             INNER JOIN alumnos_x_curso AS axc
                     ON axc.id_persona = axm.id_persona
                    AND axc.id_curso = ?
             WHERE axm.id_materia = ?
               AND axm.id_grupo IS NOT NULL
               $filtro_activo",
            'ii',
            [$id_curso, $id_materia]
        );
        $asignados = (int)($fila_asignados['total'] ?? 0);
        if ($asignados < $total_curso) {
            $problemas[] = [
                'id_materia' => $id_materia,
                'nombre_materia' => (string)$materia['nombre_materia'],
                'grupos_txt' => (string)$materia['grupos_txt'],
                'alumnos_curso' => $total_curso,
                'asignados' => $asignados,
            ];
        }
    }

    return $problemas;
}

function boletin_docente_grupo_en_materia(mysqli $conexion, int $id_docente, int $id_materia): ?int {
    if ($id_docente <= 0 || $id_materia <= 0) {
        return null;
    }
    $fila = db_fetch_one(
        $conexion,
        "SELECT id_grupo
         FROM docentes_x_materia
         WHERE id_persona = ? AND id_materia = ?
         LIMIT 1",
        'ii',
        [$id_docente, $id_materia]
    );
    if (!$fila) {
        return null;
    }
    $id_grupo = (int)($fila['id_grupo'] ?? 0);
    return $id_grupo > 0 ? $id_grupo : null;
}

function boletin_alumnos_esperados_materia_docente(mysqli $conexion, int $id_curso, int $id_materia, int $id_docente): array {
    if ($id_curso <= 0 || $id_materia <= 0 || $id_docente <= 0) {
        return [];
    }

    $grupos = grupos_de_materia($conexion, $id_materia);
    $filtro_activo = condicion_persona_activa($conexion, 'p');
    if (count($grupos) > 1) {
        $id_grupo_docente = boletin_docente_grupo_en_materia($conexion, $id_docente, $id_materia);
        if ($id_grupo_docente === null) {
            return [];
        }
        return db_fetch_all(
            $conexion,
            "SELECT DISTINCT p.id_persona, p.apellido, p.nombre, p.dni, p.mail, axm.id_grupo
             FROM alumnos_x_materia AS axm
             INNER JOIN alumnos_x_curso AS axc
                     ON axc.id_persona = axm.id_persona
                    AND axc.id_curso = ?
             INNER JOIN personas AS p ON p.id_persona = axm.id_persona
             INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
             INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
             WHERE axm.id_materia = ?
               AND axm.id_grupo = ?
               AND LOWER(tp.tipo) = 'alumno'
               $filtro_activo
             ORDER BY p.apellido ASC, p.nombre ASC",
            'iii',
            [$id_curso, $id_materia, $id_grupo_docente]
        );
    }

    $filas_explicitas = db_fetch_all(
        $conexion,
        "SELECT DISTINCT p.id_persona, p.apellido, p.nombre, p.dni, p.mail, axm.id_grupo
         FROM alumnos_x_materia AS axm
         INNER JOIN alumnos_x_curso AS axc
                 ON axc.id_persona = axm.id_persona
                AND axc.id_curso = ?
         INNER JOIN personas AS p ON p.id_persona = axm.id_persona
         INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
         INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
         WHERE axm.id_materia = ?
           AND LOWER(tp.tipo) = 'alumno'
           $filtro_activo
         ORDER BY p.apellido ASC, p.nombre ASC",
        'ii',
        [$id_curso, $id_materia]
    );

    if ($filas_explicitas !== []) {
        return $filas_explicitas;
    }

    return db_fetch_all(
        $conexion,
        "SELECT DISTINCT p.id_persona, p.apellido, p.nombre, p.dni, p.mail, NULL AS id_grupo
         FROM alumnos_x_curso AS axc
         INNER JOIN personas AS p ON p.id_persona = axc.id_persona
         INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
         INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
         WHERE axc.id_curso = ?
           AND LOWER(tp.tipo) = 'alumno'
           $filtro_activo
         ORDER BY p.apellido ASC, p.nombre ASC",
        'i',
        [$id_curso]
    );
}

function boletin_total_esperado_por_materia(mysqli $conexion, int $id_curso, int $id_materia): int {
    if ($id_curso <= 0 || $id_materia <= 0) {
        return 0;
    }

    $filtro_activo = condicion_persona_activa($conexion, 'p');
    $grupos = grupos_de_materia($conexion, $id_materia);
    if (count($grupos) > 1) {
        $fila = db_fetch_one(
            $conexion,
            "SELECT COUNT(DISTINCT axm.id_persona) AS total
             FROM alumnos_x_materia AS axm
             INNER JOIN personas AS p ON p.id_persona = axm.id_persona
             INNER JOIN materias_x_grupo AS mxg
                     ON mxg.id_materia = axm.id_materia
                    AND mxg.id_grupo = axm.id_grupo
             INNER JOIN alumnos_x_curso AS axc
                     ON axc.id_persona = axm.id_persona
                    AND axc.id_curso = ?
             WHERE axm.id_materia = ?
               AND axm.id_grupo IS NOT NULL
               $filtro_activo",
            'ii',
            [$id_curso, $id_materia]
        );
        return (int)($fila['total'] ?? 0);
    }

    $fila_expl = db_fetch_one(
        $conexion,
        "SELECT COUNT(DISTINCT axm.id_persona) AS total
         FROM alumnos_x_materia AS axm
         INNER JOIN personas AS p ON p.id_persona = axm.id_persona
         INNER JOIN alumnos_x_curso AS axc
                 ON axc.id_persona = axm.id_persona
                AND axc.id_curso = ?
         WHERE axm.id_materia = ?
           $filtro_activo",
        'ii',
        [$id_curso, $id_materia]
    );
    $total_expl = (int)($fila_expl['total'] ?? 0);
    if ($total_expl > 0) {
        return $total_expl;
    }
    return boletin_total_alumnos_curso($conexion, $id_curso);
}

function boletin_resumen_completitud_curso_periodo(mysqli $conexion, int $id_curso, int $id_periodo): array {
    $materias = boletin_materias_por_curso($conexion, $id_curso);
    $filas = [];
    $total_materias = 0;
    $completas = 0;
    $faltantes_total = 0;

    foreach ($materias as $materia) {
        $id_materia = (int)$materia['id_materia'];
        $esperado = boletin_total_esperado_por_materia($conexion, $id_curso, $id_materia);
        $fila_cargadas = db_fetch_one(
            $conexion,
            "SELECT COUNT(*) AS total
             FROM boletin_notas
             WHERE id_curso = ? AND id_periodo = ? AND id_materia = ?",
            'iii',
            [$id_curso, $id_periodo, $id_materia]
        );
        $cargadas = (int)($fila_cargadas['total'] ?? 0);
        $tiene_docente = ((int)$materia['total_docentes']) > 0;
        $faltantes = max(0, $esperado - $cargadas);
        $completa = $tiene_docente && $esperado > 0 && $faltantes === 0;

        $total_materias++;
        if ($completa) {
            $completas++;
        }
        $faltantes_total += $faltantes;

        $filas[] = [
            'id_materia' => $id_materia,
            'nombre_materia' => (string)$materia['nombre_materia'],
            'grupos_txt' => (string)$materia['grupos_txt'],
            'total_docentes' => (int)$materia['total_docentes'],
            'esperado' => $esperado,
            'cargadas' => $cargadas,
            'faltantes' => $faltantes,
            'completa' => $completa,
        ];
    }

    return [
        'materias' => $filas,
        'total_materias' => $total_materias,
        'materias_completas' => $completas,
        'faltantes_total' => $faltantes_total,
        'completo' => $total_materias > 0 && $completas === $total_materias && $faltantes_total === 0,
    ];
}

function boletin_formatear_decimal_1(?float $valor): string {
    if ($valor === null) {
        return '';
    }
    return number_format($valor, 1, ',', '');
}

function boletin_html_escape(string $texto): string {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function boletin_celda_numero_sigla(?int $nota, ?string $sigla): string {
    return boletin_formatear_nota_sigla($nota, $sigla);
}

function boletin_pdf_escape(string $texto): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $texto);
}

function boletin_pdf_latin1(string $texto): string {
    $texto = trim(preg_replace('/\s+/', ' ', $texto) ?? '');
    if ($texto === '') {
        return '';
    }
    $latin1 = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
    if ($latin1 === false) {
        $latin1 = preg_replace('/[^\x20-\x7E]/', '?', $texto) ?? $texto;
    }
    return (string)$latin1;
}

function boletin_generar_pdf_simple(array $lineas): string {
    if ($lineas === []) {
        $lineas = ['Boletin sin datos'];
    }

    $lineas_por_pagina = 46;
    $chunks = array_chunk($lineas, $lineas_por_pagina);

    $objetos = [];
    $page_obj_nums = [];
    $content_obj_nums = [];

    $next_obj = 4;
    foreach ($chunks as $_chunk) {
        $page_obj_nums[] = $next_obj++;
        $content_obj_nums[] = $next_obj++;
    }

    $objetos[1] = "<< /Type /Catalog /Pages 2 0 R >>";

    $kids = [];
    foreach ($page_obj_nums as $page_num) {
        $kids[] = $page_num . " 0 R";
    }
    $objetos[2] = "<< /Type /Pages /Count " . count($page_obj_nums) . " /Kids [" . implode(' ', $kids) . "] >>";
    $objetos[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

    foreach ($chunks as $idx => $chunk) {
        $page_num = $page_obj_nums[$idx];
        $content_num = $content_obj_nums[$idx];

        $y = 795;
        $stream = "BT\n/F1 11 Tf\n";
        $first = true;
        foreach ($chunk as $linea) {
            $texto = boletin_pdf_escape(boletin_pdf_latin1((string)$linea));
            if ($first) {
                $stream .= "50 {$y} Td\n({$texto}) Tj\n";
                $first = false;
            } else {
                $stream .= "0 -16 Td\n({$texto}) Tj\n";
            }
        }
        $stream .= "ET\n";
        $stream_bytes = strlen($stream);

        $objetos[$content_num] = "<< /Length {$stream_bytes} >>\nstream\n{$stream}endstream";
        $objetos[$page_num] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$content_num} 0 R >>";
    }

    ksort($objetos);
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objetos as $num => $contenido) {
        $offsets[$num] = strlen($pdf);
        $pdf .= $num . " 0 obj\n" . $contenido . "\nendobj\n";
    }

    $xref_pos = strlen($pdf);
    $max_obj = max(array_keys($objetos));
    $pdf .= "xref\n0 " . ($max_obj + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $max_obj; $i++) {
        $off = $offsets[$i] ?? 0;
        $pdf .= sprintf("%010d 00000 n \n", $off);
    }
    $pdf .= "trailer\n<< /Size " . ($max_obj + 1) . " /Root 1 0 R >>\nstartxref\n{$xref_pos}\n%%EOF";

    return $pdf;
}

function boletin_datos_pdf_alumno(mysqli $conexion, int $id_periodo, int $id_curso, int $id_alumno): ?array {
    $periodo_ref = boletin_periodo_por_id($conexion, $id_periodo);
    if (!$periodo_ref) {
        return null;
    }
    $id_ciclo = (int)($periodo_ref['id_ciclo'] ?? 0);
    if ($id_ciclo <= 0) {
        return null;
    }

    $cabecera = db_fetch_one(
        $conexion,
        "SELECT cl.id_ciclo, cl.anio, cl.nombre AS ciclo_nombre,
                c.id_curso, c.grado, s.seccion, mo.moda,
                p.id_persona AS id_alumno, p.apellido, p.nombre, p.dni
         FROM ciclos_lectivos AS cl
         INNER JOIN cursos AS c ON c.id_curso = ?
         INNER JOIN secciones AS s ON s.id_seccion = c.id_seccion
         INNER JOIN modalidad AS mo ON mo.id_modalidad = c.id_modalidad
         INNER JOIN personas AS p ON p.id_persona = ?
         WHERE cl.id_ciclo = ?
         LIMIT 1",
        'iii',
        [$id_curso, $id_alumno, $id_ciclo]
    );
    if (!$cabecera) {
        return null;
    }

    $mapeo_periodos = boletin_periodos_mapeados_pdf_por_curso($conexion, $id_ciclo, $id_curso);
    $periodos_publicados = [];
    foreach ($mapeo_periodos as $codigo => $meta) {
        if ((string)($meta['estado_curso'] ?? '') === 'publicado') {
            $periodos_publicados[$codigo] = (int)$meta['id_periodo'];
        }
    }

    $materias = db_fetch_all(
        $conexion,
        "SELECT id_materia, nombre_materia
         FROM materias
         WHERE id_curso = ?
         ORDER BY nombre_materia ASC",
        'i',
        [$id_curso]
    );

    $notas_por_periodo = [];
    if ($periodos_publicados !== []) {
        $ids_periodo = array_values(array_unique(array_filter(array_map('intval', $periodos_publicados), static fn($v) => $v > 0)));
        $placeholders = implode(',', array_fill(0, count($ids_periodo), '?'));
        $tipos = 'ii' . str_repeat('i', count($ids_periodo));
        $params = array_merge([$id_curso, $id_alumno], $ids_periodo);
        $filas_notas = db_fetch_all(
            $conexion,
            "SELECT id_materia, id_periodo, nota_num, sigla
             FROM boletin_notas
             WHERE id_curso = ?
               AND id_alumno = ?
               AND id_periodo IN ($placeholders)",
            $tipos,
            $params
        );
        foreach ($filas_notas as $fila) {
            $id_materia = (int)$fila['id_materia'];
            $id_periodo_nota = (int)$fila['id_periodo'];
            $notas_por_periodo[$id_materia][$id_periodo_nota] = [
                'nota_num' => (int)$fila['nota_num'],
                'sigla' => (string)$fila['sigla'],
            ];
        }
    }

    $complementos = db_fetch_all(
        $conexion,
        "SELECT id_materia, inas_1, inas_2, int_dic, int_feb_mar, nota_final
         FROM boletin_complementos_anuales
         WHERE id_ciclo = ?
           AND id_curso = ?
           AND id_alumno = ?",
        'iii',
        [$id_ciclo, $id_curso, $id_alumno]
    );
    $complementos_map = [];
    foreach ($complementos as $comp) {
        $complementos_map[(int)$comp['id_materia']] = $comp;
    }

    $filas_pdf = [];
    $suma_final = 0.0;
    $cant_final = 0;

    foreach ($materias as $materia) {
        $id_materia = (int)$materia['id_materia'];
        $comp = $complementos_map[$id_materia] ?? null;

        $nota_inf1 = null;
        $sigla_inf1 = null;
        if (isset($periodos_publicados['INF1'])) {
            $idp = (int)$periodos_publicados['INF1'];
            $nota_inf1 = $notas_por_periodo[$id_materia][$idp]['nota_num'] ?? null;
            $sigla_inf1 = $notas_por_periodo[$id_materia][$idp]['sigla'] ?? null;
        }

        $nota_cuat1 = null;
        $sigla_cuat1 = null;
        if (isset($periodos_publicados['CUAT1'])) {
            $idp = (int)$periodos_publicados['CUAT1'];
            $nota_cuat1 = $notas_por_periodo[$id_materia][$idp]['nota_num'] ?? null;
            $sigla_cuat1 = $notas_por_periodo[$id_materia][$idp]['sigla'] ?? null;
        }

        $nota_inf2 = null;
        $sigla_inf2 = null;
        if (isset($periodos_publicados['INF2'])) {
            $idp = (int)$periodos_publicados['INF2'];
            $nota_inf2 = $notas_por_periodo[$id_materia][$idp]['nota_num'] ?? null;
            $sigla_inf2 = $notas_por_periodo[$id_materia][$idp]['sigla'] ?? null;
        }

        $nota_cuat2 = null;
        $sigla_cuat2 = null;
        if (isset($periodos_publicados['CUAT2'])) {
            $idp = (int)$periodos_publicados['CUAT2'];
            $nota_cuat2 = $notas_por_periodo[$id_materia][$idp]['nota_num'] ?? null;
            $sigla_cuat2 = $notas_por_periodo[$id_materia][$idp]['sigla'] ?? null;
        }

        $nota_final = null;
        if ($comp && $comp['nota_final'] !== null && $comp['nota_final'] !== '') {
            $nota_final = (float)$comp['nota_final'];
            $suma_final += $nota_final;
            $cant_final++;
        }

        $filas_pdf[] = [
            'id_materia' => $id_materia,
            'nombre_materia' => (string)$materia['nombre_materia'],
            'inf1' => boletin_celda_numero_sigla($nota_inf1, $sigla_inf1),
            'cuat1' => boletin_celda_numero_sigla($nota_cuat1, $sigla_cuat1),
            'inas1' => $comp && $comp['inas_1'] !== null ? (string)(int)$comp['inas_1'] : '',
            'inf2' => boletin_celda_numero_sigla($nota_inf2, $sigla_inf2),
            'cuat2' => boletin_celda_numero_sigla($nota_cuat2, $sigla_cuat2),
            'inas2' => $comp && $comp['inas_2'] !== null ? (string)(int)$comp['inas_2'] : '',
            'int_dic' => $comp && $comp['int_dic'] !== null ? (string)(int)$comp['int_dic'] : '',
            'int_feb_mar' => $comp && $comp['int_feb_mar'] !== null ? (string)(int)$comp['int_feb_mar'] : '',
            'nota_final' => $nota_final,
        ];
    }

    $promedio_final = $cant_final > 0 ? round($suma_final / $cant_final, 1) : null;

    return [
        'cabecera' => $cabecera,
        'institucion' => boletin_config_institucion($conexion),
        'materias' => $filas_pdf,
        'promedio_final' => $promedio_final,
        'periodo_referencia' => [
            'id_periodo' => (int)$periodo_ref['id_periodo'],
            'nombre' => (string)($periodo_ref['nombre'] ?? ''),
            'orden' => (int)($periodo_ref['orden'] ?? 0),
        ],
    ];
}

function boletin_render_html_anual(array $datos): string {
    $cab = $datos['cabecera'];
    $inst = $datos['institucion'];
    $materias = $datos['materias'];
    $promedio_txt = boletin_formatear_decimal_1($datos['promedio_final']);

    $filas_html = '';
    foreach ($materias as $fila) {
        $nota_final_txt = $fila['nota_final'] === null ? '' : boletin_formatear_decimal_1((float)$fila['nota_final']);
        $filas_html .= '<tr>'
            . '<td class="materia">' . boletin_html_escape((string)$fila['nombre_materia']) . '</td>'
            . '<td>' . boletin_html_escape((string)$fila['inf1']) . '</td>'
            . '<td>' . boletin_html_escape((string)$fila['cuat1']) . '</td>'
            . '<td>' . boletin_html_escape((string)$fila['inas1']) . '</td>'
            . '<td>' . boletin_html_escape((string)$fila['inf2']) . '</td>'
            . '<td>' . boletin_html_escape((string)$fila['cuat2']) . '</td>'
            . '<td>' . boletin_html_escape((string)$fila['inas2']) . '</td>'
            . '<td>' . boletin_html_escape((string)$fila['int_dic']) . '</td>'
            . '<td>' . boletin_html_escape((string)$fila['int_feb_mar']) . '</td>'
            . '<td class="final">' . boletin_html_escape($nota_final_txt) . '</td>'
            . '</tr>';
    }

    $curso_txt = $cab['grado'] . '° ' . $cab['moda'];
    $division_txt = (string)$cab['seccion'];
    $alumno_txt = $cab['apellido'] . ', ' . $cab['nombre'];
    $linea_escuela = trim((string)$inst['direccion']) . ' - Tel ' . trim((string)$inst['telefono']);
    $linea_ciudad = '(' . trim((string)$inst['codigo_postal']) . ') ' . strtoupper(trim((string)$inst['ciudad']));
    $periodo_txt = trim((string)($datos['periodo_referencia']['nombre'] ?? ''));

    return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 16mm 10mm 12mm 10mm; }
    body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 11px; }
    .header-top { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .header-top td { vertical-align: top; }
    .logo-box { width: 90px; height: 72px; border: 1px solid #666; text-align: center; font-size: 9px; color: #444; }
    .escuela { text-align: center; font-size: 12px; line-height: 1.35; }
    .escuela .nombre { font-weight: 700; font-size: 14px; }
    .meta { border: 1px solid #333; border-bottom: 0; padding: 6px 8px; font-size: 11px; }
    .meta table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 1px 3px; }
    .boletin { width: 100%; border-collapse: collapse; table-layout: fixed; border: 1px solid #333; }
    .boletin th, .boletin td { border: 1px solid #333; padding: 4px 3px; text-align: center; vertical-align: middle; font-size: 10px; }
    .boletin thead th { font-size: 9px; text-transform: uppercase; background: #f5f5f5; }
    .boletin .materia { text-align: left; padding-left: 6px; width: 35%; }
    .boletin .final { font-weight: 700; }
    .firma { text-align: left; font-size: 10px; padding: 7px 6px !important; }
    .boletin td.firma { text-align: left !important; padding-left: 6px !important; }
    .promedio { font-weight: 700; background: #f5f5f5; }
    .nota-pie { margin-top: 8px; font-size: 9px; line-height: 1.35; }
    .tiny { font-size: 8px; color: #444; }
</style>
</head>
<body>
    <table class="header-top">
        <tr>
            <td style="width:100px">
                <div class="logo-box">LOGO / SELLO<br>pendiente</div>
            </td>
            <td class="escuela">
                <div class="nombre">' . boletin_html_escape((string)$inst['nombre_escuela']) . '</div>
                <div>' . boletin_html_escape($linea_escuela) . '</div>
                <div><strong>' . boletin_html_escape($linea_ciudad) . '</strong></div>
                <div class="tiny">Corte publicado: ' . boletin_html_escape($periodo_txt) . '</div>
            </td>
        </tr>
    </table>

    <div class="meta">
        <table>
            <tr>
                <td style="width:60%"><strong>NOMBRE Y APELLIDO DEL ALUMNO:</strong> ' . boletin_html_escape($alumno_txt) . '</td>
                <td style="width:20%"><strong>CURSO:</strong> ' . boletin_html_escape($curso_txt) . '</td>
                <td style="width:20%"><strong>DIVISION:</strong> ' . boletin_html_escape($division_txt) . '</td>
            </tr>
        </table>
    </div>

    <table class="boletin">
        <thead>
            <tr>
                <th class="materia">' . boletin_html_escape($curso_txt) . '</th>
                <th>1° INFORME</th>
                <th>1° CUATRIM</th>
                <th>INAS</th>
                <th>2° INFORME</th>
                <th>2° CUATRIM</th>
                <th>INAS</th>
                <th>INT. DICIEM</th>
                <th>INT. FEB/MAR</th>
                <th>NOTA FINAL</th>
            </tr>
        </thead>
        <tbody>
            ' . $filas_html . '
            <tr>
                <td class="firma" colspan="9" style="text-align:left"><strong>Firma Padre / Madre / Tutor</strong></td>
                <td class="promedio">' . boletin_html_escape($promedio_txt) . '</td>
            </tr>
        </tbody>
    </table>

    <div class="nota-pie">
        1. La valoraci&oacute;n preliminar refiere a una valoraci&oacute;n cuantitativa y cualitativa. La misma se expresa en n&uacute;meros enteros, en escala de 1 (uno) a 10 (diez), y dentro de las categor&iacute;as TEA-TEP-TED.<br>
        2. La cualificaci&oacute;n del cuatrimestre resulta de la ponderaci&oacute;n de las valoraciones parciales cualitativas y cuantitativas obtenidas por la/el estudiante.<br>
        3. Intensificaci&oacute;n: los saberes pendientes se logran aprobar con una calificaci&oacute;n de 7 (siete) o m&aacute;s.
    </div>
</body>
</html>';
}

function boletin_generar_pdf_desde_html(string $html): ?string {
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!class_exists('\\Dompdf\\Dompdf')) {
        if (!is_file($autoload)) {
            return null;
        }
        require_once $autoload;
    }
    if (!class_exists('\\Dompdf\\Dompdf') || !class_exists('\\Dompdf\\Options')) {
        return null;
    }

    try {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    } catch (Throwable $e) {
        return null;
    }
}

function boletin_ruta_pdf_relativa(array $cabecera, int $id_periodo, int $id_curso, int $id_alumno, int $version): string {
    $anio = (int)($cabecera['anio'] ?? 0);
    $anio = $anio > 0 ? $anio : (int)date('Y');
    return 'boletines_archivo/ciclo_' . $anio
        . '/curso_' . $id_curso
        . '/anual/alumno_' . $id_alumno . '_periodo_' . $id_periodo . '_v' . $version . '.pdf';
}

function boletin_generar_pdf_alumno(mysqli $conexion, int $id_periodo, int $id_curso, int $id_alumno, int $version, int $id_generador): ?array {
    $datos = boletin_datos_pdf_alumno($conexion, $id_periodo, $id_curso, $id_alumno);
    if (!$datos) {
        return null;
    }

    $cab = $datos['cabecera'];
    $html = boletin_render_html_anual($datos);
    $pdf = boletin_generar_pdf_desde_html($html);
    if ($pdf === null) {
        $lineas = [
            'PLEI - Boletin Anual',
            'Ciclo: ' . $cab['ciclo_nombre'] . ' (' . $cab['anio'] . ')',
            'Curso: ' . $cab['grado'] . '° ' . $cab['seccion'] . ' (' . $cab['moda'] . ')',
            'Alumno: ' . $cab['apellido'] . ', ' . $cab['nombre'] . ' - DNI ' . $cab['dni'],
            'Render HTML->PDF no disponible, se emite formato simplificado.',
        ];
        foreach ($datos['materias'] as $fila) {
            $lineas[] = (string)$fila['nombre_materia'] . ' | NF: ' . boletin_formatear_decimal_1($fila['nota_final']);
        }
        $pdf = boletin_generar_pdf_simple($lineas);
    }

    $ruta_rel = boletin_ruta_pdf_relativa($cab, $id_periodo, $id_curso, $id_alumno, $version);
    $ruta_abs = dirname(__DIR__, 2) . '/' . $ruta_rel;
    $directorio = dirname($ruta_abs);
    if (!is_dir($directorio) && !@mkdir($directorio, 0775, true) && !is_dir($directorio)) {
        return null;
    }
    if (@file_put_contents($ruta_abs, $pdf) === false) {
        return null;
    }
    $hash = hash('sha256', $pdf);

    $stmt = mysqli_prepare(
        $conexion,
        "INSERT INTO boletin_pdf_historial
            (id_periodo, id_curso, id_alumno, version, ruta_pdf, hash_sha256, generado_por)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            ruta_pdf = VALUES(ruta_pdf),
            hash_sha256 = VALUES(hash_sha256),
            generado_por = VALUES(generado_por),
            generado_en = CURRENT_TIMESTAMP"
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param(
        $stmt,
        'iiiissi',
        $id_periodo,
        $id_curso,
        $id_alumno,
        $version,
        $ruta_rel,
        $hash,
        $id_generador
    );
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        return null;
    }

    return [
        'ruta_rel' => $ruta_rel,
        'ruta_abs' => $ruta_abs,
        'hash' => $hash,
    ];
}

function boletin_generar_pdfs_publicacion(mysqli $conexion, int $id_periodo, int $id_curso, int $version, int $id_generador): array {
    $filtro_activo = condicion_persona_activa($conexion, 'p');
    $alumnos = db_fetch_all(
        $conexion,
        "SELECT p.id_persona
         FROM alumnos_x_curso AS axc
         INNER JOIN personas AS p ON p.id_persona = axc.id_persona
         INNER JOIN tipo_persona_x_persona AS tpp ON tpp.id_persona = p.id_persona
         INNER JOIN tipos_personas AS tp ON tp.id_tipo_persona = tpp.id_tipo_persona
         WHERE axc.id_curso = ?
           AND LOWER(tp.tipo) = 'alumno'
           $filtro_activo
         ORDER BY p.apellido ASC, p.nombre ASC",
        'i',
        [$id_curso]
    );

    $errores = [];
    $generados = 0;
    foreach ($alumnos as $a) {
        $id_alumno = (int)$a['id_persona'];
        $ok = boletin_generar_pdf_alumno($conexion, $id_periodo, $id_curso, $id_alumno, $version, $id_generador);
        if ($ok) {
            $generados++;
        } else {
            $errores[] = $id_alumno;
        }
    }

    return [
        'generados' => $generados,
        'errores' => $errores,
    ];
}

function boletin_ultimo_pdf_alumno(mysqli $conexion, int $id_periodo, int $id_curso, int $id_alumno): ?array {
    return db_fetch_one(
        $conexion,
        "SELECT id_boletin_pdf, id_periodo, id_curso, id_alumno, version, ruta_pdf, hash_sha256, generado_en
         FROM boletin_pdf_historial
         WHERE id_periodo = ? AND id_curso = ? AND id_alumno = ?
         ORDER BY version DESC
         LIMIT 1",
        'iii',
        [$id_periodo, $id_curso, $id_alumno]
    );
}

function boletin_usuario_puede_descargar(
    mysqli $conexion,
    int $id_usuario,
    array $tipos_usuario,
    int $id_curso,
    int $id_alumno
): bool {
    if (in_array('administrador', $tipos_usuario, true)) {
        return true;
    }
    if (in_array('alumno', $tipos_usuario, true) && $id_usuario === $id_alumno) {
        return true;
    }
    if (in_array('preceptor', $tipos_usuario, true)) {
        $fila = db_fetch_one(
            $conexion,
            "SELECT 1
             FROM preceptor_x_curso
             WHERE id_persona = ? AND id_curso = ?
             LIMIT 1",
            'ii',
            [$id_usuario, $id_curso]
        );
        if ($fila) {
            return true;
        }
    }
    if (in_array('docente', $tipos_usuario, true)) {
        $fila = db_fetch_one(
            $conexion,
            "SELECT 1
             FROM docentes_x_materia AS dm
             INNER JOIN materias AS m ON m.id_materia = dm.id_materia
             WHERE dm.id_persona = ? AND m.id_curso = ?
             LIMIT 1",
            'ii',
            [$id_usuario, $id_curso]
        );
        if ($fila) {
            return true;
        }
    }
    return false;
}
