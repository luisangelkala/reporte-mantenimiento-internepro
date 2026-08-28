# Revisión exhaustiva y plan de correcciones: Reporte de Mantenimiento Internepro

## Alcance de la revisión

Revisión estática del código PHP, JavaScript, CSS y dump SQL entregados en el directorio del proyecto. No se ejecutaron cambios técnicos, pruebas contra servidor, consultas a MariaDB ni despliegues. Todas las acciones descritas abajo están en estado `pending` y requieren aprobación explícita de QA antes de ejecutarse.

## Hallazgos y fases pendientes

| ID | Severidad | Hallazgo | Impacto | Fase propuesta | Estado |
| --- | --- | --- | --- | --- | --- |
| SEC-01 | Crítica | Credenciales MariaDB embebidas en archivos PHP y en el dump SQL. | Compromiso de base de datos si el código o respaldo se expone. | Fase 1: rotar credenciales, usar variables de entorno y restringir permisos del usuario DB. | pending |
| SEC-02 | Crítica | No hay autenticación ni autorización en el portal o `process.php`. | Terceros pueden leer, crear, editar, aprobar o borrar reportes. | Fase 1: autenticación, sesiones seguras y autorización por rol. | pending |
| SEC-03 | Crítica | Consultas SQL construidas por concatenación con datos externos. | Inyección SQL en lecturas, actualización, borrado y aprobación. | Fase 1: PDO/mysqli preparado, validación de ID entero y manejo seguro de errores. | pending |
| SEC-04 | Alta | No existe protección CSRF en acciones POST. | Un usuario autenticado futuro podría ejecutar acciones inducidas desde otro sitio. | Fase 1: tokens CSRF y verificación de origen para portal web. | pending |
| SEC-05 | Alta | Salida HTML sin escape sistemático; el aprobador se guarda sin sanitización homogénea. | XSS almacenado y alteración visual de reportes. | Fase 1: escape al renderizar (`htmlspecialchars`) y validación de entrada por contexto. | pending |
| DATA-01 | Alta | Al aprobar se sobrescribe `state_reporte` y se pierde el campo `reporte`. | Un ALIMAK aprobado puede abrir enlaces/vistas de elevador y generar avisos. | Fase 1: preservar tipo y reemplazar JSON de estado por columnas o funciones de actualización seguras. | pending |
| DATA-02 | Alta | `a_22_a` se serializa usando `$a_23_a`. | El resultado real del ítem 22 ALIMAK se pierde. | Fase 1: corregir el mapeo y añadir prueba de persistencia del checklist. | pending |
| DATA-03 | Alta | `s_8_g` se muestra en edición pero no se guarda; la vista repite `s_8_f`. | El valor de “Aceiteras” se pierde o se muestra incorrectamente. | Fase 1: añadir `s_8_g` al JSON y corregir la vista. | pending |
| DATA-04 | Media | La vista ALIMAK del ítem 34 muestra la observación `ab_33`. | Se muestra una observación incorrecta. | Fase 1: usar `ab_34` y agregar caso de prueba de presentación. | pending |
| DATA-05 | Media | La vista ALIMAK valida `a_11_a` antes de imprimir `a_11_c`. | El dato puede ocultarse ante estados parciales de datos. | Fase 1: validar la clave correcta (`a_11_c`). | pending |
| DATA-06 | Media | `updated_at` no se actualiza y fechas se almacenan como `varchar`. | Auditoría y filtrado poco fiables. | Fase 1: migración aprobada a `DATE`/`DATETIME` y actualización automática. | pending |
| DATA-07 | Media | El formulario se almacena como JSON no validado dentro de texto. | Difícil búsqueda, validación, auditoría y evolución de estructura. | Fase 2: definir esquema de datos/versionado JSON o normalización aprobada. | pending |
| DATA-08 | Alta | Un reporte aprobado podía eliminarse desde web o API. | Se pierde un registro formalmente aprobado. | Fase 3.4: bloquear borrado en web, API y APK cuando `state_reporte.status` es `close`. | testing |
| OPS-01 | Alta | No se identificó configuración separada por ambiente ni manejo de secretos. | Riesgo de publicar credenciales y dificultad de despliegue seguro. | Fase 1: archivo de configuración fuera del webroot, permisos mínimos y plantilla `.env.example` sin secretos. | pending |
| OPS-02 | Media | No se identificaron pruebas automatizadas, CI ni guía de despliegue/reversión. | Las correcciones pueden romper producción. | Fase 1: pruebas de regresión, checklist QA y runbook de despliegue/reversión. | pending |
| OPS-03 | Media | No hay control de versiones Git en el directorio analizado. | No hay trazabilidad local verificable de cambios. | Fase 0: confirmar ubicación del repositorio o inicializarlo solo con autorización de QA. | pending |
| UX-01 | Media | Las acciones destructivas se basan solo en confirmación JavaScript y realizan borrado físico. | Pérdida irreversible de reportes y sin auditoría. | Fase 1: archivado lógico, autorización, auditoría y restauración; sujeto a decisión de negocio. | pending |
| UX-02 | Baja | Metadatos HTML y comentarios aún describen un “Password generator”; existen cadenas con problemas de codificación. | Mantenimiento confuso y textos visibles degradados. | Fase 3: corregir metadatos, UTF-8 y nomenclatura sin alterar funcionalidad. | pending |
| TECH-01 | Media | `process.php` concentra conexión, reglas de negocio, serialización, HTML de listado y endpoints. | Alto acoplamiento y mayor riesgo al añadir APK/API. | Fase 2: separar configuración, repositorio, servicio y controladores/API. | pending |
| TECH-02 | Media | Tras el redireccionamiento de guardado no se termina la ejecución explícitamente. | Puede producir cuerpo JSON adicional o respuestas ambiguas. | Fase 1: centralizar respuestas HTTP y usar `exit` tras cada redirección. | pending |
| TECH-03 | Baja | Se usa jQuery 3.2.1 y recursos de diferentes generaciones de Bootstrap/Font Awesome. | Deuda técnica y posible exposición a vulnerabilidades de dependencias antiguas. | Fase 3: inventario de dependencias, actualización compatible y pruebas visuales QA. | pending |

