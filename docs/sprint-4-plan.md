# Sprint 4 · atención técnica

HU10–HU13. La bandeja `GET /technician/requests` lista solo asignaciones activas y pagina los resultados. `GET /technician/requests/{code}` muestra el caso y sus evidencias; `GET /technician/attachments/{id}` entrega archivos privados solo si pertenecen a una asignación activa. `POST /technician/requests/{code}/progress` registra el estado y una observación; al solucionar exige texto de solución. El trabajador ve esa solución en `GET /requests/{code}` únicamente si el caso es suyo.

Archivos: contrato `AssignedRequestRepository`, adaptador PDO, casos de uso para listar, consultar y actualizar, rutas HTTP, vistas del técnico, ampliación del detalle del trabajador y pruebas. Tablas existentes: `request_assignments`, `requests`, `statuses`, `request_history`, `request_solutions`, `request_attachments`; no se requiere migración.

Transiciones: Pendiente → En atención → Solucionada. Una solicitud solucionada no puede modificarse desde el panel técnico; Cerrada y Cancelada quedan fuera del flujo de este sprint. La reasignación revoca el acceso del técnico anterior. Los cambios de estado, observación, solución e historial se guardan en una misma transacción.
