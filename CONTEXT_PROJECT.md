# Contexto del proyecto: Reportes Internepro

## Gobierno del proyecto

Aplicación de reportes operativos de Internepro desplegada sobre Linux, Apache, PHP y MariaDB. El proyecto dispone de portal web, API REST y aplicación Android nativa.

QA controla el ciclo de cambios. Ningún desarrollo, despliegue, compilación para distribución, migración ni prueba contra la VPS puede ejecutarse sin su autorización expresa.

Este documento es la única fuente activa para requisitos, trabajo detectado y fases de implementación. El historial de cambios terminados se conserva en Git y no se repite aquí.

## Estado funcional actual

- Portal web PHP para listar, crear, visualizar, aprobar, reabrir y eliminar reportes según su estado.
- API REST en `/api/v1`, autenticada mediante credencial técnica Bearer almacenada fuera del repositorio.
- Aplicación Android nativa en Kotlin y Jetpack Compose para móvil y tablet.
- Tipos de reporte actualmente operativos: `elevador` y `alimak`.
- Fotografías generales y fotografías asociadas a seis secciones ALIMAK, con comentario opcional, compresión y almacenamiento privado.
- Los reportes aprobados quedan bloqueados para edición y eliminación hasta que la web ejecute `Volver a PENDIENTE`.
- La aprobación genera un PDF privado y versionado; web y APK permiten abrirlo y compartir su URL temporal mediante WhatsApp.
- Configuración MariaDB en `config/db.php` y credencial API en `config/auth.php`, ambos excluidos de Git.
- La web continúa siendo un canal operativo; la APK es el canal principal para el trabajo de campo.

## Arquitectura vigente

```text
Portal web PHP -----------------------+
                                      |
APK Android (Kotlin/Compose) --> API PHP /api/v1 --> MariaDB: reporte
                                      |
                                      +--> storage privado de fotos y PDF
```

Principios vigentes:

- La APK nunca se conecta directamente a MariaDB.
- La API es la autoridad de validación, estado, límites y permisos.
- Los secretos no se versionan ni se entregan al navegador.
- Las fotos y los PDF no se exponen mediante rutas físicas públicas.
- Los tipos Elevador y ALIMAK no pueden degradarse al incorporar nuevos tipos.

## Nuevo alcance activo: reporte de Llamada

QA solicita un tercer tipo de reporte llamado `Llamada`, basado en el formato físico “Reporte de trabajo, mantenimiento y correctivos”. Se implementará y probará primero en web; la APK se desarrollará únicamente después de la validación web.

Campos identificados en el formato:

- Cliente.
- Equipo.
- Fecha.
- Trabajo realizado.
- Motivo.
- Piezas reemplazadas.
- Observaciones y recomendaciones.
- Espacio de conformidad o firma de `La empresa`.
- Espacio de conformidad o firma de `Cliente`.

El reporte tendrá un único bloque de fotografías generales al inicio y no tendrá fotografías por sección.

## Flujo de estados de implementación

Los identificadores `PR-###` avanzan solamente mediante este flujo:

```text
PENDING
   |
   | autorización expresa de QA para desarrollar
   v
DEVELOPING
   |
   | Dev termina código y verificaciones locales
   +--------------------------+
   |                          |
   v                          v
DEPLOYMENT WEB        DEPLOYMENT & COMPILING
(cambio web/API)      (cambio de la App)
   |                          |
   +------------+-------------+
                | QA despliega o compila/instala
                v
             TESTING
                |
                | QA valida el comportamiento
                v
             COMPLETED
```

Definición y responsable de cada estado:

| Estado | Significado | Responsable de la siguiente acción |
| --- | --- | --- |
| `PENDING` | Requisito documentado, pero Dev no está autorizado a modificar código. | QA autoriza. |
| `DEVELOPING` | QA autorizó y Dev está implementando/verificando el código. | Dev. |
| `DEPLOYMENT WEB` | Dev terminó un cambio web/API y lo dejó listo para subir a la VPS. | QA despliega. |
| `DEPLOYMENT & COMPILING` | Dev terminó un cambio Android y lo dejó listo para compilar, instalar o generar APK. | QA compila e instala. |
| `TESTING` | QA confirmó el despliegue o compilación e inició las pruebas funcionales. | QA prueba. |
| `COMPLETED` | QA validó el alcance completo del PR. | Cerrado. |