## Evidencia técnica principal

- Conexión y credenciales repetidas: `process.php`, `edit.php`, `edit_alimak.php`, `view.php`, `view_alimak.php`.
- SQL por concatenación: selección por `id` en las vistas/edición y `DELETE`/`UPDATE` en `process.php`.
- Actualización de aprobación que pierde el tipo: función `report_aprobar` en `process.php`.
- Mapeos de checklist: función `report_insert` en `process.php`; presentación en `view.php` y `view_alimak.php`.
- Esquema y muestra de datos: `db_registros_elevadores.sql`.

## Fase 0: preparación obligatoria

**Estado: completed**

Alcance propuesto:

1. QA confirma el ambiente autorizado para evaluación y pruebas; no se utilizará producción para experimentación.
2. Inventariar versiones reales de Apache, PHP, MariaDB y Android objetivo únicamente después de autorización.
3. Definir respaldo comprobado, ventana de cambio, responsables, criterios de aceptación y plan de reversión.
4. Crear casos de prueba QA que cubran ambos tipos de reporte y sus 16/38 secciones respectivamente.
5. Centralizar la configuración de conexión en `config/db.php`, excluido de Git, sin modificar el comportamiento de la aplicación.

## Fase 1: corrección segura del sistema existente

**Estado: completed**

Precondiciones: aprobación de QA de SEC-01 a SEC-05, DATA-01 a DATA-06, OPS-01/02, UX-01 y TECH-02; respaldo validado; ambiente de prueba disponible.

Resultados esperados:

- Portal web mantiene sus URL y funcionalidad.
- Solo usuarios autenticados y autorizados pueden actuar.
- Todas las operaciones de datos usan consultas preparadas y validación de servidor.
- Los reportes existentes conservan su tipo al aprobarse.
- Los errores de mapeo se corrigen con cobertura de regresión.
- Hay trazabilidad mínima para cambios y eliminación/archivado.

## Fase 2: API para web y APK

**Estado: completed**

Crear `/api/v1` con contrato OpenAPI y respuestas JSON consistentes. Endpoints propuestos:

- `POST /auth/login`, `POST /auth/logout`, `GET /me`.
- `GET /reports`, `POST /reports`.
- `GET /reports/{id}`, `PUT /reports/{id}`.
- `POST /reports/{id}/approve`.
- `DELETE /reports/{id}` o `POST /reports/{id}/archive`, según decisión de negocio.

La API validará tipo, estado permitido, campos de checklist y permisos. No expondrá credenciales ni errores internos. El portal web podrá migrar por módulos a esta misma lógica.

## Fase 3: APK Android

**Estado: completed**

La implementación propuesta es Kotlin + Jetpack Compose + Room + cliente HTTPS. Requiere que Fase 2 esté aprobada y validada. La APK se validará primero en una tablet de QA y se firmará/distribuirá conforme al proceso que QA apruebe.

**Bloqueo de compilación:** el entorno de desarrollo actual no dispone de Java, Gradle ni Android SDK. QA debe habilitar un entorno de compilación Android aprobado antes de generar y verificar una APK.

## Fase 3.1: diseño visual y experiencia táctil de la APK

**Estado: completed**

Objetivo: trasladar la identidad visual actual de Internepro a una interfaz Android pensada para tablet y móvil, sin cambiar los contratos de API ni la funcionalidad de los reportes.

### Referencia funcional aprobada por QA

- Listado de reportes con botones `Nuevo Reporte` y `Nuevo Reporte ALIMAK`.
- Acciones por reporte: compartir por WhatsApp, ver, editar y eliminar con confirmación.
- Vista de reporte con datos generales, instrucciones, checklist, observaciones y estado de aprobación.
- Edición de datos generales y selección por actividad de los estados `OK`, `X` y `R`.

