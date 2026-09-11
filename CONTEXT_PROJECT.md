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
- Campo simple de texto `La empresa`.
- Campo simple de texto `Cliente`, alineado junto al anterior.

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

**Comportamiento requerido:** el sistema debe reconocer `llamada` como tercer tipo de reporte sin modificar el comportamiento de `elevador` y `alimak`. La etiqueta visible será `Llamada` y su formulario debe representar fielmente el formato operativo aprobado, incluidos los campos de texto `La empresa` y `Cliente` ubicados uno al lado del otro.

**ISSUES relacionados:** `ISSUE-001`, `ISSUE-009`, `ISSUE-015`.

### REQ-002 — Crear Llamada desde la web

**Comportamiento requerido:** el listado web debe mostrar un tercer botón llamado exactamente `Llamada`. Al pulsarlo debe crear un reporte pendiente de tipo `llamada` y abrir el formulario correspondiente. El título será automático con formato `LLAMADA - CLIENTE - FECHA`; antes de disponer de esos datos se mostrará `LLAMADA #ID`.

**ISSUES relacionados:** `ISSUE-002`, `ISSUE-010`.

### REQ-003 — Capturar los campos del formato

**Comportamiento requerido:** el formulario debe capturar, guardar y recuperar Cliente, Equipo, Fecha, Trabajo realizado, Motivo, Piezas reemplazadas y Observaciones y recomendaciones, preservando los saltos de línea de los campos extensos.

**ISSUES relacionados:** `ISSUE-003`.

### REQ-004 — Representar los campos finales de empresa y cliente

**Comportamiento requerido:** la web y la APK deben proporcionar dos campos simples de texto, `La empresa` y `Cliente`, alineados uno al lado del otro como en el formato físico. Ambos son opcionales y su ausencia no bloquea el guardado ni la aprobación. No son áreas de firma manuscrita ni archivos de imagen.

**ISSUES relacionados:** `ISSUE-004`, `ISSUE-015`.

### REQ-005 — Incorporar fotografías generales

**Comportamiento requerido:** Llamada debe tener un solo bloque de fotografías generales al inicio del reporte, con un máximo de cinco imágenes. No debe crear grupos por sección. Las fotos deben admitir comentario opcional, miniatura, ampliación y eliminación mientras el reporte esté pendiente. La web permitirá cargar y eliminar fotos únicamente para Llamada; esta excepción no modifica Elevador ni ALIMAK.

**ISSUES relacionados:** `ISSUE-005`, `ISSUE-006`, `ISSUE-016`.

### REQ-006 — Mantener las reglas de estado

**Comportamiento requerido:** un reporte Llamada pendiente puede modificarse y eliminarse. Después de aprobarlo, web, API y APK deben impedir su modificación, eliminación y cambios fotográficos hasta que sea devuelto a `PENDIENTE` desde la web.

**ISSUES relacionados:** `ISSUE-007`, `ISSUE-016`.

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

**ISSUES relacionados:** `ISSUE-009`, `ISSUE-014`, `ISSUE-016`.

## ISSUES

### ISSUE-001 — El modelo y la API no reconocen Llamada

**Tipo:** trabajo funcional.

**Estado:** `RESOLVED BY PR-002`.

**Trabajo existente:** ampliar la lista de tipos, serialización, deserialización y enrutamiento para aceptar `llamada` sin interpretar el registro como Elevador.

**REQ relacionados:** `REQ-001`.
**Bugs relacionados:** riesgo de tipo incorrecto, ruta equivocada o pérdida del tipo después de aprobar/reabrir.

### ISSUE-002 — No existe el tercer botón ni el flujo web de alta

**Tipo:** trabajo funcional.

**Estado:** `IMPLEMENTED IN PR-003 — PENDING QA`.

**Trabajo existente:** añadir el botón `Llamada`, crear el registro pendiente y dirigirlo a su formulario.

**REQ relacionados:** `REQ-002`.
**Bugs relacionados:** doble creación por clic repetido, alta con tipo incorrecto o formulario equivocado.

### ISSUE-003 — No existe persistencia para los campos específicos

**Tipo:** trabajo de datos.

