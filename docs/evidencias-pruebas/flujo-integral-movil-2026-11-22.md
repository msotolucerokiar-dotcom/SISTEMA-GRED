# Flujo integral de atención y registro desde celular

**Fecha:** 22 de noviembre de 2026 (America/Bogota). **Versión base:** `00fe1d4` (`main`). **Entorno:** PHP 8.3.36, PHPUnit 11.5.57, Docker Compose local.

## Recorrido probado

1. El trabajador inicia sesión y registra una solicitud mediante `POST /requests/new`. La prueba integral usa el código devuelto por esa misma operación para los pasos siguientes.
2. El Responsable CSI concede y revoca permisos individuales, consulta la solicitud, y permite su asignación y clasificación. Las rutas administrativas deniegan el acceso a técnico y practicante sin el permiso correspondiente.
3. El técnico asignado consulta el caso y delega una tarea. El practicante consulta solamente su tarea, registra el trabajo y la envía a validación. Se comprueba el rechazo sin CSRF y que el practicante no puede aprobarla.
4. El técnico aprueba el trabajo delegado. El responsable comprueba que ya no queda validación pendiente. Se comprueba también la cancelación de otra tarea y el historial de permisos.
5. El técnico inicia la atención y registra la solución. El responsable ve el cambio de seguimiento y el trabajador consulta la solución en su propia solicitud.

## Comprobación móvil

La prueba HTTP del trabajador usa un agente de usuario de iPhone para iniciar sesión, abrir el formulario, enviar la solicitud con un adjunto PDF y consultar después el detalle. Se verificaron la metaetiqueta `viewport`, controles de ancho completo y tamaño táctil mínimo de 48 px en el formulario, además de su texto orientativo para registrar desde el celular.

El navegador local sin interfaz no pudo generar una captura a 390 px porque falló el proceso gráfico. Por ello, **la disposición visual en un navegador móvil real queda pendiente de una comprobación manual**; la prueba HTTP y la inspección del marcado no acreditan por sí solas la apariencia final en pantalla.

## Resultado

Antes de ampliar la prueba integral, pasaron **5 pruebas y 159 aserciones**. Después de incluir el registro web en el mismo recorrido y la comprobación HTTP móvil, pasaron **5 pruebas y 164 aserciones, sin fallos** (`00:56.298`). Comando:

```sh
docker compose exec -T app vendor/bin/phpunit tests/Integration/Http/WorkerRequestFlowTest.php tests/Integration/Http/CsiOperationalFlowTest.php tests/Integration/Http/TechnicianFlowTest.php tests/Integration/Persistence/PdoTaskRepositoryTest.php
```

No se modificó código de producción ni se encontraron defectos en el flujo probado.
