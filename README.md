# CSI Soporte

Sistema web para registrar y dar seguimiento a solicitudes de soporte tecnológico de la GRED Lambayeque.

## Requisitos

Docker Engine con Docker Compose. Para desarrollo local de estilos: Node.js 20 o superior.

## Inicio rápido

```sh
cp .env.example .env
docker compose up --build
```

La aplicación queda disponible en <http://localhost:8080>. PostgreSQL escucha solo en `127.0.0.1:5432`. Las migraciones y los datos de referencia se aplican al iniciar `app`. Las cuentas se crean mediante el comando interactivo indicado abajo; no se incluyen contraseñas en el repositorio.

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

Revisa `.env.example`. En despliegues, establece credenciales de base de datos únicas y termina HTTPS antes de exponer el portal. Los adjuntos se guardan en `storage/private` y nunca bajo `public`. La guía de entrega y respaldo está en `docs/entrega.md`.

## Cuentas y acceso (Sprint 1)

No hay contraseñas demo. Crea cada cuenta desde una terminal interactiva; la contraseña se solicita sin incluirla en el historial del shell:

```sh
docker compose exec app php bin/user-create.php responsable@regionlambayeque.gob.pe "Nombre Responsable" csi_manager
docker compose exec app php bin/user-create.php tecnico@regionlambayeque.gob.pe "Nombre Técnico" technician
docker compose exec app php bin/user-create.php trabajador@regionlambayeque.gob.pe "Nombre Trabajador" worker
```

El acceso está en `/`, los paneles en `/dashboard` y el cierre es un `POST` protegido por CSRF. En HTTPS, establece `SESSION_SECURE=1`.

El Responsable CSI administra roles y estado de las cuentas desde `/csi/users`. Los cambios se aplican en la próxima petición de cada usuario; una cuenta desactivada pierde su sesión. La interfaz impide cambiar la cuenta propia y retirar al último responsable activo.

El Responsable CSI también configura allí los permisos individuales de los técnicos: asignar o reasignar, clasificar, consultar reportes y delegar tareas. Una cuenta nueva de técnico comienza sin estos permisos; cada concesión o revocación queda auditada y surte efecto en la siguiente petición. Al desactivar una cuenta técnica o cambiarle el rol, se revocan sus permisos adicionales.

## Solicitudes del trabajador (Sprint 2)

Después de iniciar sesión como trabajador, abre `/requests/new` para registrar un problema y `/requests` para consultar los casos propios. Cada solicitud recibe un código `SOL-año-número` y comienza en estado Pendiente. Se permiten hasta tres evidencias PDF/JPG/PNG/WebP de 8 MiB cada una. Los archivos se guardan en `storage/private` y se descargan mediante una ruta que comprueba la propiedad de la solicitud.

La migración `003_worker_requests.sql` se aplica al iniciar `app`; para ejecutarla manualmente usa `docker compose exec app composer migrate`. Tras cambiar estilos, recompila el CSS con `docker compose --profile assets run --rm tailwind`.

La portada incorpora animaciones discretas con GSAP y ScrollTrigger, que respetan la preferencia de movimiento reducido. Para regenerar los recursos locales con Node usa `npm ci` y `npm run build:assets`; en Docker, `docker compose --profile assets run --rm tailwind` construye CSS y los archivos de GSAP.

## Supervisión CSI (Sprint 3)

Inicia sesión con una cuenta `csi_manager` y abre `/csi/requests`. El responsable ve todas las solicitudes; en cada detalle puede elegir una categoría y prioridad, asignar un técnico activo o reasignar el caso. Cada cambio se guarda en una transacción y registra una acción en el historial. El trabajador no puede acceder a estas rutas.

Para crear cuentas del responsable y de técnicos, usa `bin/user-create.php` con los roles `csi_manager` y `technician`. La cuenta técnica debe permanecer activa para aparecer en la lista de asignación.

## Atención técnica (Sprint 4)

Inicia sesión con una cuenta `technician` y abre `/technician/requests`. Solo aparecen las solicitudes actualmente asignadas a esa cuenta. En cada caso puedes consultar evidencias, iniciar la atención con una observación y, después, registrar la solución. La solución es obligatoria al marcar el caso como **Solucionada**; el trabajador la ve en el detalle de su propia solicitud. Una reasignación retira el acceso del técnico anterior.

Para probar el flujo completo, crea un trabajador, un responsable y un técnico con `bin/user-create.php`; el trabajador registra un caso, el responsable lo asigna desde `/csi/requests` y el técnico lo atiende desde `/technician/requests`. Ejecuta `docker compose exec app composer test` para verificar transiciones y permisos.

## Colaboración CSI

El responsable puede asignarse un caso para atenderlo directamente. La asignación principal sigue determinando quién responde por la solicitud. Si hace falta ayuda de un practicante, crea una cuenta opcional con rol `intern` y delega una tarea desde el detalle del caso. El practicante ve solo sus tareas en `/intern/tasks`, registra lo realizado y lo envía a validación. El responsable de la solicitud o el ingeniero aprueba o cancela la tarea. Una solicitud con tareas pendientes no puede marcarse como solucionada. Varios practicantes pueden colaborar mediante tareas separadas; la reasignación cancela las tareas aún abiertas y conserva el historial.

```sh
docker compose exec app php bin/user-create.php practicante@regionlambayeque.gob.pe "Nombre Practicante" intern
```

Si un practicante solo acompaña al equipo y no necesita registrar trabajo, no hace falta crearle una cuenta. Desactivar su cuenta conserva el historial de sus intervenciones.

## Historial, búsqueda y reportes (Sprint 5)

El historial de acciones aparece en el detalle de cada solicitud, con actor, fecha y observaciones. El trabajador solo ve solicitudes propias; el técnico, asignaciones activas; el responsable CSI, todas.

Desde `/csi/requests`, el responsable puede buscar por código o asunto y filtrar por estado, fecha, área, categoría y técnico. La paginación conserva los filtros. En `/csi/reports` se muestran totales, una distribución gráfica y una tabla por estado. Las descargas CSV y PDF contienen las solicitudes filtradas; el periodo del reporte se limita a 12 meses y, por defecto, toma los 12 meses anteriores a la fecha actual. Solo las cuentas con `report.view` pueden abrir o descargar reportes.

## Estado del proyecto

Etapa 0 y Sprints 1–6: base de Clean Architecture, infraestructura Docker, autenticación, administración de acceso, solicitudes del trabajador, supervisión CSI, atención técnica, historial, búsqueda, reportes y revisión de seguridad. El skill UI UX Pro Max está instalado para Codex en `.agents/skills/ui-ux-pro-max/`; su sistema visual persistido está en `design-system/csi-soporte-gred-lambayeque/MASTER.md`. Ver también `docs/design-system.md`, `docs/decisiones.md` y `docs/entrega.md`.