### Propuesta visual

- Encabezado fijo con logo de Internepro, título de sección y conexión/estado de sincronización.
- Paleta basada en la identidad mostrada: rojo Internepro como color principal, blanco/gris muy claro como superficie y negro/gris oscuro para texto.
- Listado en tarjetas táctiles: ID, título, tipo, fecha, estado y menú de acciones; en tablet se mostrarán en dos columnas cuando el ancho lo permita.
- Dos botones principales persistentes: `Nuevo Elevador` y `Nuevo ALIMAK`; en móvil se convierten en botones apilados o un botón flotante con selector.
- Detalle en secciones plegables: datos generales, instrucciones, checklist y observaciones. Esto evita una página excesivamente larga.
- Cada actividad usa selector segmentado grande `OK / X / R`, visible y cómodo para guantes/dedos, en lugar de menús pequeños.
- Observaciones en campos amplios por sección y barra inferior fija con `Guardar` y `Aprobar` cuando corresponda.
- Compartir mediante el selector nativo Android, que incluye WhatsApp si está instalado.
- Eliminar exige un diálogo de confirmación claro con nombre/ID del reporte y acción destructiva destacada en rojo.

### Adaptación responsive

| Contexto | Diseño |
| --- | --- |
| Tablet horizontal | Panel de datos generales en dos columnas y checklist con varias actividades visibles. |
| Tablet vertical | Una columna amplia, acciones superiores y barra inferior fija. |
| Móvil | Una columna, acciones en menú y secciones plegables. |

### Entregables y validación QA

1. Prototipo navegable de listado, detalle, edición y confirmación de borrado.
2. Integración del logo oficial proporcionado por QA, respetando sus proporciones.
3. Prueba de contraste, tamaño táctil y orientación en la tablet física final.
4. QA aprueba el diseño antes de sustituir las pantallas funcionales actuales.

## Microfases de la experiencia Android

| Microfase | Estado | Alcance |
| --- | --- | --- |
| 3.1 | completed | Diseño responsive del listado: logo superior, conexión, botones de alta y una franja compacta con estado de carga a la izquierda y selector funcional de tipo a la derecha. Dos tarjetas por fila en móvil/tablet, vertical/horizontal; cada tarjeta incorpora imagen placeholder, badge flotante de estado e iconos en una sola fila. La fila superior de cada card muestra `#ID - TIPO` a la izquierda y la fecha de creación a la derecha. Se corrigió el BUG que cambiaba la etiqueta del filtro sin filtrar la colección: `Todos`, `Elevador` y `ALIMAK` renderizan ahora únicamente los resultados correspondientes y muestran un estado vacío cuando aplica. Validada por QA; ajustes finales pendientes de prueba de regresión. |
| 3.2 | completed | Creación y edición de Elevador/ALIMAK: datos generales, bloque de fotografías antes del checklist, checklist `OK/X/R`, observaciones, captura o selección de fotografías desde la tablet, vista previa, carga segura por API autenticada y guardado del reporte. Las fotografías se almacenan fuera de acceso web directo y se consultan únicamente mediante la API autenticada. Validada por QA; nueva ubicación visual pendiente de prueba de regresión. |
| 3.3 | completed | Visualización y aprobación: detalle, instrucciones, checklist, observaciones, estado y aprobación por API. Validada por QA. |
| 3.4 | completed | Eliminación: confirmación explícita con ID/título, API, actualización automática del listado y manejo de errores. Los reportes aprobados no son eliminables en web, API ni APK. Validada por QA. |
| 3.5 | completed | Fotografías desde APK: abrir cámara del equipo, capturar una o varias imágenes y comprimir cada una a JPEG menor de 250 KB. Las nuevas fotos permanecen privadas/locales hasta Guardar; la carga autenticada, verificación, portada de card y miniaturas ocurren después. Permite eliminar fotos locales y fotos guardadas mediante API autenticada, con vista a pantalla completa y reintento ante error. Validada por QA. |
| 3.5.1 | completed | Cámara/galería, compresión local menor de 250 KB y cola local de fotografías. Validada por QA. |
| 3.5.2 | completed | Guardado real: subida, verificación autenticada y recuperación de fotografías al reabrir edición. Validada por QA. |
| 3.5.3 | completed | Portada: primera fotografía persistida en la card del listado. Validada por QA. |
| 3.5.4 | completed | Edición: miniaturas y eliminación local/remota de fotografías. Validada por QA. |
| 3.5.5 | completed | Visualización: miniaturas bajo Aprobar y visor de imagen a pantalla completa. Validada por QA. |

### Empaquetado de la versión 1.0

- La APK declara el nombre visible `Internepro Reportes` y un icono launcher generado a partir del emblema oficial `IP` incluido en el proyecto.
- La firma de QA garantiza la integridad y continuidad de las actualizaciones, pero una APK enviada por WhatsApp continúa siendo una instalación externa no distribuida por Google Play.
- Para pruebas con el cliente se recomienda Google Play Internal Testing, conservando Play Protect y el bloqueo automático del dispositivo activos.

