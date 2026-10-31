# Capturas reales pendientes

Haz estas capturas en el navegador donde el sistema ya funciona. Guarda los PNG en esta carpeta con los nombres indicados. Las pruebas automatizadas crean y eliminan sus propias cuentas; para las capturas usa cuentas de ensayo de la interfaz o cuentas de ensayo ya disponibles. Emplea correos `@example.test` y nombres ficticios. No captures contraseñas, formularios con contraseñas escritas, datos personales reales ni información sensible de solicitudes. Desactiva las cuentas de ensayo al terminar, si no se usarán más.

Antes de empezar, comprueba que `docker compose ps` muestra `app`, `db` y `web` activos y que abres <http://localhost:8080>. Si una cuenta recién creada muestra `/change-password`, completa ese paso sin tomar una captura con las contraseñas visibles.

| Archivo | Pantalla real y pasos |
| --- | --- |
| `01-inicio-sesion.png` | Cierra sesión y abre `http://localhost:8080/`. Captura el formulario vacío de acceso. |
| `02-inicio-correcto.png` | Ingresa con una cuenta de ensayo activa. Captura `/dashboard` con el saludo y el nombre de la cuenta; la URL y el panel muestran que inició sesión. |
| `03-panel-trabajador.png` | Ingresa con `worker` y captura `/dashboard`, incluidos los accesos y el resumen de solicitudes propias. |
| `04-panel-locador.png` | Ingresa con `technician` y captura `/dashboard`, mostrando «Solicitudes asignadas». |
| `05-panel-responsable.png` | Ingresa con `csi_manager` y captura `/dashboard`, con accesos de supervisión y administración. |
| `06-administracion-usuarios.png` | Desde el Responsable CSI abre `/csi/users` y captura el botón «Crear usuario» y el listado. Usa cuentas ficticias; oculta correos si aparecen datos reales. |
| `07-crear-usuario.png` | Pulsa «Crear usuario» y captura `/csi/users/new` con campos vacíos. No escribas ni captures una contraseña. |
| `08-creacion-exitosa.png` | Crea una cuenta de ensayo con datos ficticios. Tras guardar, captura `/csi/users?created=1`, donde aparece «El usuario se creó correctamente» y el nuevo usuario en el listado. |
| `09-roles-permisos.png` | En `/csi/users`, localiza la cuenta de un técnico de ensayo. Captura los controles de rol/estado y la sección de permisos individuales. Para evidenciar concesión y revocación, toma dos capturas adicionales opcionales antes y después del cambio. |
| `10-acceso-denegado.png` | Cierra sesión del responsable, ingresa con el técnico de ensayo y abre directamente `/csi/users/new`. Captura la página 403 «Acceso restringido». |

Para evidenciar el ciclo de asignación sin compartir datos reales, registra una solicitud ficticia como trabajador; como técnico intenta asignarla antes del permiso (403), pide al responsable que active «Asignar y reasignar», vuelve a intentarlo en la misma sesión (se permite), revoca el permiso y repite (403). El flujo está cubierto automáticamente por `UserCreationFlowTest` y `CsiOperationalFlowTest`; las capturas del navegador son complementarias.

**Limitación del entorno actual:** la ejecución headless de Chrome falló al iniciar el proceso gráfico (`GPU process isn't usable`), por lo que aquí no se obtuvo ninguna captura. Además, Docker publica el portal solo en `127.0.0.1:8080`; un teléfono externo no podrá acceder a esa dirección sin un despliegue o una configuración de red adicional.