Reglas:

- Dev no puede mover un PR de `PENDING` a `DEVELOPING` sin autorización expresa de QA.
- Al finalizar código web/API, Dev lo mueve a `DEPLOYMENT WEB`; este estado no significa que la VPS haya sido actualizada.
- Al finalizar código Android, Dev lo mueve a `DEPLOYMENT & COMPILING`; este estado no significa que QA haya generado o instalado el APK.
- Solo QA comunica el paso a `TESTING` y posteriormente a `COMPLETED`.
- Si QA detecta un bug durante `TESTING`, el PR vuelve a `DEVELOPING` conservando el mismo identificador y se registra un ISSUE nuevo cuando el defecto sea distinto del trabajo ya descrito.

### Trazabilidad simplificada con Git

- `PR-###` es una fase interna de implementación documentada en este archivo; no se crearán Pull Requests en GitHub.
- Cada PR tendrá un mensaje de commit principal definido antes de comenzar.
- Todos los commits adicionales de una corrección conservarán el identificador `PR-###` y mencionarán el `ISSUE-###` correspondiente.
- Cuando Dev termine el código, realizará el commit principal, obtendrá su SHA y lo registrará en la tabla `Registro Git por PR`.
- Como un commit no puede contener su propio SHA, el identificador del commit principal se añadirá mediante un segundo commit exclusivamente documental con el formato `docs(PR-###): registrar evidencia del commit SHA`.
- QA desplegará o compilará el SHA documentado. El estado no pasará a `TESTING` hasta que QA confirme que esa revisión exacta está instalada en DEMO o en el dispositivo.
- No se reescribirá ni sustituirá un commit ya entregado a QA; cualquier corrección generará un SHA nuevo asociado al mismo PR y al ISSUE correspondiente.

Ejemplo:

```text
Commit principal:
  a1b2c3d  feat(PR-002): incorporar reporte Llamada en backend y API

Commit documental:
  d4e5f6g  docs(PR-002): registrar evidencia del commit a1b2c3d

Registro del PR:
  PR-002 -> REQ-001 -> ISSUE-001 -> commit a1b2c3d
```

## REQUIREMENTS

### REQ-001 — Incorporar el tipo Llamada

**Comportamiento requerido:** el sistema debe reconocer `llamada` como tercer tipo de reporte sin modificar el comportamiento de `elevador` y `alimak`. La etiqueta visible será `Llamada`.

**ISSUES relacionados:** `ISSUE-001`, `ISSUE-009`.

### REQ-002 — Crear Llamada desde la web

**Comportamiento requerido:** el listado web debe mostrar un tercer botón llamado exactamente `Llamada`. Al pulsarlo debe crear un reporte pendiente de tipo `llamada` y abrir el formulario correspondiente. El título será automático con formato `LLAMADA - CLIENTE - FECHA`; antes de disponer de esos datos se mostrará `LLAMADA #ID`.

**ISSUES relacionados:** `ISSUE-002`, `ISSUE-010`.

### REQ-003 — Capturar los campos del formato

**Comportamiento requerido:** el formulario debe capturar, guardar y recuperar Cliente, Equipo, Fecha, Trabajo realizado, Motivo, Piezas reemplazadas y Observaciones y recomendaciones, preservando los saltos de línea de los campos extensos.

**ISSUES relacionados:** `ISSUE-003`.

### REQ-004 — Representar la conformidad de empresa y cliente

**Comportamiento requerido:** la web y la APK deben proporcionar áreas de firma manuscrita digital para `La empresa` y `Cliente`, y la visualización/PDF deben mostrar las firmas existentes. En este alcance las firmas son opcionales y su ausencia no bloquea el guardado ni la aprobación.