## Fase 4: galería fotográfica en el portal web

**Estado: completed**

Objetivo: incorporar en la web la consulta visual de las fotografías ya asociadas a cada reporte y permitir que un reporte aprobado vuelva controladamente a estado `PENDIENTE` para corregirlo desde la APK, sin exponer credenciales o archivos privados.

### Alcance funcional

- En cada fila del listado de reportes se añadirá un icono de fotografías cuando el reporte tenga al menos una imagen asociada.
- Al pulsar el icono se abrirá un popup tipo galería con la fotografía ampliada, contador de posición, botón de cierre y flechas para avanzar o retroceder entre todas las imágenes del mismo reporte.
- En las vistas de visualización de reportes Elevador y ALIMAK se mostrará una sección de miniaturas.
- Al pulsar una miniatura se abrirá la misma galería ampliada y se podrá recorrer el resto de las fotografías mediante flechas.
- El popup será responsive para escritorio, tablet y móvil. Admitirá cierre con `Escape`, navegación con flechas del teclado y controles táctiles visibles.
- Si una fotografía fue eliminada o no puede cargarse, la web mostrará un aviso controlado sin romper el listado ni la vista del reporte.
- En la vista web de visualización de un reporte aprobado se añadirá la acción `Volver a PENDIENTE`.
- La acción exigirá una confirmación explícita que informe que el reporte volverá a estar disponible para edición en la APK.
- Al confirmar, el reporte pasará de aprobado/cerrado a pendiente/abierto y se retirará su aprobación vigente, sin eliminar datos, actividades, observaciones ni fotografías.
- La APK deberá reconocer el nuevo estado después de actualizar el listado y permitir nuevamente la edición del reporte.

### Seguridad e integración

- El token Bearer técnico no se incluirá en HTML ni JavaScript del navegador.
- Las imágenes serán entregadas por un controlador PHP del servidor que validará el ID entero del reporte, el nombre permitido del archivo y que la fotografía pertenezca a ese reporte.
- Se mantendrá bloqueado el acceso web directo al directorio privado de fotografías.
- La galería será de solo lectura; esta fase no incorpora carga ni eliminación de fotografías desde la web.
- El cambio de estado se realizará mediante una operación validada en el servidor; no se confiará el estado solicitado directamente al navegador.
- Solo se permitirá la transición de un reporte existente en estado aprobado/cerrado a pendiente/abierto. Las solicitudes inválidas devolverán un error controlado y no modificarán el reporte.

### Criterios de aceptación QA

1. Un reporte con fotos muestra el icono en su fila; uno sin fotos no ofrece una galería vacía.
2. El popup muestra exclusivamente las fotos del reporte seleccionado.
3. Las flechas anterior/siguiente, el contador y el cierre funcionan correctamente con una o varias imágenes.
4. Las vistas de Elevador y ALIMAK muestran miniaturas y permiten ampliarlas.
5. La galería se adapta a escritorio, tablet, móvil y a ambas orientaciones.
6. Ningún token o secreto queda visible en el código entregado al navegador y el acceso directo al almacenamiento continúa denegado.
7. Un archivo ausente o inválido genera un mensaje controlado y no expone rutas internas del servidor.
8. La acción `Volver a PENDIENTE` aparece en la visualización de un reporte aprobado y solicita confirmación antes de ejecutar el cambio.
9. Al confirmar, el reporte queda pendiente sin perder campos, checklist, observaciones ni fotografías.
10. Después de recargar/sincronizar la APK, el reporte aparece pendiente y puede editarse y guardarse nuevamente.
11. La acción no puede aplicarse a un reporte inexistente o que ya esté pendiente, y cualquier fallo mantiene intacto el estado anterior.

QA autorizó la implementación técnica y validó funcionalmente esta fase en DEMO.

### Implementación técnica realizada

- El listado web incorpora un icono de galería únicamente en los reportes que contienen fotografías.
- Las vistas `view.php` y `view_alimak.php` muestran las miniaturas inmediatamente después del bloque de instrucciones generales y antes del checklist del reporte.
- Se añadió un visor responsive con ampliación, contador, navegación circular mediante botones, teclado y gesto horizontal táctil.
- Se corrigió el visor para reportes con una sola fotografía: aunque las flechas estén ocultas, la imagen conserva la columna central y utiliza todo el espacio disponible en lugar de reducirse al tamaño de una miniatura.
- Las fotografías se entregan mediante URLs firmadas y temporales generadas en el servidor. El controlador valida firma, vencimiento, reporte, nombre permitido, pertenencia de la fotografía y tipo MIME antes de leer el archivo privado.
- La vista de un reporte aprobado incorpora `Volver a PENDIENTE`, confirmación explícita y protección CSRF de sesión.
- El servidor acepta únicamente la transición `close` a `open`, conserva el tipo Elevador/ALIMAK y no modifica datos, checklist, observaciones ni fotografías.
- Al aprobar desde la web se conserva ahora el tipo de reporte, evitando que un ALIMAK sea interpretado como Elevador al sincronizar.

