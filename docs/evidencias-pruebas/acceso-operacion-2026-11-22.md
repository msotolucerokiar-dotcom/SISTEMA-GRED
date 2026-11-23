# Verificación de acceso y operación

**Fecha:** 22 de noviembre de 2026 (America/Bogota). **Versión:** `2f5848e` (`main`). **Entorno:** Docker Compose local, PHP 8.3.36 y PHPUnit 11.5.57.

## Pruebas automatizadas

Se ejecutaron las pruebas unitarias de autenticación, administración y autorización, más las pruebas de integración HTTP de creación de usuarios, revocación de sesiones y permisos, y las de persistencia de administración y permisos.

**Resultado: 16 pruebas, 174 aserciones, 0 fallos** (`00:59.995`). Las pruebas comprobaron inicio de sesión con contraseña válida, cambio obligatorio de contraseña inicial, rechazo de cuentas inactivas, cierre de sesión con CSRF, invalidación de la sesión, efecto inmediato de cambios de rol, concesión y revocación de `request.assign`, límites administrativos y acceso denegado a usuarios sin permiso.

## Arranque y comprobaciones HTTP

- `docker compose config --quiet`: correcto.
- `docker compose ps`: `app` y `db` saludables; `web` en ejecución en `127.0.0.1:8080`. PostgreSQL está enlazado a `127.0.0.1:5432`.
- `GET /`: **200**. Sin sesión, `GET /csi/users`, `GET /technician/requests` y `GET /csi/reports.csv`: **303**, redirección al acceso.
- `GET /.env`: **403**. `GET /storage/private/example.pdf` y `GET /src/Infrastructure/Http/Session.php`: **404**. La configuración de Nginx sirve solo `public` y el almacenamiento privado queda fuera del directorio público.
- La respuesta incluye `X-Content-Type-Options: nosniff` y política CSP. La aplicación establece `Cache-Control: no-store, private` para sus respuestas; la sesión usa cookies `HttpOnly`, `SameSite=Lax`, modo estricto y atributo `Secure` bajo HTTPS o con `SESSION_SECURE=1`.

## Documentación de operación

[README.md](../../README.md) explica instalación, cuentas, pruebas y flujos principales. [docs/entrega.md](../entrega.md) documenta arranque, comprobación de servicios, migraciones, publicación con HTTPS, copias de seguridad y recuperación. No se detectaron defectos que requirieran cambios de aplicación.

Esta revisión cubre el entorno local y las rutas comprobadas; no equivale a una auditoría externa ni a una prueba de despliegue público con HTTPS.
