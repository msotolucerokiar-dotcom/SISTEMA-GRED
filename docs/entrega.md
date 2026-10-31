# Entrega y operación

## Requisitos

Docker Engine con Docker Compose. El archivo `.env` se crea a partir de `.env.example` y debe permanecer fuera de Git. El portal y PostgreSQL escuchan solo en `127.0.0.1` para desarrollo local.

## Arranque y verificación

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
docker compose up -d --build
docker compose ps
docker compose exec -T app composer test
docker compose exec -T app composer audit --locked --no-interaction
```

Abre <http://localhost:8080>. `app` y `db` deben figurar como `healthy`; `web` debe estar `Up`. Las migraciones se ejecutan al arrancar `app` y son idempotentes. Si es necesario, repítelas con `docker compose exec -T app composer migrate`.

## Cuentas

No se publica ninguna contraseña. Crea una cuenta Responsable CSI desde terminal interactiva:

```powershell
docker compose exec app php bin/user-create.php responsable@regionlambayeque.gob.pe "Nombre Responsable" csi_manager
```

Esta cuenta inicial permite crear trabajadores, técnicos, practicantes y otros responsables desde `/csi/users/new`, sin terminal. La contraseña inicial se entrega de forma privada y el usuario debe cambiarla en su primer acceso; el formulario no la muestra después de guardar. El responsable cambia roles y estado desde `/csi/users`. Los permisos se consultan de nuevo en cada petición; al desactivar una cuenta, la sesión deja de servir. Cambia las contraseñas de cualquier cuenta de prueba antes de entregar el sistema a usuarios reales. El archivo local `Documentacion.txt`, si se usa para apuntes, está ignorado por Git.

## Publicación

Este `docker-compose.yml` está preparado para acceso local. Para publicar el portal, configura un proxy HTTPS confiable frente a `127.0.0.1:8080`, establece `SESSION_SECURE=1`, usa una contraseña única para PostgreSQL y limita el acceso al servidor y a las copias de seguridad. No publiques el puerto 5432. Verifica el certificado y el inicio de sesión por HTTPS antes de dar acceso a usuarios.

## Datos y recuperación

Conserva copias del volumen PostgreSQL y del volumen `private_uploads`; ambos son necesarios para recuperar solicitudes y evidencias. Comprueba periódicamente que puedas restaurar ambos en un entorno aislado. `docker compose down` conserva los volúmenes; `docker compose down -v` los elimina, por lo que no se usa para detener una instalación con datos.

Antes de una actualización, haz una copia de la base y de los adjuntos. Después ejecuta `docker compose up -d --build`, comprueba `docker compose ps`, corre las pruebas y revisa `docker compose logs --tail=100 app web db`. La aplicación no envía correos ni integra un proveedor institucional de identidad; las cuentas se administran localmente.