Fase validada por QA y cerrada en estado `completed`.

## Fase 5: evidencia fotográfica estructurada, PDF y nueva identidad

**Estado: testing**

Objetivo: ampliar la evidencia de mantenimiento permitiendo comentarios opcionales por fotografía, separar las fotos generales de las fotos asociadas a secciones específicas, generar un PDF definitivo al aprobar y compartir ese PDF desde la APK. Esta fase también incorpora el nuevo logo oficial.

### Microfases

| Microfase | Estado | Alcance |
| --- | --- | --- |
| 5.1 | completed | Fotografías generales: cada foto puede tener un comentario opcional y se permite un máximo de cinco fotos por reporte. APK, API y backend validan el límite; no es suficiente ocultar el botón en la interfaz. Implementación técnica, persistencia corregida y validación funcional de QA completadas. |
| 5.2 | completed | Fotografías por sección: las seis secciones ALIMAK autorizadas tienen su propio botón de cámara/galería, máximo independiente de cinco fotos y comentario opcional por cada fotografía. La asociación usa la clave técnica para evitar ambigüedad entre títulos repetidos. Implementación compilada y validada funcionalmente por QA en DEMO. |
| 5.3 | completed | Persistencia y visualización: el modelo/API conserva nombre, comentario, ámbito (`general` o `section`), clave de sección y fecha de carga. APK y web presentan grupos, miniaturas, comentarios y visor correspondiente. Validada funcionalmente por QA en DEMO. |
| 5.4 | testing | PDF de aprobación: al aprobar, el backend genera y registra de forma transaccional un PDF completo con datos, instrucciones, checklist, observaciones, fotos generales y fotos de las seis secciones con sus comentarios. Implementación terminada; pendiente de validación funcional de QA en DEMO. |
| 5.5 | testing | Consulta y compartir PDF: APK y web muestran iconos PDF y WhatsApp únicamente activos cuando el reporte está aprobado y existe un PDF vigente. La API entrega una URL firmada temporal sin exponer el token Bearer. Implementación compilada; pendiente de validación funcional de QA en DEMO. |
| 5.6 | pending | Paridad fotográfica en la web: reacomodar las fotos generales con sus comentarios y añadir dentro de cada reporte ALIMAK los bloques fotográficos de las seis secciones autorizadas, siguiendo la misma organización, límites y reglas de edición de la APK. |
| 5.7 | pending | Nueva identidad visual: sustituir el logo en web, APK, icono launcher y PDF usando los archivos oficiales que entregue QA, respetando proporciones, fondo y variantes aprobadas. |

### Implementación técnica 5.1

- La APK presenta el bloque `Fotografías generales` con contador visible `0/5` a `5/5`.
- Cámara y galería aceptan únicamente los espacios disponibles; al alcanzar cinco se deshabilitan ambos botones.
- Cada fotografía nueva y guardada dispone de un comentario opcional editable de hasta 500 caracteres. La ausencia de comentario no bloquea el guardado.
- Los comentarios de fotografías guardadas se conservan en `_photos` junto con nombre, fecha de carga y ámbito `general`.
- La API acepta el comentario vacío, valida como máximo 500 caracteres, limita a cinco las fotografías generales y bloquea fotografías nuevas en reportes aprobados.
- La comprobación del límite se ejecuta bajo bloqueo transaccional del reporte para impedir que cargas simultáneas superen cinco.
- Las fotografías históricas sin comentario continúan siendo legibles y pueden conservarse sin completar el comentario.
- Evidencia local: `:app:compileDebugKotlin` finalizó con `BUILD SUCCESSFUL`. La prueba funcional contra API, MariaDB y almacenamiento de DEMO queda a cargo de QA.
- Las microfases 5.2 a 5.7 no forman parte de esta implementación y permanecen en `pending`.
- Corrección durante `testing`: los comentarios de fotos nuevas ya no dependen únicamente del envío multipart. Tras cargar y verificar los archivos, la APK sincroniza nuevamente `_photos`, guarda el reporte y lo relee desde la API; si nombre y comentario no coinciden con lo enviado, muestra un error y no informa el guardado como exitoso.

### Reglas funcionales de fotografías

- El comentario de cada fotografía será opcional; si se completa, admitirá como máximo 500 caracteres.
- El bloque general admitirá de cero a cinco fotografías.
- Cada una de las seis secciones habilitadas admitirá de cero a cinco fotografías independientes.
- El máximo posible para un reporte ALIMAK será de treinta y cinco fotografías: cinco generales y treinta distribuidas entre seis secciones.
- Los límites se comprobarán tanto en la APK como en la API para impedir cargas adicionales mediante solicitudes directas o reintentos simultáneos.
- Las fotos pendientes podrán añadirse, comentarse o eliminarse antes de guardar. Las fotos persistidas y sus comentarios podrán editarse o eliminarse mientras el reporte permanezca pendiente.
- Un reporte aprobado conservará bloqueadas sus fotografías y comentarios. Para corregirlos deberá utilizarse `Volver a PENDIENTE` desde la web.
- Las fotografías existentes anteriores a esta fase se tratarán como generales y con comentario vacío; completarlo será opcional y la migración no eliminará archivos existentes.
- Cada foto continuará comprimida por la APK al límite vigente de menos de 250 KB y almacenada fuera del acceso web directo.