**ISSUES relacionados:** `ISSUE-004`.

### REQ-005 — Incorporar fotografías generales

**Comportamiento requerido:** Llamada debe tener un solo bloque de fotografías generales al inicio del reporte, con un máximo de cinco imágenes. No debe crear grupos por sección. Las fotos deben admitir comentario opcional, miniatura, ampliación y eliminación mientras el reporte esté pendiente. La web permitirá cargar y eliminar fotos únicamente para Llamada; esta excepción no modifica Elevador ni ALIMAK.

**ISSUES relacionados:** `ISSUE-005`, `ISSUE-006`.

### REQ-006 — Mantener las reglas de estado

**Comportamiento requerido:** un reporte Llamada pendiente puede modificarse y eliminarse. Después de aprobarlo, web, API y APK deben impedir su modificación, eliminación y cambios fotográficos hasta que sea devuelto a `PENDIENTE` desde la web.

**ISSUES relacionados:** `ISSUE-007`.

### REQ-007 — Mantener las acciones del listado

**Comportamiento requerido:** cada fila de Llamada debe disponer de las mismas acciones aplicables a los otros tipos: PDF, WhatsApp, visualizar y eliminar. Cada acción debe habilitarse o deshabilitarse de acuerdo con el estado y la existencia del PDF.

**ISSUES relacionados:** `ISSUE-008`.

### REQ-008 — Generar y compartir el PDF

**Comportamiento requerido:** al aprobar una Llamada, el backend debe generar un PDF con el logo, título, todos los campos, la conformidad definida, fotos generales y comentarios. Solo después de generar un PDF válido se habilitarán PDF y WhatsApp.

**ISSUES relacionados:** `ISSUE-008`, `ISSUE-011`.

### REQ-009 — Priorizar la implementación web

**Comportamiento requerido:** el ciclo web y API del reporte Llamada debe implementarse, desplegarse y ser validado por QA antes de comenzar la interfaz equivalente en la APK.

**ISSUES relacionados:** `ISSUE-012`.

### REQ-010 — Incorporar Llamada en la APK

**Comportamiento requerido:** después de la aprobación web, la APK debe permitir listar, filtrar, crear, editar, fotografiar, visualizar, aprobar, abrir PDF y compartir reportes Llamada con paridad funcional y diseño responsive.

**ISSUES relacionados:** `ISSUE-009`, `ISSUE-013`.

### REQ-011 — Proteger compatibilidad y seguridad

**Comportamiento requerido:** incorporar Llamada no debe romper Elevador/ALIMAK ni debilitar autenticación, CSRF, consultas preparadas, almacenamiento privado, URLs firmadas, bloqueo de aprobados o compatibilidad con versiones anteriores de la APK.

**ISSUES relacionados:** `ISSUE-009`, `ISSUE-014`.

## ISSUES

### ISSUE-001 — El modelo y la API no reconocen Llamada

**Tipo:** trabajo funcional.

**Estado:** `IMPLEMENTED IN PR-002 — PENDING QA`.

**Trabajo existente:** ampliar la lista de tipos, serialización, deserialización y enrutamiento para aceptar `llamada` sin interpretar el registro como Elevador.

**REQ relacionados:** `REQ-001`.
**Bugs relacionados:** riesgo de tipo incorrecto, ruta equivocada o pérdida del tipo después de aprobar/reabrir.

### ISSUE-002 — No existe el tercer botón ni el flujo web de alta

**Tipo:** trabajo funcional.

**Estado:** `OPEN`.

**Trabajo existente:** añadir el botón `Llamada`, crear el registro pendiente y dirigirlo a su formulario.

**REQ relacionados:** `REQ-002`.
**Bugs relacionados:** doble creación por clic repetido, alta con tipo incorrecto o formulario equivocado.

### ISSUE-003 — No existe persistencia para los campos específicos

**Tipo:** trabajo de datos.

**Estado:** `IMPLEMENTED IN PR-002 — PENDING QA`.