**Estado:** `RESOLVED BY PR-002`.

**Trabajo existente:** definir claves estables, límites, validación, persistencia JSON y presentación de los campos del formato.

**REQ relacionados:** `REQ-003`.
**Bugs relacionados:** pérdida de saltos de línea, campos cruzados, valores omitidos al reabrir o texto sin escape.

### ISSUE-004 — Definir el mecanismo de firma o conformidad

**Tipo:** decisión funcional.

**Estado:** `SUPERSEDED BY ISSUE-015`.

**Resolución histórica corregida por QA:** la interpretación de dos áreas de firma manuscrita fue incorrecta. La definición vigente está documentada en `ISSUE-015`.

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

**Estado:** `RESOLVED BY PR-002`.

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

**Trabajo existente:** diseñar el documento multipágina respetando el orden del formato, fotos, comentarios, campos finales de empresa/cliente y reglas transaccionales de aprobación.

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

### ISSUE-015 — Los campos finales fueron implementados erróneamente como firmas manuscritas

**Tipo:** BUG funcional y visual detectado por QA.

**Estado:** `IMPLEMENTED IN PR-003 — PENDING QA`.

**Trabajo existente:** retirar las dos áreas de dibujo, el almacenamiento de imágenes de firma y sus controles; reemplazarlos por dos campos simples de texto llamados `La empresa` y `Cliente`, alineados horizontalmente. Los valores deben ser opcionales, persistir en `data_reporte` y conservarse al volver a editar.

**REQ relacionados:** `REQ-001`, `REQ-004`.
**Bugs relacionados:** interpretación incorrecta del formato físico, interfaz sobredimensionada y almacenamiento innecesario de archivos de firma.

### ISSUE-016 — La web de Llamada no tiene gestión de fotografías generales

**Tipo:** trabajo funcional planificado.

**Estado:** `IMPLEMENTED IN PR-004 — PENDING QA`.

**Trabajo existente:** incorporar al editor web de Llamada un único bloque general que permita cargar hasta cinco fotografías, escribir comentarios opcionales, comprobar la subida, mostrar miniaturas, ampliar imágenes y eliminar evidencia mientras el reporte esté pendiente.

**REQ relacionados:** `REQ-005`, `REQ-006`, `REQ-011`.
**Bugs relacionados:** superar el límite por solicitudes simultáneas, pérdida de comentarios, archivos subidos sin metadatos, eliminación de evidencia de un aprobado o habilitación accidental de carga web para Elevador/ALIMAK.

## IMIPLEMENTATION

> El nombre de esta sección conserva la nomenclatura solicitada por QA. Cada PR es una fase técnica interna y no representa un Pull Request de GitHub.