### Implementación técnica 5.2

- El modelo Android identifica cada sección del checklist mediante su clave estable y conserva `scope`, `section_key`, comentario y fecha de carga por fotografía.
- Los bloques fotográficos aparecen dentro de CABINA `a_2`, CONTROL `a_9`, CREMALLERA `a_15`, PARACAÍDAS `a_22`, PUERTAS DE PASILLO `a_28` y FOSO `a_32`.
- Cada bloque presenta cámara, galería, contador independiente `0/5`, compresión local, miniaturas, comentario opcional, reintento y eliminación.
- Las fotos generales continúan separadas y únicamente una foto general puede utilizarse como portada de la card.
- La API acepta `scope=section` únicamente para reportes ALIMAK y para las seis claves autorizadas; rechaza otros ámbitos, otras secciones y el sexto archivo de cualquier bloque.
- El bloqueo transaccional del reporte evita superar cinco mediante cargas simultáneas. La sincronización final conserva todos los bloques sin sobrescribir sus fotografías.
- Evidencia local: `:app:compileDebugKotlin` finalizó con `BUILD SUCCESSFUL`. La validación contra API, MariaDB y almacenamiento de DEMO queda pendiente de QA.
- La visualización agrupada fuera del editor se implementa y valida de forma independiente en 5.3.

### Implementación técnica 5.3

- La vista de visualización de la APK presenta `Fotografías generales` y, dentro de cada sección ALIMAK autorizada, sus fotografías correspondientes.
- Cada miniatura muestra el comentario o `Sin comentario`; al abrirla, el visor a pantalla completa muestra grupo, comentario, posición y flechas para recorrer únicamente las fotos del mismo grupo.
- La web interpreta `scope`, `section_key` y `comment`, y separa la evidencia en generales, CABINA, CONTROL, CREMALLERA, PARACAÍDAS, PUERTAS DE PASILLO y FOSO.
- Las miniaturas web muestran el comentario. El visor web conserva flechas, teclado, gesto táctil y ahora presenta grupo y comentario de la fotografía activa.
- Las fotografías y sus comentarios permanecen disponibles dentro de la vista del reporte. Por solicitud posterior de QA, el icono de galería se retiró del listado web para reservar esa fila a PDF, WhatsApp, visualización y borrado.
- Los registros históricos sin `scope`, `section_key` o `comment` continúan tratándose como fotografías generales con comentario vacío.
- La edición web de fotografías no forma parte de 5.3; carga, modificación, eliminación y límites visuales en web permanecen reservados para 5.6.
- Evidencia local: `:app:compileDebugKotlin` finalizó con `BUILD SUCCESSFUL`; las pruebas funcionales web y APK en DEMO quedan pendientes de QA.
- Corrección solicitada durante `testing`: la vista ALIMAK muestra únicamente las fotos generales bajo las instrucciones y coloca las fotos `a_2`, `a_9`, `a_15`, `a_22`, `a_28` y `a_32` dentro de la fila de su sección correspondiente, evitando una galería única mezclada.
- El icono de edición se oculta del listado web para todos los reportes. Las rutas y la funcionalidad técnica de edición permanecen intactas para la futura fase 5.6.
- QA validó la presentación agrupada en APK y web; la microfase queda cerrada en estado `completed`.

### Implementación técnica 5.4