**Trabajo existente:** definir claves estables, límites, validación, persistencia JSON y presentación de los campos del formato.

**REQ relacionados:** `REQ-003`.
**Bugs relacionados:** pérdida de saltos de línea, campos cruzados, valores omitidos al reabrir o texto sin escape.

### ISSUE-004 — Definir el mecanismo de firma o conformidad

**Tipo:** decisión funcional.

**Estado:** `RESOLVED BY PR-001`.

**Resolución aprobada:** se usarán dos áreas de firma manuscrita digital, una para `La empresa` y otra para `Cliente`. Serán opcionales en el alcance actual y su ausencia no bloqueará la aprobación. Una obligación futura requerirá un ISSUE nuevo.

**REQ relacionados:** `REQ-004`.
**Bugs relacionados:** implementar una captura incompatible con la operación real o generar un PDF incompleto.

### ISSUE-005 — Definir el límite del bloque fotográfico

**Tipo:** decisión funcional.

**Estado:** `RESOLVED BY PR-001`.

**Resolución aprobada:** “única” significa un único bloque general con un máximo de cinco fotografías; no significa una sola fotografía.

**REQ relacionados:** `REQ-005`.
**Bugs relacionados:** permitir más evidencia de la autorizada o bloquear fotografías necesarias.

### ISSUE-006 — Definir si Llamada cargará fotos desde web

**Tipo:** decisión de alcance.

**Estado:** `RESOLVED BY PR-001`.

**Resolución aprobada:** la web permitirá cargar y eliminar fotografías de Llamada mientras el reporte esté pendiente. Es una excepción exclusiva del nuevo tipo y no habilita edición fotográfica en Elevador o ALIMAK.

**REQ relacionados:** `REQ-005`.
**Bugs relacionados:** reactivar accidentalmente edición fotográfica web para Elevador/ALIMAK.

### ISSUE-007 — Debe extenderse el bloqueo de aprobados

**Tipo:** trabajo de reglas y seguridad.

**Estado:** `IMPLEMENTED IN PR-002 — PENDING QA`.

**Trabajo existente:** aplicar a Llamada las validaciones de edición, fotos, eliminación, aprobación y reapertura existentes en interfaz y servidor.

**REQ relacionados:** `REQ-006`.
**Bugs relacionados:** modificación o eliminación directa de un aprobado y PDF desactualizado.

### ISSUE-008 — Las acciones y el PDF no contemplan Llamada

**Tipo:** trabajo funcional.

**Estado:** `OPEN`.

**Trabajo existente:** adaptar listado, visualización, PDF firmado y WhatsApp al nuevo tipo.

**REQ relacionados:** `REQ-007`, `REQ-008`.
**Bugs relacionados:** iconos habilitados sin PDF, URL inválida, título incorrecto o eliminación de aprobados.

### ISSUE-009 — Clientes existentes pueden fallar ante un tipo desconocido

**Tipo:** riesgo de compatibilidad.

**Estado:** `OPEN`.

**Trabajo existente:** comprobar API, web y APK actual cuando `GET /reports` incluya `llamada`; aplicar manejo seguro antes de crear datos visibles para clientes antiguos.

**REQ relacionados:** `REQ-001`, `REQ-010`, `REQ-011`.
**Bugs relacionados:** cierre de la APK, filtro incorrecto, card mal etiquetada o apertura de editor equivocado.

### ISSUE-010 — Definir el título de la fila

**Tipo:** decisión funcional.

**Estado:** `RESOLVED BY PR-001`.

**Resolución aprobada:** el título no será editable. Se generará como `LLAMADA - CLIENTE - FECHA`; mientras falten los datos se mostrará `LLAMADA #ID`.

**REQ relacionados:** `REQ-002`.
**Bugs relacionados:** filas sin título o títulos inconsistentes entre web, APK, PDF y WhatsApp.

### ISSUE-011 — El generador PDF requiere una plantilla Llamada

**Tipo:** trabajo backend.

**Estado:** `OPEN`.

