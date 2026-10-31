# Resumen técnico · Sprint 01

## Objetivo

Dejar operativos y verificables el inicio de sesión, la administración de cuentas, los roles y los permisos individuales del sistema CSI GRED.

## Historias y criterios de aceptación

Se relaciona con **HU01 (autenticación y acceso)** y **HU02 (administración de usuarios y roles)** de la planificación existente, ampliadas con el alta web y los permisos individuales del modelo operativo CSI.

| Criterio | Estado comprobado |
| --- | --- |
| El Responsable CSI crea usuarios en la web | Ruta `/csi/users/new`, formulario y prueba HTTP con los cuatro roles. |
| Correo único y datos válidos | Validación del caso de uso y restricción única de PostgreSQL sin distinguir mayúsculas. |
| Contraseña segura | Hash Argon2id; cambio obligatorio de la contraseña inicial antes de usar rutas privadas. |
| Sesión segura | Login, CSRF, logout, redirección anónima y revocación al desactivar cuenta. |
| Acceso según rol | Permisos básicos por `role_permission`; permisos extra del locador por `user_permissions`, recargados en cada petición. |
| Auditoría | Concesión y revocación registradas con actor y momento en `user_permission_history`. |

## Funcionalidades y tareas realizadas

- Se reutilizaron repositorios PDO, casos de uso, sesiones y control de acceso existentes. El alta web añadió el formulario móvil en administración CSI, validación de entrada y mensajes comprensibles.
- La migración aditiva `005_user_onboarding.sql` añadió el cambio obligatorio de contraseña inicial y el índice único de correo normalizado. No se modificaron migraciones anteriores.
- El Responsable CSI puede crear cuentas activas o inactivas para trabajador, locador, responsable y practicante. Los técnicos y practicantes empiezan sin permisos administrativos adicionales.
- Se mantuvo el comportamiento actual de solicitudes, atenciones, reportes y tareas delegadas.
- En esta revisión se amplió la cobertura HTTP del ciclo completo de permisos del locador, intentos directos de elevar roles y crear cuentas, acceso del practicante, y cierre de sesión.

## Archivos principales

| Área | Archivos |
| --- | --- |
| Autenticación y permisos | `src/Application/Authentication/`, `src/Domain/Authentication/`, `src/Infrastructure/Persistence/PdoUserRepository.php`, `src/Infrastructure/Persistence/PdoUserAdministrationRepository.php`, `src/Infrastructure/Persistence/PdoUserPermissionRepository.php` |
| HTTP y vistas | `public/index.php`, `src/Infrastructure/Http/Session.php`, `src/Presentation/Views/csi-users.php`, `csi-user-create.php`, `change-password.php` |
| Base de datos | `database/migrations/005_user_onboarding.sql`, `database/migrations/004_csi_collaboration.sql` |
| Pruebas revisadas | `tests/Unit/Authentication/LoginTest.php`, `tests/Integration/Http/UserCreationFlowTest.php`, `SessionRevocationTest.php`, `CsiOperationalFlowTest.php` |

## Pruebas y resultado

La última suite completa terminó con **46 pruebas aprobadas, 0 fallidas y 479 aserciones**. Consulta [pruebas.md](pruebas.md) y la [salida original de PHPUnit](phpunit-output.txt). Las pruebas HTTP ejercitan el backend con cuentas de ensayo temporales y las eliminan al terminar. El estado real de Docker y las respuestas HTTP anónimas están guardados en esta carpeta.

## Limitaciones reales

- No hay envío de correos: el responsable debe comunicar la contraseña inicial de forma privada.
- El primer Responsable CSI se crea por terminal al instalar el sistema; las cuentas posteriores se crean desde la web.
- No se obtuvieron capturas reales desde este entorno por fallo de Chrome headless al iniciar su proceso gráfico. La guía de capturas indica las diez pantallas y los pasos para capturarlas manualmente.
- La prueba con agente móvil verifica la respuesta HTTP de login, pero no sustituye una prueba visual y táctil en un teléfono real.
- La instalación Docker actual escucha en `127.0.0.1`; el acceso desde un celular requiere una dirección de red o despliegue adecuado y HTTPS antes de uso institucional.

Este documento registra trabajo técnico y resultados observados. No acredita firmas, reuniones, aprobaciones ni validaciones de un supervisor.
