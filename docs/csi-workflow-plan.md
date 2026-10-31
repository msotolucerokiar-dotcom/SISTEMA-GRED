# Ajuste del modelo operativo CSI

El Responsable CSI conserva la autoridad de usuarios y permisos. Los técnicos conservan solo atención básica al crearse. Se añaden permisos individuales `request.assign`, `request.classify`, `report.view` y `request.delegate`; solo el responsable puede otorgarlos o revocarlos. La concesión activa vive en `user_permissions` y cada cambio en `user_permission_history`. Los permisos efectivos se recargan desde PostgreSQL en cada petición.

La asignación principal de `request_assignments` sigue indicando al responsable. El ingeniero puede ser responsable y usar el mismo flujo de atención. Un practicante opcional usa el rol `intern` y tareas separadas `request_tasks`: el responsable delega, el practicante documenta el trabajo y el responsable valida. Una tarea no cambia la asignación ni el estado de la solicitud. Se pueden crear varias tareas en un caso; la reasignación cancela tareas pendientes. Las acciones se reflejan en `request_history`.

Archivos: nuevas migraciones, repositorios PDO, casos de uso, rutas para permisos y tareas, vistas con el sistema visual actual, y pruebas de autorización, revocación y flujo. La creación de cuentas de practicante sigue siendo opcional con `bin/user-create.php`.