- Se incorporó un generador PDF autocontenido en el backend, sin dependencias de Composer ni servicios externos. Produce documentos A4 multipágina con identificación del reporte, datos generales, aprobador, fecha, nomenclatura, checklist completo, observaciones y evidencia fotográfica con comentarios.
- Las fotos generales se imprimen antes del checklist. Las fotos ALIMAK de CABINA, CONTROL, CREMALLERA, PARACAÍDAS, PUERTAS DE PASILLO y FOSO se imprimen dentro de su sección correspondiente; no se mezclan en una galería única.
- Las fotografías JPEG se incorporan directamente. Para archivos históricos PNG o WEBP el servidor utiliza GD para convertirlos a JPEG. Si GD no está disponible, o si falta o es inválida una fotografía registrada, la aprobación falla de forma controlada y conserva el reporte pendiente; no se emite un PDF incompleto.
- La API y la aprobación web utilizan una única operación compartida con bloqueo `FOR UPDATE` y transacción MariaDB. El archivo se genera primero y el estado solo cambia a `close` cuando el PDF quedó escrito y registrado; cualquier fallo revierte la base de datos y elimina el archivo incompleto.
- Cada versión se almacena en `storage/report-pdfs/{id}` bajo un nombre interno aleatorio y permisos privados. `storage/.htaccess` continúa denegando el acceso HTTP directo.
- `state_reporte.pdf` registra `name`, `generated_at`, `version` y `status=active`. Al usar `Volver a PENDIENTE`, la versión queda `invalidated` con fecha de invalidación; una aprobación posterior incrementa la versión y genera un archivo nuevo.
- La web muestra un error controlado cuando no puede generarse el documento. La API devuelve `500` sin cerrar el reporte; también conserva respuestas diferenciadas para reporte inexistente, datos inválidos o reporte previamente aprobado.
- El archivo físico generado en 5.4 permanece privado. La URL firmada y las acciones de consulta/WhatsApp fueron incorporadas posteriormente como parte de 5.5.
- Se mantiene la identidad textual actual en el documento. La sustitución por el nuevo logo oficial continúa en 5.7 y exige el recurso gráfico aprobado por QA.
- Evidencia local: revisión estática y `git diff --check` sin errores de espacios. Este equipo no dispone de ejecutable PHP, por lo que el lint PHP y la prueba real de generación con MariaDB/almacenamiento deben ejecutarse en DEMO por QA.

### Implementación técnica 5.5

- Se corrigió el fallo silencioso de aprobación en la APK: cualquier error devuelto por el backend ahora aparece debajo del botón, y mientras se genera el documento se muestra `Generando y verificando PDF...`. La interfaz solo confirma la aprobación cuando la API devuelve el reporte cerrado con su PDF.
- La API incorpora `GET /reports/{id}/pdf` y también añade `state_reporte.pdf.url` a los listados, detalles y respuesta de aprobación cuando existe una versión activa.
- `pdf.php` entrega el documento sin Bearer mediante una URL firmada con HMAC, reporte, nombre interno y vencimiento. Antes de leer el archivo vuelve a comprobar en MariaDB que el reporte continúe aprobado, que la versión sea la activa y que el archivo exista.
- La vigencia inicial del enlace es de siete días. Cada nueva consulta a la API o carga del listado web genera un enlace actualizado; no se guarda la URL temporal en MariaDB.
- Volver a `PENDIENTE` invalida inmediatamente cualquier enlace emitido, aunque todavía no haya llegado su vencimiento. Una aprobación posterior crea otra versión y otro enlace.
- Las cards APK incluyen un icono PDF para abrir el documento remoto y un icono oficial de WhatsApp que comparte el texto `Reporte de mantenimiento #ID: URL`. Ambos quedan grises y deshabilitados sin PDF vigente.
- El listado web retiró el icono de fotografías. Cada fila muestra PDF y WhatsApp habilitados para reportes aprobados con PDF; en reportes pendientes o aprobados antiguos sin documento aparecen deshabilitados.
- La URL no contiene ni revela `INTERNEPRO_API_TOKEN`. El archivo continúa almacenado bajo `storage/report-pdfs` y no puede abrirse por su ruta física.
- Evidencia local Android: `:app:compileDebugKotlin` finalizó con `BUILD SUCCESSFUL`. El lint y las pruebas funcionales PHP continúan a cargo de QA en DEMO porque localhost no dispone de PHP CLI.

### Secciones ALIMAK autorizadas para fotografías (Microfase 5.2)

| Orden | Sección visible | Clave de sección | Observación | Actividades incluidas |
| --- | --- | --- | --- | --- |
| 1 | CABINA | `a_2` | `ab_2` | `a_2_a`: Estado de los paneles de la cabina: limpieza y golpes. |
| 2 | CONTROL | `a_9` | `ab_9` | `a_9_a`–`a_9_h`: Contactores, Auxiliares, Breaker, Relay, Temporizadores, Conexiones, ACL y Tarjeta com. |
| 3 | CREMALLERA | `a_15` | `ab_15` | `a_15_a`–`a_15_c`: Piñón, Cremallera y Contrarrueda. |
| 4 | PARACAÍDAS | `a_22` | `ab_22` | `a_22_a`: Prueba paracaídas fecha. |
| 5 | PUERTAS DE PASILLO | `a_28` | `ab_28` | `a_28_a`: Puertas de pasillo: estado y limpieza. |
| 6 | FOSO | `a_32` | `ab_32` | `a_32_a`: Stop de foso. |

Reglas de asociación:

- Las fotos se asociarán a la clave estable (`a_2`, `a_9`, `a_15`, `a_22`, `a_28` o `a_32`) y no únicamente al título visible.
- `CABINA a_2` no debe mezclarse con `CABINA a_4`, `a_18` o `a_34`.
- `CREMALLERA a_15` no debe mezclarse con `CREMALLERA a_23`.
- Cada bloque mostrará sus botones de cámara/selección, contador `0/5`, miniaturas y comentario opcional por foto dentro de la tarjeta de la sección correspondiente.
- Las fotografías de estas secciones se guardarán y mostrarán en el mismo orden definido en esta tabla.