**Trabajo existente:** diseñar el documento multipágina respetando el orden del formato, fotos, comentarios, firmas y reglas transaccionales de aprobación.

**REQ relacionados:** `REQ-008`.
**Bugs relacionados:** cierre sin PDF, campos truncados, fotos omitidas o archivo compartido desactualizado.

### ISSUE-012 — La secuencia web antes de APK debe ser verificable

**Tipo:** control de proceso.

**Estado:** `OPEN`.

**Trabajo existente:** cerrar las pruebas web y la regresión antes de autorizar cualquier PR Android.

**REQ relacionados:** `REQ-009`.
**Bugs relacionados:** duplicar en Android un contrato todavía inestable.

### ISSUE-013 — La APK no tiene interfaz Llamada

**Tipo:** trabajo Android.

**Estado:** `OPEN`.

**Trabajo existente:** añadir botón, filtro, card, editor, fotos generales, visor y acciones una vez validado el contrato web.

**REQ relacionados:** `REQ-010`.
**Bugs relacionados:** problemas de orientación, pérdida de datos, sincronización incompleta o diferencias con web.

### ISSUE-014 — Se requiere regresión de los tipos existentes

**Tipo:** riesgo de regresión.

**Estado:** `OPEN`.

**Trabajo existente:** probar Elevador y ALIMAK después de cambios de tipo, rutas, PDF, filtros y fotografías.

**REQ relacionados:** `REQ-011`.
**Bugs relacionados:** degradación de flujos ya validados.

## IMIPLEMENTATION

> El nombre de esta sección conserva la nomenclatura solicitada por QA. Cada PR es una fase técnica interna y no representa un Pull Request de GitHub.

| PR | Estado | Implementación prevista | REQ relacionados | ISSUE relacionados | Salida esperada |
| --- | --- | --- | --- | --- | --- |
| `PR-001` | `COMPLETED` | Contrato funcional cerrado: firmas digitales opcionales, máximo de cinco fotos, carga web exclusiva para Llamada y título automático. | `REQ-002`, `REQ-003`, `REQ-004`, `REQ-005` | `ISSUE-004`, `ISSUE-005`, `ISSUE-006`, `ISSUE-010` | Validado y cerrado por QA. |
| `PR-002` | `DEPLOYMENT WEB` | Backend/API implementado para `llamada`: alta, detalle, actualización validada, fotos generales, aprobación/PDF base, reapertura y eliminación protegida. | `REQ-001`, `REQ-003`, `REQ-005`, `REQ-006`, `REQ-008`, `REQ-011` | `ISSUE-001`, `ISSUE-003`, `ISSUE-007`; avances en `ISSUE-008`, `ISSUE-009`, `ISSUE-011`, `ISSUE-014` | Código listo; QA debe desplegar el SHA documentado en la VPS DEMO. |
| `PR-003` | `PENDING` | Añadir botón `Llamada` y formulario web responsive con todos los campos aprobados. | `REQ-002`, `REQ-003`, `REQ-004` | `ISSUE-002`, `ISSUE-003`, `ISSUE-004`, `ISSUE-010` | `DEPLOYMENT WEB`. |
| `PR-004` | `PENDING` | Implementar el único bloque fotográfico general en la web conforme al límite y alcance aprobados, con comentarios, miniaturas, visor y eliminación segura si corresponde. | `REQ-005`, `REQ-006`, `REQ-011` | `ISSUE-005`, `ISSUE-006`, `ISSUE-007`, `ISSUE-014` | `DEPLOYMENT WEB`. |
| `PR-005` | `PENDING` | Implementar vista web, acciones de fila, aprobación, plantilla PDF, URL firmada y WhatsApp para Llamada. | `REQ-004`, `REQ-006`, `REQ-007`, `REQ-008` | `ISSUE-004`, `ISSUE-007`, `ISSUE-008`, `ISSUE-010`, `ISSUE-011` | `DEPLOYMENT WEB`. |
| `PR-006` | `PENDING` | Ejecutar correcciones derivadas del despliegue web y preparar la matriz de regresión de Llamada, Elevador y ALIMAK. | `REQ-009`, `REQ-011` | `ISSUE-009`, `ISSUE-012`, `ISSUE-014` | `TESTING` cuando QA confirme el despliegue; `COMPLETED` solo tras su validación. |
| `PR-007` | `PENDING` | Preparar compatibilidad Android con el tipo `llamada` en modelos, parser, API, filtros y navegación, sin publicar aún la interfaz completa. | `REQ-001`, `REQ-010`, `REQ-011` | `ISSUE-009`, `ISSUE-013`, `ISSUE-014` | `DEPLOYMENT & COMPILING`. |
| `PR-008` | `PENDING` | Implementar interfaz Android completa: alta, card, edición, fotos generales, visualización, aprobación, PDF, WhatsApp y bloqueo por estado. | `REQ-003`, `REQ-004`, `REQ-005`, `REQ-006`, `REQ-007`, `REQ-008`, `REQ-010` | `ISSUE-003`, `ISSUE-004`, `ISSUE-005`, `ISSUE-007`, `ISSUE-008`, `ISSUE-011`, `ISSUE-013` | `DEPLOYMENT & COMPILING`. |
| `PR-009` | `PENDING` | Corregir hallazgos de compilación/prueba Android y realizar regresión en móvil/tablet, vertical/horizontal y APK release firmada. | `REQ-009`, `REQ-010`, `REQ-011` | `ISSUE-009`, `ISSUE-012`, `ISSUE-013`, `ISSUE-014` | `TESTING` cuando QA compile/instale; `COMPLETED` solo tras su validación. |