| PR | Estado | Implementación prevista | REQ relacionados | ISSUE relacionados | Salida esperada |
| --- | --- | --- | --- | --- | --- |
| `PR-001` | `COMPLETED` | Contrato funcional inicial cerrado; la interpretación histórica de firmas fue sustituida por los campos de texto definidos posteriormente por QA. | `REQ-002`, `REQ-003`, `REQ-004`, `REQ-005` | `ISSUE-004`, `ISSUE-005`, `ISSUE-006`, `ISSUE-010`; corrección posterior `ISSUE-015` | Validado y cerrado por QA; conserva trazabilidad histórica. |
| `PR-002` | `COMPLETED` | Backend/API implementado para `llamada`: alta, detalle, actualización validada, fotos generales, aprobación/PDF base, reapertura y eliminación protegida. | `REQ-001`, `REQ-003`, `REQ-005`, `REQ-006`, `REQ-008`, `REQ-011` | Resuelve `ISSUE-001`, `ISSUE-003`, `ISSUE-007`; avances en `ISSUE-008`, `ISSUE-009`, `ISSUE-011`, `ISSUE-014` | Desplegado y validado completamente por QA en DEMO. |
| `PR-003` | `DEPLOYMENT WEB` | Botón `Llamada`, formulario web responsive y dos campos finales de texto alineados horizontalmente. | `REQ-001`, `REQ-002`, `REQ-003`, `REQ-004` | Implementa `ISSUE-002` y `ISSUE-015`, pendientes de validación QA; respeta los contratos cerrados en `ISSUE-003` e `ISSUE-010` | Corrección lista para despliegue en la VPS DEMO por QA. |
| `PR-004` | `DEPLOYMENT WEB` | Bloque fotográfico general web implementado con carga asíncrona, máximo de cinco, comentarios, miniaturas, visor y eliminación segura. | `REQ-005`, `REQ-006`, `REQ-011` | Implementa `ISSUE-016`, pendiente de validación QA; depende de `ISSUE-005`, `ISSUE-006` e `ISSUE-007`; aporta evidencia para `ISSUE-014` | Código listo para despliegue en la VPS DEMO por QA. |
| `PR-005` | `PENDING` | Implementar vista web, acciones de fila, aprobación, plantilla PDF, URL firmada y WhatsApp para Llamada. | `REQ-004`, `REQ-006`, `REQ-007`, `REQ-008` | `ISSUE-007`, `ISSUE-008`, `ISSUE-010`, `ISSUE-011`, `ISSUE-015` | `DEPLOYMENT WEB`. |
| `PR-006` | `PENDING` | Ejecutar correcciones derivadas del despliegue web y preparar la matriz de regresión de Llamada, Elevador y ALIMAK. | `REQ-009`, `REQ-011` | `ISSUE-009`, `ISSUE-012`, `ISSUE-014` | `TESTING` cuando QA confirme el despliegue; `COMPLETED` solo tras su validación. |
| `PR-007` | `PENDING` | Preparar compatibilidad Android con el tipo `llamada` en modelos, parser, API, filtros y navegación, sin publicar aún la interfaz completa. | `REQ-001`, `REQ-010`, `REQ-011` | `ISSUE-009`, `ISSUE-013`, `ISSUE-014` | `DEPLOYMENT & COMPILING`. |
| `PR-008` | `PENDING` | Implementar interfaz Android completa: alta, card, edición, fotos generales, visualización, aprobación, PDF, WhatsApp y bloqueo por estado. | `REQ-003`, `REQ-004`, `REQ-005`, `REQ-006`, `REQ-007`, `REQ-008`, `REQ-010` | `ISSUE-003`, `ISSUE-005`, `ISSUE-007`, `ISSUE-008`, `ISSUE-011`, `ISSUE-013`, `ISSUE-015` | `DEPLOYMENT & COMPILING`. |
| `PR-009` | `PENDING` | Corregir hallazgos de compilación/prueba Android y realizar regresión en móvil/tablet, vertical/horizontal y APK release firmada. | `REQ-009`, `REQ-010`, `REQ-011` | `ISSUE-009`, `ISSUE-012`, `ISSUE-013`, `ISSUE-014` | `TESTING` cuando QA compile/instale; `COMPLETED` solo tras su validación. |

### Registro Git por PR

| PR | Nombre previsto del commit principal | SHA principal | Commits de corrección/evidencia |
| --- | --- | --- | --- |
| `PR-001` | `docs(PR-001): cerrar contrato del reporte Llamada` | `696a595e6f37badb4c91fe583bbb96b385f23722` | `24ad246` |
| `PR-002` | `feat(PR-002): incorporar reporte Llamada en backend y API` | `0932b3ed22980bc146c6363b9ee3d67a8f338b4d` | `7d6a509` |
| `PR-003` | `feat(PR-003): crear formulario web del reporte Llamada` | `214b74a60c3e538d014d67404deb1318e303438f` | `24f598716f36708c6e4e6c03e526472091ab7a5c` (`ISSUE-015`) |
| `PR-004` | `feat(PR-004): incorporar fotografías generales de Llamada` | `293eeb21840b0b582eca5a424607233d5c6b15e5` | `PENDING` |
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
- `firma_empresa`: texto opcional de `La empresa`, con máximo de 255 caracteres.
- `firma_cliente`: texto opcional de `Cliente`, con máximo de 255 caracteres.
- `_photos`: colección fotográfica existente, utilizando únicamente ámbito `general` para Llamada.

Los campos narrativos preservarán saltos de línea. Los dos campos finales son texto simple almacenado en `data_reporte`; no representan imágenes ni firmas manuscritas.

