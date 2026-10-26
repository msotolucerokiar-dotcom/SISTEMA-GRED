# CSI Soporte

Sistema web para registrar y dar seguimiento a solicitudes de soporte tecnológico de la GRED Lambayeque.

## Requisitos

Docker Engine con Docker Compose. Para desarrollo local de estilos: Node.js 20 o superior.

## Inicio rápido

```sh
cp .env.example .env
docker compose up --build
```

La aplicación queda disponible en <http://localhost:8080>. PostgreSQL escucha en `localhost:5432`. La primera migración y los datos de referencia se aplican al iniciar `app`. No se crean usuarios demo con contraseñas predecibles; se habilitarán junto con el flujo de autenticación.

Comandos útiles:

```sh
docker compose exec app composer test
docker compose exec app composer migrate
docker compose logs -f app web db
docker compose down
```

## Estructura

```text
src/Domain/                 Entidades, reglas, contratos y excepciones
src/Application/            Casos de uso, DTOs y puertos
src/Infrastructure/         PDO, persistencia, migraciones y configuración
src/Presentation/           HTTP, vistas, rutas y estilos
tests/Unit/                 Pruebas unitarias
tests/Integration/          Pruebas de integración
database/migrations/        Migraciones SQL versionadas
database/seeds/             Datos de referencia
docker/                     PHP-FPM y Nginx
public/                     Único directorio público
storage/                    Archivos privados fuera del webroot
```

## Configuración

Revisa `.env.example`. En despliegues, establece `APP_SECRET` y credenciales de base de datos únicas. Los adjuntos se guardan en `storage/private` y nunca bajo `public`.

## Cuentas y acceso (Sprint 1)

No hay contraseñas demo. Crea cada cuenta desde una terminal interactiva; la contraseña se solicita sin incluirla en el historial del shell:

```sh
docker compose exec app php bin/user-create.php responsable@regionlambayeque.gob.pe "Nombre Responsable" csi_manager
docker compose exec app php bin/user-create.php tecnico@regionlambayeque.gob.pe "Nombre Técnico" technician
docker compose exec app php bin/user-create.php trabajador@regionlambayeque.gob.pe "Nombre Trabajador" worker
```

El acceso está en `/`, los paneles en `/dashboard` y el cierre es un `POST` protegido por CSRF. En HTTPS, establece `SESSION_SECURE=1`.

## Estado del proyecto

Etapa 0 y Sprint 1: base de Clean Architecture, infraestructura Docker, autenticación, control de acceso y paneles por rol. El skill UI UX Pro Max está instalado para Codex en `.agents/skills/ui-ux-pro-max/`; su sistema visual persistido está en `design-system/csi-soporte-gred-lambayeque/MASTER.md`. Ver también `docs/design-system.md` y `docs/decisiones.md`. Próximos sprints: registro de solicitudes y adjuntos; asignación y atención; auditoría, búsqueda y reportes.