### Registro Git por PR

| PR | Nombre previsto del commit principal | SHA principal | Commits de corrección/evidencia |
| --- | --- | --- | --- |
| `PR-001` | `docs(PR-001): cerrar contrato del reporte Llamada` | `696a595e6f37badb4c91fe583bbb96b385f23722` | `24ad246` |
| `PR-002` | `feat(PR-002): incorporar reporte Llamada en backend y API` | `0932b3ed22980bc146c6363b9ee3d67a8f338b4d` | `PENDING` |
| `PR-003` | `feat(PR-003): crear formulario web del reporte Llamada` | `PENDING` | `PENDING` |
| `PR-004` | `feat(PR-004): incorporar fotografías generales de Llamada` | `PENDING` | `PENDING` |
| `PR-005` | `feat(PR-005): añadir vista PDF y acciones de Llamada` | `PENDING` | `PENDING` |
| `PR-006` | `test(PR-006): registrar regresión web de reportes` | `PENDING` | `PENDING` |
| `PR-007` | `feat(PR-007): admitir tipo Llamada en Android` | `PENDING` | `PENDING` |
| `PR-008` | `feat(PR-008): implementar reporte Llamada en la APK` | `PENDING` | `PENDING` |
| `PR-009` | `test(PR-009): registrar validación Android y release` | `PENDING` | `PENDING` |

## Contrato aprobado en PR-001

### Datos y claves técnicas

- `cliente_reporte`: Cliente, texto corto, utilizando la columna existente.
- `equipo_reporte`: Equipo, texto corto, utilizando la columna existente.
- `fecha_reporte`: Fecha, utilizando la columna existente y su validación de fecha.
- `trabajo_realizado`: texto multilínea dentro de `data_reporte`.
- `motivo`: texto multilínea dentro de `data_reporte`.
- `piezas_reemplazadas`: texto multilínea dentro de `data_reporte`; puede quedar vacío.
- `observaciones_recomendaciones`: texto multilínea dentro de `data_reporte`; puede quedar vacío.
- `firma_empresa`: referencia segura a la firma manuscrita digital de la empresa.
- `firma_cliente`: referencia segura a la firma manuscrita digital del cliente.
- `_photos`: colección fotográfica existente, utilizando únicamente ámbito `general` para Llamada.

Los campos narrativos preservarán saltos de línea. Las firmas se almacenarán como recursos privados y no como datos Base64 embebidos en HTML o JSON.