### Campos finales de conformidad

- Habrá un campo simple de texto para `La empresa` y otro para `Cliente`.
- Ambos estarán uno al lado del otro, incluso en la presentación móvil, reproduciendo el formato entregado por QA.
- Son opcionales durante creación, edición y aprobación.
- Sus valores se mostrarán posteriormente en la visualización y el PDF.
- Una vez aprobado quedarán bloqueados junto con el resto del reporte.

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

QA validó los casos funcionales de `PR-002` en DEMO; `ISSUE-001`, `ISSUE-003` e `ISSUE-007` quedan resueltos. `ISSUE-008`, `ISSUE-009`, `ISSUE-011` e `ISSUE-014` solo recibieron avances y no se consideran resueltos por este PR.

### Comportamiento implementado

- `POST /api/v1/index.php/reports` acepta `type=llamada`, crea el estado `open`, inicializa su estructura JSON y asigna `LLAMADA #ID`.
- `GET /reports` y `GET /reports/{id}` devuelven el nuevo tipo y sus datos sin convertirlo en Elevador.
- `PUT /reports/{id}` valida fecha `YYYY-MM-DD`, campos permitidos y límite de 10 000 caracteres por campo narrativo; calcula el título automáticamente.
- Los cambios de metadatos fotográficos no pueden agregar o retirar archivos mediante `PUT`; esas operaciones deben usar los endpoints de fotos.
- Llamada admite exclusivamente fotos `general`, con comentario opcional de hasta 500 caracteres y máximo de cinco.
- `POST /reports/{id}/approve` conserva el tipo y genera un PDF backend base con los datos y fotos generales. La plantilla visual definitiva corresponde a PR posteriores.
- `POST /reports/{id}/reopen` invalida el PDF activo y devuelve un aprobado a estado pendiente.
- `DELETE /reports/{id}` y `DELETE /reports/{id}/photos/{name}` rechazan reportes aprobados y usan bloqueo transaccional.
- El backend web admite crear el tipo mediante sentencia preparada y preservarlo al reabrir.
- Mientras no exista la vista web propia, el listado deshabilita su icono de visualización en lugar de abrir incorrectamente la vista Elevador.
- No se requiere migración de tabla: se reutilizan las columnas existentes y `data_reporte`.

### Evidencia Dev

- Revisión estática de rutas, tipos, estados y consultas preparadas completada.
- `git diff --check` sin errores.
- Localhost y WSL no disponen de PHP CLI; QA debe ejecutar `php -l` y las pruebas integradas con Apache/MariaDB en DEMO.

### Evidencia QA

- Lint PHP ejecutado en el servidor autorizado sin errores informados.
- Alta, detalle, listado, actualización y título automático de Llamada validados.
- Fecha y campos inválidos rechazados correctamente.
- Fotografías generales, comentario opcional, límite de cinco y rechazo de secciones validados.
- Aprobación, PDF base, bloqueo del aprobado, reapertura e invalidación del PDF validados.
- Eliminación del reporte pendiente validada.

`PR-002` está `COMPLETED`. `PR-003` a `PR-009` permanecen en `PENDING`.

## Alcance previsto de PR-003

**Estado:** `DEPLOYMENT WEB`. La corrección de `ISSUE-015` está terminada; QA debe desplegarla en DEMO.

**Objetivo:** entregar en la web el flujo de creación y edición de un reporte `Llamada`, consumiendo el backend validado en `PR-002`.

### ISSUE que resolverá

- `ISSUE-002` — No existe el tercer botón ni el flujo web de alta. Es el único ISSUE abierto que este PR cerrará directamente.

### Dependencias ya resueltas

- `ISSUE-003`: aporta las claves, validaciones y persistencia de los campos; no se vuelve a cerrar en este PR.
- `ISSUE-004`: conserva la interpretación histórica descartada; `ISSUE-015` contiene la definición vigente de dos textos opcionales.
- `ISSUE-010`: aporta la regla de título automático `LLAMADA - CLIENTE - FECHA`, con respaldo `LLAMADA #ID`.

### Implementación ejecutada

