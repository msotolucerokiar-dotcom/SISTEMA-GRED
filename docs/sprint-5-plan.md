# Sprint 5 · historial, búsqueda y reportes

HU14: mostrar en cada detalle una línea temporal de `request_history` con actor, acción, fecha y observación. El trabajador solo consulta el historial de solicitudes propias; el técnico, de asignaciones activas; el responsable CSI, de todas. Se añade un caso de uso y un repositorio de lectura, sin migración.

HU15: ampliar `GET /csi/requests` con búsqueda por código o asunto y filtros por estado, fecha de creación, área, categoría y técnico responsable. La paginación conserva los filtros. Los valores se validan antes de construir consultas preparadas.

HU16: `GET /csi/reports` muestra totales y distribución por estado para el mismo conjunto filtrado; `GET /csi/reports.csv` y `GET /csi/reports.pdf` exportan una tabla y resumen. Solo `report.view` permite el acceso. La exportación limita el rango a 12 meses para mantener el consumo de recursos acotado. Archivos nuevos: contrato y adaptador de consultas, casos de uso, exportadores CSV/PDF, rutas, vistas y pruebas unitarias, de integración PostgreSQL y HTTP.
