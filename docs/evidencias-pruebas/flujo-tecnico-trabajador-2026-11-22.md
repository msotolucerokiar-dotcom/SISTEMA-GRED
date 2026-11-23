# Verificación del flujo técnico y trabajador

**Fecha:** 22 de noviembre de 2026 (America/Bogota). **Versión base:** `d1575d1` en `main`. Entorno: PHP 8.3.36, PHPUnit 11.5.57, aplicación en Docker.

## Antes

- Pruebas específicas del flujo: **3 correctas, 77 aserciones, 0 fallos**. Salida local: `tecnico-trabajador-antes-2026-11-22.txt`.
- La ejecución anterior de la suite completa registró **53 pruebas, 653 aserciones y 2 fallos** por aserciones de HTML que ya no correspondían a la interfaz actual. Véase [evidencia de la suite anterior](README.md).

## Comprobaciones y ajustes

Las pruebas de integración HTTP y persistencia verifican que el técnico asignado inicia la atención, registra una observación en el historial y guarda una solución; después, el trabajador propietario consulta el estado, la observación y el texto de la solución. También comprueban que no se muestra una solución antes de registrarla, que un técnico ajeno no puede intervenir y que se validan el estado y el token CSRF.

Se añadieron cuatro aserciones al flujo para comprobar explícitamente los estados y la observación visibles para el trabajador. Se actualizaron dos aserciones de otras pruebas para reflejar el HTML actual: el filtro de validación mediante una etiqueta activa y la confirmación de creación de usuarios con entidades HTML. **No fue necesario modificar código de producción.**

## Después

| Ejecución | Resultado | Salida conservada localmente |
| --- | --- | --- |
| `docker compose exec -T app vendor/bin/phpunit tests/Integration/Http/TechnicianFlowTest.php tests/Integration/Persistence/PdoAssignedRequestRepositoryTest.php tests/Integration/Http/WorkerRequestFlowTest.php` | **3 pruebas, 81 aserciones, 0 fallos** | `tecnico-trabajador-despues-2026-11-22.txt` |
| `docker compose exec -T app composer test` | **53 pruebas, 725 aserciones, 0 fallos** | `suite-despues-2026-11-22.txt` |

Las salidas completas `.txt` quedan en esta carpeta del entorno local y están excluidas de Git; este resumen sí se conserva en el repositorio. La verificación se hizo con pruebas de integración HTTP y persistencia, sin una sesión manual de navegador.