### Firmas

- Habrá un área de firma manuscrita digital para `La empresa` y otra para `Cliente`.
- Ambas firmas serán opcionales durante creación, edición y aprobación en el alcance actual.
- Si existe una firma se mostrará en la visualización y en el PDF.
- Mientras el reporte esté pendiente podrá reemplazarse o eliminarse con confirmación.
- Una vez aprobado quedará bloqueada junto con el resto del reporte.

### Fotografías

- Llamada tendrá exactamente un bloque general con capacidad de cero a cinco fotografías.
- Cada comentario será opcional y mantendrá el máximo vigente de 500 caracteres.
- La web podrá cargar y eliminar fotos únicamente para reportes Llamada pendientes.
- Elevador y ALIMAK conservarán su comportamiento web actual sin controles de carga o eliminación.
- La API será autoridad del límite y del bloqueo por estado.

### Título

- El usuario no editará el título.
- Con cliente y fecha se mostrará `LLAMADA - CLIENTE - FECHA`.
- Antes de completar esos datos se mostrará `LLAMADA #ID`.
- Web, API, APK, PDF y WhatsApp utilizarán el mismo título calculado.

`PR-001` fue validado por QA y está `COMPLETED`.

## Implementación ejecutada en PR-002

### Issues resueltos técnicamente

- `ISSUE-001`: backend y API reconocen y preservan el tipo `llamada` durante alta, consulta, aprobación y reapertura.
- `ISSUE-003`: la API inicializa, valida, normaliza, guarda y recupera las cuatro claves narrativas definidas en el contrato.
- `ISSUE-007`: actualización, carga/eliminación de fotos, eliminación del reporte y reapertura respetan el estado bajo bloqueo transaccional.

Estos tres ISSUE permanecerán pendientes de cierre administrativo hasta que QA valide `PR-002` en DEMO. `ISSUE-008`, `ISSUE-009`, `ISSUE-011` e `ISSUE-014` solo reciben avances y no se consideran resueltos por este PR.

### Comportamiento implementado

- `POST /api/v1/index.php/reports` acepta `type=llamada`, crea el estado `open`, inicializa su estructura JSON y asigna `LLAMADA #ID`.
- `GET /reports` y `GET /reports/{id}` devuelven el nuevo tipo y sus datos sin convertirlo en Elevador.
- `PUT /reports/{id}` valida fecha `YYYY-MM-DD`, campos permitidos y límite de 10 000 caracteres por campo narrativo; calcula el título automáticamente.
- Los cambios de metadatos fotográficos no pueden agregar o retirar archivos mediante `PUT`; esas operaciones deben usar los endpoints de fotos.
- Llamada admite exclusivamente fotos `general`, con comentario opcional de hasta 500 caracteres y máximo de cinco.
- `POST /reports/{id}/approve` conserva el tipo y genera un PDF backend base con los datos y fotos generales. La plantilla visual definitiva y firmas corresponden a PR posteriores.
- `POST /reports/{id}/reopen` invalida el PDF activo y devuelve un aprobado a estado pendiente.
- `DELETE /reports/{id}` y `DELETE /reports/{id}/photos/{name}` rechazan reportes aprobados y usan bloqueo transaccional.
- El backend web admite crear el tipo mediante sentencia preparada y preservarlo al reabrir.
- Mientras no exista la vista web propia, el listado deshabilita su icono de visualización en lugar de abrir incorrectamente la vista Elevador.
- No se requiere migración de tabla: se reutilizan las columnas existentes y `data_reporte`.

### Evidencia Dev

- Revisión estática de rutas, tipos, estados y consultas preparadas completada.
- `git diff --check` sin errores.
- Localhost y WSL no disponen de PHP CLI; QA debe ejecutar `php -l` y las pruebas integradas con Apache/MariaDB en DEMO.

`PR-002` está en `DEPLOYMENT WEB`. `PR-003` a `PR-009` permanecen en `PENDING`.
