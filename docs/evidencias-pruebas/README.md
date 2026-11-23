# Resultado de PHPUnit — 22 de noviembre de 2026

- **Versión probada:** rama `main`, commit `d2da339`.
- **Comando:** `docker compose exec -T app composer test`.
- **Entorno:** PHP 8.3.36, PHPUnit 11.5.57.
- **Resultado:** 53 pruebas, 653 aserciones; 51 pruebas pasaron y 2 fallaron. Código de salida: 1. Duración: 2 min 16 s.

## Fallos

1. `CsiOperationalFlowTest::testIndividualPermissionsAndInternWorkFlow` (`tests/Integration/Http/CsiOperationalFlowTest.php:84`): esperaba `value="pending" selected` en la respuesta de `/csi/requests?validation=pending`, pero no aparece.
2. `UserCreationFlowTest::testManagerCreatesAccountsAndNewUserChangesInitialPassword` (`tests/Integration/Http/UserCreationFlowTest.php:55`): esperaba el texto literal «El usuario se creó correctamente»; el HTML devuelve «El usuario se cre&oacute; correctamente».

La salida completa de PHPUnit se guardó localmente en `phpunit-2026-11-22-d2da339.txt`. El archivo se excluye de Git porque los fallos imprimen HTML con datos de cuentas y tokens CSRF temporales. Este resumen no incluye esos datos.
