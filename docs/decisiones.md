# Decisiones de implementación

1. **Sin cuentas de demostración en etapa 0.** Evita contraseñas por defecto activas antes de implementar login y el cambio inicial de credenciales. Los roles y permisos de referencia sí se siembran.
2. **Adjuntos privados en volumen separado.** Los metadatos quedan en PostgreSQL; los archivos no viven en el webroot. El límite inicial es 8 MiB por archivo y la validación MIME real se implementará con el caso de uso de adjuntos.
3. **SQL versionado y transaccional.** Se mantiene una tabla común de migraciones para migraciones y seeds; cada archivo se ejecuta una vez y en transacción.
4. **Sin framework HTTP en etapa 0.** La portada es PHP nativo; el composition root es explícito y pequeño. Se añadirá router/middleware de presentación cuando comience autenticación.
5. **El login de la portada es una vista de preparación.** No envía ni procesa credenciales; el botón está deshabilitado hasta el Sprint 1.
6. **Código público de solicitud se completa con HU05.** El esquema reserva `public_code` como único; su generación secuencial y atómica se añadirá en el caso de uso de registro.
7. **Sistema de diseño asistido por UI UX Pro Max.** El skill de Codex está en `.agents/skills/ui-ux-pro-max/`; la salida persistida para CSI GRED Lambayeque está en `design-system/csi-soporte-gred-lambayeque/MASTER.md`. Para páginas nuevas, priorizar accesibilidad, contraste alto, foco visible, controles táctiles amplios y movimiento reducido.
8. **Repositorio remoto sin contenido inicial.** El `HEAD` clonado no contiene archivos ni una rama válida; la etapa se inicia como trabajo nuevo en el workspace. No se atribuye historial previo.
9. **Alta de cuentas con CLI interactiva.** El Sprint 1 no crea usuarios ni contraseñas demo. Un administrador de infraestructura crea cuentas con Argon2id mediante `bin/user-create.php`; esto evita credenciales por defecto.
10. **Rate limit por correo e IP.** Cinco fallos en quince minutos bloquean nuevas comprobaciones de ese par. Los intentos quedan registrados en PostgreSQL.
11. **Permisos cargados al iniciar sesión.** El panel y futuras rutas consultan los permisos de `role_permission`; ocultar elementos de interfaz no es control de acceso.
12. **Área elegida por el trabajador.** El esquema existente requiere un área por solicitud; el formulario ofrece las áreas activas y la migración añade “Sede central” como valor inicial. Se podrán administrar áreas en un sprint posterior.
13. **Código público secuencial.** PostgreSQL asigna un número desde `request_public_number_seq` y la aplicación lo combina con el año UTC. Los huecos de numeración tras transacciones fallidas son aceptables; la restricción única impide duplicados.
14. **Evidencias privadas.** Se limita a tres archivos de 8 MiB, se valida MIME real y se guardan con clave aleatoria fuera del webroot. La descarga exige ser propietario. El archivo se borra si falla la escritura de la solicitud.
