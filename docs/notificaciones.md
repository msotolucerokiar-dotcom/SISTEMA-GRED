# Notificaciones en el portal

La campanita del panel muestra los cinco avisos más recientes de la cuenta y el número de avisos sin leer. «Ver todas las notificaciones» abre la bandeja paginada en `/notifications`. Al seleccionar un aviso, el sistema lo marca como leído y abre el caso o tarea correspondiente. Cada consulta y cada apertura comprueban el usuario autenticado; no es posible leer los avisos de otra cuenta.

La migración `006_notifications.sql` crea la bandeja. Los avisos se generan al insertar acciones en `request_history`; ese historial sigue siendo el registro de origen. Solo se notifican eventos nuevos posteriores a la migración. No se envían correos.

Destinatarios actuales:

| Evento | Cuenta que recibe el aviso |
| --- | --- |
| Nueva solicitud | Responsables CSI activos, excepto quien la registró |
| Asignación o reasignación | Nuevo técnico, técnico anterior cuando corresponda y trabajador solicitante |
| Clasificación, inicio o solución | Trabajador solicitante y otros responsables CSI activos |
| Tarea delegada, validada o cancelada | Practicante encargado |
| Tarea enviada a validar | Responsable de esa tarea y otros responsables CSI activos |

El autor de una acción no recibe una notificación de su propia acción. Las cuentas desactivadas no reciben avisos nuevos; sus avisos previos permanecen asociados a la cuenta hasta que se elimine. Las rutas de destino siguen aplicando sus permisos habituales. Para activar la migración en una instalación en marcha, ejecuta `docker compose exec app php bin/migrate.php`.
