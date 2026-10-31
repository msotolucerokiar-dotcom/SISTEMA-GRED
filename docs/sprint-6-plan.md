# Sprint 6 · verificación y entrega

Objetivo: revisar los flujos completos de los tres roles, corregir fallos de seguridad y dejar instrucciones de operación y entrega reproducibles.

Archivos: autenticación y sesión, repositorio de usuarios, rutas HTTP, configuración Nginx/Docker si procede, pruebas unitarias e integración, README y documentación de decisiones. Durante la revisión del backlog se detectó que HU02 carece de gestión de roles en la interfaz: se añade una lista de cuentas y cambio de rol/estado para `user.manage_roles`, con protección de la última cuenta responsable. Tablas existentes: `users`, `roles`, `role_permission`, `requests`, `request_attachments`, `request_history`, `request_solutions`; no se prevé migración.

Comprobaciones: sesiones revocadas tras desactivar usuario o cambiar rol/permisos; CSRF y acceso a adjuntos y exportaciones; cabeceras de seguridad; entradas inválidas; sintaxis PHP, pruebas PHPUnit, contenedores sanos y respuesta HTTP real. Verificar que `.env` y archivos locales de credenciales no se publiquen.
