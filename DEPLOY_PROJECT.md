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

**Estado: testing**

La implementación propuesta es Kotlin + Jetpack Compose + Room + cliente HTTPS. Requiere que Fase 2 esté aprobada y validada. La APK se validará primero en una tablet de QA y se firmará/distribuirá conforme al proceso que QA apruebe.

**Bloqueo de compilación:** el entorno de desarrollo actual no dispone de Java, Gradle ni Android SDK. QA debe habilitar un entorno de compilación Android aprobado antes de generar y verificar una APK.

## Fase 3.1: diseño visual y experiencia táctil de la APK

**Estado: testing**

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
| 3.1 | pending | Diseño responsive del listado: logo superior, conexión, botones de alta y una franja compacta con estado de carga a la izquierda y selector visual de tipo a la derecha. Dos tarjetas por fila en móvil/tablet, vertical/horizontal; cada tarjeta incorpora imagen placeholder, badge flotante de estado e iconos en una sola fila, sin acciones funcionales todavía. Validada visualmente por QA; queda `pending` por instrucción expresa de QA. |
| 3.2 | testing | Creación y edición de Elevador/ALIMAK: datos generales, checklist `OK/X/R`, observaciones, captura o selección de fotografías desde la tablet, vista previa, carga segura por API autenticada y guardado del reporte. Las fotografías se almacenan fuera de acceso web directo y se consultan únicamente mediante la API autenticada. |
| 3.3 | completed | Visualización y aprobación: detalle, instrucciones, checklist, observaciones, estado y aprobación por API. Validada por QA. |
| 3.4 | completed | Eliminación: confirmación explícita con ID/título, API, actualización automática del listado y manejo de errores. Los reportes aprobados no son eliminables en web, API ni APK. Validada por QA. |
| 3.5 | completed | Fotografías desde APK: abrir cámara del equipo, capturar una o varias imágenes y comprimir cada una a JPEG menor de 250 KB. Las nuevas fotos permanecen privadas/locales hasta Guardar; la carga autenticada, verificación, portada de card y miniaturas ocurren después. Permite eliminar fotos locales y fotos guardadas mediante API autenticada, con vista a pantalla completa y reintento ante error. Validada por QA. |
| 3.5.1 | completed | Cámara/galería, compresión local menor de 250 KB y cola local de fotografías. Validada por QA. |
| 3.5.2 | completed | Guardado real: subida, verificación autenticada y recuperación de fotografías al reabrir edición. Validada por QA. |
| 3.5.3 | completed | Portada: primera fotografía persistida en la card del listado. Validada por QA. |
| 3.5.4 | completed | Edición: miniaturas y eliminación local/remota de fotografías. Validada por QA. |
| 3.5.5 | completed | Visualización: miniaturas bajo Aprobar y visor de imagen a pantalla completa. Validada por QA. |

## Fase 4: galería fotográfica en el portal web

**Estado: testing**

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

QA autorizó la implementación técnica de esta fase; su cierre requiere validación funcional en DEMO.

### Implementación técnica realizada

- El listado web incorpora un icono de galería únicamente en los reportes que contienen fotografías.
- Las vistas `view.php` y `view_alimak.php` muestran las miniaturas inmediatamente después del bloque de instrucciones generales y antes del checklist del reporte.
- Se añadió un visor responsive con ampliación, contador, navegación circular mediante botones, teclado y gesto horizontal táctil.
- Se corrigió el visor para reportes con una sola fotografía: aunque las flechas estén ocultas, la imagen conserva la columna central y utiliza todo el espacio disponible en lugar de reducirse al tamaño de una miniatura.
- Las fotografías se entregan mediante URLs firmadas y temporales generadas en el servidor. El controlador valida firma, vencimiento, reporte, nombre permitido, pertenencia de la fotografía y tipo MIME antes de leer el archivo privado.
- La vista de un reporte aprobado incorpora `Volver a PENDIENTE`, confirmación explícita y protección CSRF de sesión.
- El servidor acepta únicamente la transición `close` a `open`, conserva el tipo Elevador/ALIMAK y no modifica datos, checklist, observaciones ni fotografías.
- Al aprobar desde la web se conserva ahora el tipo de reporte, evitando que un ALIMAK sea interpretado como Elevador al sincronizar.

La fase queda en `testing` hasta que QA valide el comportamiento en DEMO.

## Criterio de ejecución

Ninguna fase puede pasar de `pending` a ejecución por inferencia. QA debe aprobar expresamente: identificador de fase, ambiente, alcance, ventana de cambio y criterios de aceptación. Tras cada actividad autorizada se actualizará este documento con la evidencia y el resultado de QA.
