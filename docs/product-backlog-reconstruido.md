# Product Backlog reconstruido — borrador para validación

**Corte:** 22 de noviembre de 2026. **Estado:** propuesta reconstruida, pendiente de confirmación del Product Owner o responsable de planificación. La prioridad y el sprint de HU01–HU16 proceden del encargo inicial del proyecto aportado por el usuario; los planes de `docs/sprint-*-plan.md`, el código y las pruebas confirman entregas técnicas, no una aprobación formal del backlog vigente. No se han reasignado prioridades sin evidencia.

## Historias originales y distribución

| ID | Historia del encargo inicial | Prioridad original | Sprint original | Situación observable al corte |
| --- | --- | --- | --- | --- |
| HU01 | Iniciar y cerrar sesión con credenciales y mensaje claro de error | Alta | 1 | Implementada y probada; se amplió el primer acceso con cambio de contraseña. |
| HU02 | Gestionar roles y permisos; cada usuario accede solo a lo autorizado | Alta | 1 | Implementada y ampliada con alta web, permisos individuales y rol opcional de practicante. |
| HU03 | Registrar solicitud con descripción del problema | Alta | 2 | Implementada y probada. |
| HU04 | Adjuntar evidencias con validación de tipo y tamaño | Alta | 2 | Implementada y probada; archivos privados. |
| HU05 | Generar un código único por solicitud | Alta | 2 | Implementada y probada. |
| HU06 | Consultar solicitudes y estados propios del trabajador | Alta | 2 | Implementada y probada. |
| HU07 | Visualizar todas las solicitudes desde el Responsable CSI | Alta | 3 | Implementada y probada. |
| HU08 | Clasificar por categoría y priorizar solicitudes | Alta | 3 | Implementada y probada. |
| HU09 | Asignar y reasignar solicitudes al personal técnico | Alta | 3 | Implementada y probada; conserva historial. |
| HU10 | Consultar solicitudes asignadas y evidencias como técnico | Alta | 4 | Implementada y probada. |
| HU11 | Actualizar estado: Pendiente, En atención, Solucionada, Cerrada, etc. | Alta | 4 | **Parcial frente al texto original:** funciona Pendiente → En atención → Solucionada. Cerrada y Cancelada no tienen operación implementada; requieren criterio de aceptación y decisión de reapertura/cierre. |
| HU12 | Registrar observaciones y solución como técnico | Alta | 4 | Implementada y probada. |
| HU13 | Consultar la solución registrada como trabajador | Media | 4 | Implementada y probada. |
| HU14 | Consultar historial de acciones por solicitud: quién, qué y cuándo | Media | 5 | Implementada y probada con acceso según rol. |
| HU15 | Buscar y filtrar por estado, fecha, área, categoría y responsable | Media | 5 | Implementada y probada; se añadieron filtros operativos posteriores. |
| HU16 | Reportar solicitudes registradas y atendidas con tablas, gráficos simples y CSV/PDF | Media | 5 | Implementada y probada; indicadores ampliados posteriormente. |

**Sprint 0:** base técnica, Docker, PostgreSQL, migraciones, arquitectura, UI inicial y PHPUnit; sin HU numerada en el backlog inicial. **Sprint 6:** pruebas transversales, correcciones, seguridad, documentación y preparación de entrega; tampoco tenía HU nueva numerada. La secuencia anterior figura en el encargo inicial y coincide con los [planes técnicos](sprint-2-plan.md) y la [cronología de commits](cronologia-y-alcance.md).

## Ampliaciones posteriores que necesitan decisión de backlog

| Cambio observado | Relación probable | Evidencia | Decisión pendiente |
| --- | --- | --- | --- |
| Alta de usuarios desde la web y cambio obligatorio de contraseña inicial | Ampliación de HU01–HU02 | [Resumen Sprint 01](evidencias-sprint-01/resumen-sprint-01.md) | Confirmar si queda dentro de HU01/HU02 o se registra como historia aparte. |
| Permisos individuales del locador, practicante opcional y tareas delegadas con validación | Ampliación de HU02, HU09–HU12 | [Ajuste del modelo CSI](csi-workflow-plan.md) | Confirmar historias, prioridad y sprint atribuido. |
| Notificaciones internas dirigidas | Capacidad adicional sin HU original | Commit `52e2834`; [documentación](notificaciones.md) | Crear o vincular una HU y asignar prioridad. |
| Paneles operativos, alertas de demora, carga de equipo y análisis de reportes | Ampliaciones de HU07, HU15–HU16 | [Cronología](cronologia-y-alcance.md), [decisiones](decisiones.md) | Confirmar alcance y criterios de aceptación. |
| Registro y consulta desde celular | RNF01 y mejora de HU03–HU04 | [Prueba HTTP móvil](evidencias-pruebas/flujo-integral-movil-2026-11-22.md) | Confirmar si requiere HU específica y prueba visual en dispositivo real. |

## Puntos que impiden llamarlo «versión vigente aprobada»

1. Confirmar que las prioridades **Alta/Media** del encargo inicial siguen vigentes; no hay reordenación posterior fechada en el repositorio.
2. Confirmar si los sprints de las ampliaciones reflejan la planificación real o solo la fecha en que se implementaron. La implementación posterior no cambia por sí misma el sprint original de HU01–HU16.
3. Acordar el criterio de aceptación pendiente de HU11: quién puede cerrar o reabrir una solicitud y qué transiciones se autorizan. La [decisión 28](decisiones.md) deja también pendiente la revisión de RF20 por el supervisor.
4. Confirmar responsables, aprobaciones y fecha de entrada en vigor del backlog. Los commits y planes técnicos no contienen esas actas.

Tras esa validación, este borrador podrá marcarse como backlog vigente y registrar versión, fecha, responsable y cambios aprobados. Hasta entonces, refleja la planificación inicial y el alcance implementado que sí se pueden rastrear.
