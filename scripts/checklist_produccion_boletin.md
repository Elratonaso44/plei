# Checklist de salida a producción (boletín)

## 1) Previo a ventana (staging)
- Cargar variables de entorno productivas (`APP_ENV=production`, DB no-root, `SESSION_COOKIE_SECURE=1`).
- Ejecutar migración:
  - `scripts/migracion_004_boletines.sql` (si aún no está aplicada)
  - `scripts/migracion_005_produccion.sql`
  - `scripts/migracion_006_boletin_anual_pdf.sql`
- Verificar que exista `vendor/` con Dompdf versionado en el deploy.
- Verificar login con usuario activo e inactivo.
- Verificar flujo completo: admin -> preceptor -> docente -> alumno.

## 2) Ventana de mantenimiento (15-30 min)
- Activar cartel de mantenimiento.
- Hacer backup manual completo:
  - dump DB (`plei_db`)
  - carpeta del proyecto
  - `boletines_archivo/`
- Deploy de código.
- Aplicar migración `scripts/migracion_005_produccion.sql`.
- Confirmar permisos de carpetas y `.htaccess` de `boletines_archivo/`.

## 3) Smoke test post deploy
- Login:
  - usuario activo: OK
  - usuario inactivo: bloqueado
  - intentos fallidos repetidos: bloqueo temporal
- Admin:
  - inactivar/reactivar persona
  - filtro “ver inactivos” en listados
- Preceptor:
  - abrir/publicar/reabrir período
  - bloqueo correcto en períodos inactivos
- Docente:
  - carga notas 1-10
  - refresh no duplica POST (PRG)
- Alumno:
  - ve y descarga solo boletines publicados

## 4) Riesgo aceptado
- No hay backup diario automatizado en esta fase.
- Mitigación actual: backup manual obligatorio antes de cada deploy.