- Añadir el tercer botón `Llamada` al listado web, conservando los botones de Elevador y ALIMAK.
- Crear un reporte pendiente de tipo `llamada` una sola vez y abrir su formulario específico.
- Construir un formulario responsive para Cliente, Equipo, Fecha, Trabajo realizado, Motivo, Piezas reemplazadas y Observaciones y recomendaciones.
- Mostrar el título calculado por el sistema sin permitir que el usuario lo edite manualmente.
- Incorporar dos campos opcionales de texto, `La empresa` y `Cliente`, alineados uno al lado del otro.
- Guardar y volver a cargar todos los datos sin pérdida de saltos de línea.
- Impedir desde la interfaz la edición de un reporte aprobado y conservar también la validación del servidor.
- Mantener sin cambios funcionales los flujos web de Elevador y ALIMAK.

### Fuera de alcance

- Fotografías generales, comentarios, miniaturas y visor: `PR-004`.
- Visualización final, aprobación, PDF, URL firmada y WhatsApp: `PR-005`.
- Cambios en Android: `PR-007` y `PR-008`.

### Criterios de aceptación para QA

- El listado muestra los tres botones y `Llamada` abre el formulario correcto.
- Un solo clic genera exactamente un reporte pendiente de tipo `llamada`.
- Todos los campos se guardan, conservan saltos de línea y reaparecen al volver a editar.
- El título cambia automáticamente según Cliente y Fecha y no puede editarse directamente.
- Los textos `La empresa` y `Cliente` son opcionales, permanecen uno al lado del otro y persisten al volver a editar.
- Un reporte aprobado no puede modificarse, incluso intentando acceder directamente a la ruta.
- Crear y editar Elevador y ALIMAK continúa funcionando sin regresiones.

### Evidencia Dev

- Se creó `edit_llamada.php` con lectura preparada, validación de tipo/estado, formulario responsive y escape de salida.
- El guardado usa bloqueo transaccional, consultas preparadas, validación de fecha y límites de longitud.
- Los dos campos finales se validan como texto de hasta 255 caracteres y se guardan dentro de `data_reporte`.
- Se retiraron el canvas, la recepción Base64, la creación de PNG y el endpoint temporal de firmas de la implementación activa.
- La API acepta y preserva los textos cuando una actualización de Llamada proviene de otro cliente compatible.
- El botón de alta utiliza CSRF y se deshabilita durante la solicitud para evitar doble creación por clic repetido.
- `git diff --check` no reportó errores.
- No existe PHP CLI en Windows ni en WSL local; el lint PHP y la prueba integrada con Apache/MariaDB corresponden a QA en DEMO.

### Corrección QA de ISSUE-015

- QA detectó que las áreas de firma manuscrita no correspondían al formato físico entregado.
- `PR-003` volvió temporalmente a `DEVELOPING` para ejecutar la corrección.
- Los controles de dibujo fueron reemplazados por dos entradas de texto de igual ancho y en una sola fila.
- El PDF backend base dejó de rotular esos valores como firmas y ahora los representa como `La empresa` y `Cliente`.
- Cualquier referencia PNG creada durante la prueba incorrecta se ignora y se sustituye al guardar los nuevos textos.

`PR-003` volvió a `DEPLOYMENT WEB`. QA decidirá posteriormente su paso a `TESTING` y `COMPLETED`; `ISSUE-002` e `ISSUE-015` no se cerrarán hasta esa validación.

## Aclaración de alcance sobre visualización y aprobación

QA informó durante la revisión de `PR-003` que la fila Llamada todavía no permite visualizar ni aprobar el reporte. No se registra como BUG de `PR-003`, porque ambas capacidades fueron excluidas expresamente de su alcance antes de desarrollarlo.

- El icono de visualización gris es temporal e intencional.
- La visualización, aprobación, PDF y WhatsApp corresponden a `PR-005`.
- El trabajo pendiente ya está representado por `ISSUE-008` e `ISSUE-011`; no se abre un ISSUE duplicado.
- Esta observación no impide probar en `PR-003` la creación, edición, persistencia, título y campos finales de texto.

## Alcance previsto de PR-004

