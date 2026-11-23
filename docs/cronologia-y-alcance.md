# Cronología verificable y cambios de alcance de SISTEMA-GRED

**Corte:** 22 de noviembre de 2026. **Fuente principal:** historial Git de `main` hasta `d6c4b3a`, planes en `docs/` y evidencias de pruebas. Las horas de los commits están en `-05:00` (Perú/Colombia). **Inicio y fin** significan aquí *primer y último commit identificable de la entrega*, no horas efectivas de trabajo ni fechas de aprobación. Git no acredita reuniones, ejecución previa al commit ni aceptación del supervisor.

| Etapa | Inicio y fin verificables en Git | Historias | Tareas e incremento entregado | Evidencia |
| --- | --- | --- | --- | --- |
| Base y Sprint 01 inicial | 26/10/2026 01:12, único commit inicial | HU01–HU02 | Infraestructura Docker/PostgreSQL, migraciones, autenticación, roles, permisos base y CLI de alta inicial. El commit raíz reúne trabajo previo que no puede separarse temporalmente. | `5ad6531`; [resumen Sprint 01](evidencias-sprint-01/resumen-sprint-01.md) |
| Sprint 02 | 31/10/2026 01:21–01:22 | HU03–HU06 | Registro, consulta y detalle de solicitudes propias; código público; evidencias privadas; pruebas del flujo trabajador. | `682d298`–`2637805`; [plan](sprint-2-plan.md) |
| Sprint 03 | 31/10/2026 01:34–01:35 | HU07–HU09 | Supervisión, clasificación, asignación y reasignación CSI con pruebas. | `9117fdd`–`d930c0c`; [plan](sprint-3-plan.md) |
| Sprint 04 | 31/10/2026 01:52 | HU10–HU13 | Bandeja técnica, cambio de estado, observaciones, solución visible para el trabajador y control de acceso a evidencias. | `44c990b`–`9e90b26`; [plan](sprint-4-plan.md) |
| Sprint 05 | 31/10/2026 02:06 | HU14–HU16 | Historial de acciones, búsqueda y filtros, reportes y exportaciones CSV/PDF. | `3dc5603`–`bf7f613`; [plan](sprint-5-plan.md) |
| Sprint 06 inicial | 31/10/2026 02:21 | Verificación y entrega; ampliación de HU02 | Protección de sesiones y despliegue local, administración de rol/estado, revocación y guía de entrega. | `404e8f1`–`2b1e739`; [plan](sprint-6-plan.md), [operación](entrega.md) |
| Ampliación del modelo CSI | 31/10/2026 10:36, commit único | Sin HU nueva numerada | Permisos individuales de técnicos y tareas delegadas a practicantes, con envío, aprobación y cancelación. | `39a34ba`; [ajuste de modelo](csi-workflow-plan.md) |
| Reapertura de Sprint 01 | 31/10/2026 11:58–12:49 | HU01–HU02 ampliadas | Alta web de usuarios, contraseña inicial con cambio obligatorio, validación y pruebas/evidencia del acceso. | `be99f19`–`ee4e3f6`; [resumen](evidencias-sprint-01/resumen-sprint-01.md) |
| Mejoras operativas y visuales | 31/10/2026 10:48–10/11/2026 22:56 | Sin HU nuevas numeradas | Panel trabajador, acceso institucional, resumen CSI, alertas, carga de equipo, notificaciones por destinatario, formularios y rediseños de solicitudes, equipo, reportes y administración. Hubo varias iteraciones y reversión de elementos visuales. | `1ec71e7`–`d2da339`; [decisiones](decisiones.md) |
| Verificación posterior | 22/11/2026 20:55–21:34 | Revisión transversal HU01–HU16 y flujo de practicantes | Pruebas de flujo técnico, historial, filtros, exportaciones, acceso, arranque y recorrido integral; evidencia y ampliación de aserciones. | `d1575d1`–`d6c4b3a`; [evidencias](evidencias-pruebas/) |

## Errores corregidos y ajustes comprobables

| Fecha del commit | Problema y corrección | Referencia |
| --- | --- | --- |
| 31/10/2026 | Caducidad de cookie al cerrar sesión: corrección en `Session::destroy()`. | `3a7f4cc` |
| 31/10/2026 | Acceso CSI poco claro para quien cambiaba de cuenta: mensajes y navegación ajustados. | `3f39e5f` |
| 01/11/2026 | Fondos de tarjetas no presentes en el CSS compilado: se conservaron las clases necesarias en Tailwind. | `8f3db2a` |
| 03/11/2026 | Panel de notificaciones que no se cerraba al pulsar fuera: se añadió el cierre. | `620932d` |
| 09–10/11/2026 | Se restauró el diseño anterior del menú y se retiró la vista previa de solicitudes tras revisar el diseño solicitado. Son rectificaciones de interfaz, no fallos de datos. | `6d53296`, `481276e` |
| 22/11/2026 | Dos pruebas fallaban por esperar HTML anterior; se actualizaron sus aserciones. La suite pasó después con 53 pruebas y 725 aserciones. No hubo corrección de lógica de aplicación. | `d1575d1`, `fd46064`; [evidencia](evidencias-pruebas/flujo-tecnico-trabajador-2026-11-22.md) |

## Cambios de alcance identificados

1. **HU02 creció respecto al acceso inicial por CLI.** Sprint 06 agregó cambio de rol/estado desde la web; después se añadieron permisos individuales y el alta web con contraseña inicial. La CLI quedó para el primer responsable y recuperación. Fuentes: [plan Sprint 06](sprint-6-plan.md), [ajuste CSI](csi-workflow-plan.md), [resumen Sprint 01](evidencias-sprint-01/resumen-sprint-01.md).
2. **La colaboración con practicantes amplió el flujo técnico.** Se añadieron tareas separadas y validación antes de solucionar, sin reemplazar la asignación principal. Fuente: `39a34ba` y [decisiones 25–27](decisiones.md).
3. **Se añadieron capacidades posteriores a los planes de Sprint 02–05:** notificaciones internas dirigidas, paneles operativos, alertas de demora, filtros y análisis adicionales. Los commits registran implementación, pero no una HU aprobada o un control formal de alcance para estas capacidades.
4. **El alcance visual cambió por iteraciones de referencia.** Se añadieron, reorganizaron y retiraron componentes; el registro Git permite identificar esos cambios, no medir aceptación visual. Ejemplos: guía de trabajo añadida el 01/11 y retirada el 03/11 (`b212f43`, `7679bf9`); vista previa de solicitud añadida el 08/11 y retirada el 10/11 (`75e399e`, `481276e`).
5. **Estados y asignación se separaron conceptualmente.** «Asignada» se representa con una asignación activa y no como estado de atención; el procedimiento de reapertura y la equivalencia con RF20 siguen pendientes de revisión del supervisor. Fuente: [decisión 28](decisiones.md).

## Qué falta confirmar fuera del repositorio

- Fechas reales de inicio y cierre de cada sprint, horas trabajadas, responsables y actas de aceptación. Los intervalos de commit no sustituyen esos registros.
- Quién aprobó los cambios de alcance, cuándo y con qué versión de requisitos. No hay actas o tickets de aprobación en las fuentes revisadas.
- Prueba visual y táctil en un celular real y validación del modelo RF20 por el supervisor. La prueba HTTP con agente móvil existe, pero no reemplaza esa validación.

Para convertir este documento en un cronograma oficial hacen falta el backlog fechado, actas o tickets de trabajo, y la confirmación del responsable de cada sprint. No se han inventado esas fechas ni aprobaciones.
