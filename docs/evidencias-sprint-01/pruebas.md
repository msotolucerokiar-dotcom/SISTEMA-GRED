# Registro de verificación · Sprint 01

## Comandos ejecutados

```powershell
docker compose exec -T app vendor/bin/phpunit tests/Unit/Authentication/LoginTest.php tests/Integration/Http/UserCreationFlowTest.php tests/Integration/Http/SessionRevocationTest.php
docker compose exec -T app vendor/bin/phpunit tests/Integration/Http/UserCreationFlowTest.php tests/Integration/Http/CsiOperationalFlowTest.php
docker compose exec -T app composer test
docker compose ps --status running
```

La suite completa se ejecutó con `-T` porque la consola de automatización no es interactiva. Es el mismo script `composer test` solicitado. Su salida original está en [phpunit-output.txt](phpunit-output.txt); no se reescribió manualmente.

## Cobertura por escenario

| Escenario | Evidencia automatizada |
| --- | --- |
| Login válido, contraseña errónea, correo desconocido, cuenta inactiva y límite de fallos | `tests/Unit/Authentication/LoginTest.php`; persistencia de intentos en `tests/Integration/Persistence/PdoUserRepositoryTest.php`; alta inactiva rechazada por HTTP en `UserCreationFlowTest.php`. |
| Primer acceso y contraseña inicial | `tests/Integration/Http/UserCreationFlowTest.php`: cuatro roles creados desde la web, hash Argon2id, redirección a cambio obligatorio, rechazo de contraseña anterior y acceso con la nueva. |
| Solicitudes HTTP con agente móvil | `UserCreationFlowTest.php` repite un login con cabecera de agente iPhone. Verifica el backend, no la presentación visual de un teléfono. |
| Logout, sesión invalidada y rutas privadas anónimas | `tests/Integration/Http/SessionRevocationTest.php`; [http-anonimo.txt](http-anonimo.txt) registra redirecciones sin cookies. |
| Cambio de rol y desactivación en la siguiente petición | `SessionRevocationTest.php`; `UserCreationFlowTest.php` comprueba POST de cambio de rol y estado, seguido de lectura real en PostgreSQL. |
| Protección CSRF | `UserCreationFlowTest.php`, `SessionRevocationTest.php` y `CsiOperationalFlowTest.php` esperan HTTP 419 para formularios sin token. |
| Trabajador: panel, registro y privacidad | `tests/Integration/Http/WorkerRequestFlowTest.php` y `tests/Integration/Persistence/PdoRequestRepositoryTest.php`. |
| Locador: atención, sin asignación por defecto, concesión y revocación en la misma sesión | `tests/Integration/Http/TechnicianFlowTest.php`, `UserCreationFlowTest.php` y `CsiOperationalFlowTest.php`. Se comprueba HTTP 403 → 303 → 403 y conservación del caso ya asignado. |
| Responsable CSI: alta, roles, estado, permisos, supervisión y atención directa | `UserCreationFlowTest.php`, `CsiManagementFlowTest.php`, `PdoUserAdministrationRepositoryTest.php` y `PdoTaskRepositoryTest.php`. |
| Practicante: tareas propias y prohibición de administrar | `CsiOperationalFlowTest.php` y `PdoTaskRepositoryTest.php`. |
| Escalación indebida y último responsable | `UserCreationFlowTest.php` rechaza POST de trabajador para convertirse en responsable y POST de técnico para crear usuarios; `CsiOperationalFlowTest.php` rechaza administración del practicante y autoconcesión del técnico; `PdoUserAdministrationRepositoryTest.php` protege al último responsable activo. |
| Auditoría de permisos | `CsiOperationalFlowTest.php` y `PdoUserPermissionRepositoryTest.php` leen la secuencia de concesiones/revocaciones de PostgreSQL. |

## Resultado de la última suite completa

La última ejecución terminó con **46 pruebas aprobadas, 0 fallidas y 479 aserciones** en **02:29.807**. Esta cifra procede de la salida original en [phpunit-output.txt](phpunit-output.txt). Las rutas HTTP anónimas respondieron como se observa en [http-anonimo.txt](http-anonimo.txt). Los contenedores activos constan en [docker-servicios.txt](docker-servicios.txt).

## Incidencias y correcciones de esta revisión

- No se detectó un defecto funcional nuevo en autenticación, autorización ni persistencia durante las verificaciones ejecutadas. La brecha encontrada fue de **cobertura verificable**: se ampliaron pruebas existentes para logout, correo desconocido, ciclo de permisos del locador creado desde la web, cambios de rol/estado por HTTP y escalación indebida.
- Se intentó obtener una captura auténtica del login con Chrome headless. El navegador falló antes de renderizar (`GPU process isn't usable`); no se guardó ni presentó ninguna imagen como evidencia. La [guía de capturas](capturas/README.md) documenta los diez pasos manuales.
- No se modificó código de producción, migraciones ni datos institucionales en esta revisión. Las pruebas crean cuentas y solicitudes de ensayo temporales y las eliminan en su limpieza.

## Verificación pendiente fuera de la automatización

Realizar las diez capturas en un navegador interactivo y una prueba táctil en un celular que tenga acceso al portal desplegado. La simulación de agente móvil comprueba HTTP, pero no sustituye esa observación visual. No hay constancia de validación por supervisor.
