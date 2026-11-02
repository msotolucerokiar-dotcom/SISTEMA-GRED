# Sistema de diseño · CSI Soporte

## Dirección visual

La portada presenta la mesa de ayuda como un servicio público cercano y ordenado. El azul institucional domina las superficies y acciones; el dorado aparece solo en detalles discretos. La composición combina un encabezado compacto, una entrada clara al portal y tres pasos de atención. Estos colores son una paleta acordada para el sistema, no una declaración de colores corporativos oficiales.

## Fundamentos

- **Color:** azul principal `#1D375C` para encabezados y botones; azul secundario `#3A699B` para enlaces y estados activos; celeste `#EAF3FA` para avisos; blanco `#FFFFFF` para tarjetas; gris `#F5F7FA` para el fondo; dorado `#D9AF32` como acento moderado. Los errores, éxitos y advertencias usan colores semánticos y texto explícito.
- **Tipografía:** Manrope para encabezados y DM Sans para interfaz. Ambas se sirven desde Google Fonts; las familias del sistema son el fallback cuando no haya conexión.
- **Espaciado:** escala base de 4 px, controles de al menos 48 px de alto, tarjetas con 20–24 px de padding, contenido con ancho máximo de 1280 px.
- **Forma y profundidad:** radio de 8 px en controles, 16 px en tarjetas principales; bordes finos y sombras suaves reservadas para elevar superficies accionables.
- **Iconos:** SVG lineales sencillos, acompañados por texto cuando la acción no sea obvia.

## Componentes y estados

- **Botón primario:** azul institucional con texto blanco de alto contraste; hover oscurece la superficie. Foco visible de 2–3 px. El dorado no se usa como fondo principal de botones.
- **Campos:** etiqueta persistente, altura mínima de 48 px, borde neutro y ayuda junto al control. Error combina texto explícito e indicador visual; no depende solo del color.
- **Badges:** texto y color comparten el significado; no usar color como único indicador. Prioridad urgente puede sumar un icono.
- **Tablas:** encabezados claramente separados, columnas que colapsan o se desplazan en móvil, filtros antes del listado, paginación con controles táctiles.
- **Vacío/carga/error:** estado vacío con siguiente acción; carga con skeleton y texto accesible; error explica cómo reintentar y conserva los datos del formulario.
- **Confirmación/toast:** confirmación para cambios irreversibles con acción descrita; toast breve con región `aria-live`, sin ocultar errores persistentes.
- **Historial:** timeline cronológica con actor, evento y fecha legible; solución destacada como resultado.
- **Métricas:** tarjetas de resumen en dashboard con etiqueta y valor, sin depender de mini gráficos para comunicar el dato.

## Adaptación por rol

Trabajador: acceso rápido a “Nueva solicitud”, seguimiento de estados y sus solicitudes recientes. Técnico: cola asignada, prioridades, antigüedad y acciones para actualizar estado. Responsable CSI: volumen por estado, asignación pendiente, filtros, reportes y administración. La navegación y el contenido se autorizan en servidor; ocultar un enlace nunca sustituye la autorización.

## Accesibilidad

Diseño mobile-first, contraste objetivo WCAG AA, orden de tabulación natural, skip link, etiquetas asociadas, botones con área táctil de 44×44 px o mayor y `:focus-visible`. Respetar movimiento reducido para animaciones futuras. Los mensajes de validación deben anunciarse a tecnologías de asistencia.

## Aplicación y revisión

UI UX Pro Max quedó instalado para Codex en `.agents/skills/ui-ux-pro-max/`. Su sistema visual persistido está en `design-system/csi-soporte-gred-lambayeque/MASTER.md` y recomienda estilo accesible y ético, paleta navy/azul de alto contraste, Fira Sans/Fira Code, foco claro, controles táctiles amplios y respeto por movimiento reducido. Usa ese archivo como fuente para páginas nuevas y revisa primero cualquier override bajo `design-system/csi-soporte-gred-lambayeque/pages/`.

Los tokens de la portada existente viven en `tailwind.config.js`; estilos globales en `src/Presentation/Assets/css/app.css`. Esa portada precede la instalación del skill; aplica el sistema persistido al rediseñarla y a las nuevas pantallas de producto.

## Sprint 2: flujo de solicitudes

Las vistas de nueva solicitud, listado y detalle mantienen la paleta institucional ya presente. Se aplican las recomendaciones de UI UX Pro Max para formularios: etiquetas visibles, instrucciones junto al campo, errores vinculados por `aria-describedby`, foco claro y acciones de al menos 44 px. El listado ofrece estado textual y paginación; el detalle separa descripción y evidencias. La tabla permite desplazamiento horizontal en pantallas estrechas para conservar los encabezados y datos completos.

## Movimiento con GSAP

La portada usa GSAP para una entrada breve del título, texto y acciones, y ScrollTrigger para mostrar las tarjetas al entrar en pantalla. Las animaciones solo se configuran con `prefers-reduced-motion: no-preference`; el contenido permanece visible por defecto. Los archivos minificados se sirven localmente desde `public/assets/` y se regeneran con `npm run build:motion` o con el servicio `tailwind` de Docker. `@gsap/react` está instalado para vistas React futuras; las páginas PHP actuales usan GSAP directamente.

## Sprint 3: supervisión

La lista global usa una tabla con encabezados y código enlazado; el detalle presenta primero la solicitud y sus evidencias, seguido de formularios separados para clasificación y asignación. Los campos mantienen etiquetas visibles, altura táctil de 48 px, foco de teclado y mensajes de error junto a la acción. El estado y la ausencia de clasificación o responsable se expresan con texto. La tabla puede desplazarse dentro de su contenedor en pantallas pequeñas para preservar todos los datos sin desplazar la página.

## Sprint 4: atención técnica

La bandeja del técnico reutiliza la tabla y la navegación del responsable, con una página vacía que explica cuándo aparecerán casos. El detalle muestra el problema y sus evidencias antes de la acción disponible. La transición se expresa con un botón de texto claro y un formulario con etiquetas visibles, indicaciones y mensajes de error. La solución del trabajador aparece en una tarjeta separada con autor y fecha; no depende del color para comunicar que el caso fue resuelto.

## Sprint 5: historial y reportes

El historial usa una lista semántica ordenada con nombre de la acción, actor, fecha y observación; los puntos de la línea temporal son decorativos y el texto comunica toda la información. La búsqueda usa filtros con etiquetas visibles y conserva los valores al paginar. El reporte combina tarjetas de métricas, barras con cifras escritas y una tabla equivalente para no depender del color ni del gráfico. Las exportaciones tienen botones de texto explícito y estados vacíos claros.

## Sprint 6: administración de acceso

La lista de cuentas usa tarjetas legibles en móvil con nombre, correo y estado escrito. Cada cuenta tiene controles etiquetados para rol y estado, y una acción explícita para guardar. La cuenta propia informa por qué no presenta controles. Errores y confirmaciones se comunican con texto y roles accesibles, sin depender solo del color.