### Requisitos específicos de fotografías en la web (Microfase 5.6)

- Las vistas web de edición y visualización de reportes ALIMAK tendrán la misma separación de evidencias que la APK: `Fotografías generales` y un bloque independiente para cada una de las seis secciones seleccionadas.
- El bloque de fotografías generales se reacomodará para presentar cada miniatura junto con su comentario, conservando el visor ampliado existente.
- Las fotos de sección aparecerán dentro o inmediatamente después de la sección de mantenimiento a la que pertenecen, nunca agrupadas como fotos generales.
- En reportes pendientes, la edición web permitirá añadir, comentar, modificar el comentario y eliminar fotografías, aplicando el máximo de cinco por bloque.
- En reportes aprobados, la web mostrará fotos y comentarios en modo de solo lectura. Para modificarlos será obligatorio volver el reporte a `PENDIENTE`.
- La web y la APK consumirán el mismo modelo y los mismos endpoints; una modificación guardada en cualquiera de los dos clientes deberá verse correctamente en el otro después de actualizar.
- El listado web no mostrará el icono de galería; las imágenes agrupadas y comentarios se consultarán desde la vista del reporte. Esta decisión no elimina el visor interno ni la futura edición fotográfica de 5.6.
- La API será la autoridad de los límites y de la asociación entre reporte, sección, fotografía y comentario, evitando que una solicitud web pueda mezclar evidencias o superar cinco fotos.

### Reglas del PDF y aprobación

- La aprobación solo responderá como exitosa cuando el PDF haya sido generado y registrado correctamente; si la generación falla, el reporte permanecerá pendiente y se devolverá un error controlado.
- El PDF representará una captura lógica del reporte aprobado e incluirá el nuevo logo, título, cliente, fecha, equipo, técnico, instrucciones, checklist, observaciones y todas las fotografías agrupadas con sus comentarios.
- El backend almacenará el PDF fuera del acceso directo y registrará al menos reporte, nombre interno, fecha de generación, versión y estado vigente.
- La descarga se realizará mediante un controlador backend con un identificador no predecible o firma específica para el PDF; nunca se incluirá la credencial técnica de la API en la URL compartida.
- Si un reporte vuelve a `PENDIENTE`, el PDF anterior dejará de ser compartible y el botón Enviar se deshabilitará. Una nueva aprobación generará una versión nueva y reemplazará el enlace activo.
- Solo un reporte con estado aprobado y PDF confirmado podrá compartirse. Si el archivo falta, la card mostrará la acción deshabilitada y un mensaje controlado.

### Requisitos pendientes de definición por QA

1. Confirmar si los reportes Elevador también tendrán secciones con fotografías; de ser así, QA deberá indicar sus nombres y claves exactas en una ampliación posterior.
2. Política definitiva de conservación o eliminación de versiones anteriores del PDF. La vigencia inicial del enlace compartido queda establecida en siete días.
3. Archivo del nuevo logo en PNG transparente de alta resolución y, preferiblemente, SVG; variantes para fondo claro/oscuro si existen.

### Criterios de aceptación QA

1. Se puede guardar una fotografía nueva sin comentario, pero no superar cinco fotos en ningún bloque ni 500 caracteres por comentario.
2. Las fotos generales y las de cada sección permanecen correctamente separadas después de guardar, cerrar y reabrir el reporte.
3. Web y APK muestran miniatura, comentario y sección correctos sin mezclar evidencias.
4. Los reportes y fotos existentes continúan disponibles después de la migración.
5. Una aprobación válida genera un PDF legible y completo antes de cerrar el reporte.
6. El PDF incluye todas las fotos en su grupo correspondiente y cada comentario asociado.
7. El botón Enviar permanece deshabilitado en reportes pendientes o sin PDF y se habilita únicamente tras una aprobación completa.
8. WhatsApp recibe la URL del PDF vigente y un tercero autorizado puede abrirla sin conocer el token Bearer.
9. Al volver a pendiente se invalida el enlace anterior; al aprobar nuevamente se genera y comparte una versión actualizada.
10. Las vistas web de edición y visualización reproducen los bloques generales y por sección, con comentarios, límites y estados de edición equivalentes a la APK.
11. La galería del listado web identifica el grupo/sección y muestra el comentario correspondiente a cada fotografía.
12. El nuevo logo aparece correctamente en web, APK, launcher y PDF en móvil/tablet y sobre los fondos aprobados.

La Fase 5 queda en `testing`: 5.1, 5.2 y 5.3 están `completed`; 5.4 y 5.5 están implementadas en `testing` y esperan validación funcional de QA. Las microfases 5.6 y 5.7 permanecen en `pending` y no han sido implementadas.

## Criterio de ejecución

Ninguna fase puede pasar de `pending` a ejecución por inferencia. QA debe aprobar expresamente: identificador de fase, ambiente, alcance, ventana de cambio y criterios de aceptación. Tras cada actividad autorizada se actualizará este documento con la evidencia y el resultado de QA.