**Estado:** `DEPLOYMENT WEB`. Implementación técnica terminada; QA debe desplegarla en DEMO.

**Objetivo:** completar en el editor web de Llamada el único bloque de fotografías generales aprobado, sin modificar la gestión web de fotografías de Elevador o ALIMAK.

### ISSUE que resolverá

- `ISSUE-016` — La web de Llamada no tiene gestión de fotografías generales. Es el único ISSUE funcional abierto que `PR-004` cerrará directamente.

### Contratos y riesgos relacionados

- `ISSUE-005`, resuelto: define un solo bloque general y un máximo de cinco fotografías.
- `ISSUE-006`, resuelto: autoriza carga y eliminación web únicamente para Llamada pendiente.
- `ISSUE-007`, resuelto: obliga al servidor a rechazar cambios fotográficos en reportes aprobados.
- `ISSUE-014`, abierto: recibirá evidencia de regresión, pero no se cerrará hasta la fase integral de pruebas.

### Implementación ejecutada

- Colocar el bloque `Fotografías generales` al inicio del contenido editable del reporte Llamada.
- Permitir seleccionar o capturar imágenes desde un navegador compatible.
- Limitar el bloque a cinco fotografías mediante validación autoritativa del servidor.
- Admitir un comentario opcional de hasta 500 caracteres por fotografía.
- Mostrar progreso y resultado verificable de cada subida sin perder los demás datos del formulario.
- Mostrar las fotografías guardadas como miniaturas y permitir ampliarlas mediante el visor existente.
- Permitir eliminar fotografías con confirmación únicamente mientras el reporte esté pendiente.
- Mantener los archivos en el almacenamiento fotográfico privado y servirlos mediante las URLs protegidas existentes.
- Conservar sin cambios la interfaz de carga fotográfica de Elevador y ALIMAK.

### Fuera de alcance

- Habilitar el ojo, crear la visualización final o aprobar el reporte: `PR-005`.
- Generar el PDF definitivo y compartirlo por WhatsApp: `PR-005`.
- Implementar Llamada en Android: `PR-007` y `PR-008`.

### Criterios de aceptación previstos

- Un reporte Llamada pendiente admite entre cero y cinco fotografías generales.
- La sexta fotografía es rechazada por el servidor y la interfaz explica el límite.
- Los comentarios pueden quedar vacíos y persisten cuando se completan.
- Las miniaturas reaparecen al volver a editar y abren el visor ampliado.
- La eliminación requiere confirmación y desaparece tanto de la interfaz como del servidor.
- Un reporte aprobado rechaza carga, cambio de comentario y eliminación mediante interfaz y solicitud directa.
- Elevador y ALIMAK no adquieren controles web nuevos de carga o eliminación.

### Evidencia Dev

- El bloque aparece al inicio del contenido editable y trabaja sin recargar el formulario.
- Carga, comentario y eliminación son operaciones asíncronas independientes protegidas con CSRF.
- Cada operación abre una transacción, bloquea el reporte y vuelve a comprobar tipo, estado y cantidad.
- El servidor acepta únicamente JPEG, PNG o WEBP de hasta 5 MB y valida dimensiones antes de almacenarlos.
- El límite de cinco se aplica dentro del bloqueo transaccional para impedir sobrepasarlo mediante solicitudes simultáneas.
- Los comentarios son opcionales, admiten hasta 500 caracteres y se guardan por fotografía.
- Las respuestas devuelven la lista confirmada por el servidor; la interfaz reconstruye miniaturas, contador y visor desde ella.
- Los archivos permanecen bajo `storage/report-photos`, cuyo acceso HTTP directo queda bloqueado; la visualización usa URLs temporales firmadas.
- `node --check` validó `assets/js/call-photos.js` y `assets/js/report-gallery.js`.
- `git diff --check` no reportó errores.
- PHP CLI no está disponible en Windows ni WSL local; QA debe ejecutar lint PHP y pruebas integradas en DEMO.

`PR-004` está en `DEPLOYMENT WEB`. `ISSUE-016` seguirá pendiente de cierre hasta que QA autorice `TESTING` y valide todos los casos.
