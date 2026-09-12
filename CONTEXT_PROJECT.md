# Contexto del proyecto: Reportes Internepro

## Gobierno del proyecto

Aplicación operativa de Internepro desplegada sobre Linux, Apache, PHP y MariaDB. El ecosistema incluye portal web, API REST y aplicación Android nativa.

QA controla el ciclo de cambios. Dev no ejecuta despliegues en la VPS, migraciones, pruebas contra DEMO/PROD, compilaciones de distribución ni cambios técnicos sin autorización expresa de QA. Dev implementa y verifica en localhost; QA despliega, compila, instala y valida.

Objetivos vigentes:

- Objetivo principal: disponer de una APK para móvil y tablet que permita realizar en campo todo el flujo operativo autorizado.
- Objetivo secundario: conservar el acceso web, sin degradarlo, como canal de administración, consulta, aprobación, reapertura, PDF y soporte operativo.
- Mantener compatibilidad con los reportes existentes de Elevador y ALIMAK.

Este archivo es la única fuente activa de contexto, requisitos, ISSUES y fases internas de implementación. `DEPLOY_PROJECT.md` fue retirado por orden de QA. Git conserva el historial técnico ya terminado.

### Estados autorizados para un PR

| Estado | Significado | Responsable siguiente |
| --- | --- | --- |
| `PENDING` | Fase documentada, sin autorización técnica. | QA autoriza. |
| `DEVELOPING` | QA autorizó y Dev está implementando. | Dev termina y verifica. |
| `DEPLOYMENT WEB` | Código web/API listo para que QA lo suba a DEMO. | QA despliega. |
| `DEPLOYMENT & COMPILING` | Código Android listo para que QA lo compile e instale. | QA compila e instala. |
| `TESTING` | QA confirmó que la revisión está desplegada o instalada. | QA prueba. |
| `COMPLETED` | QA validó el alcance completo. | Cerrado. |

Reglas de transición:

- Dev solo mueve `PENDING` a `DEVELOPING` después de autorización expresa de QA.
- Dev puede entregar en `DEPLOYMENT WEB` o `DEPLOYMENT & COMPILING`; esos estados no significan que el código ya esté en el servidor o dispositivo.
- Solo QA ordena el paso a `TESTING` y confirma `COMPLETED`.
- Un BUG encontrado en un PR abierto devuelve ese PR a `DEVELOPING`, recibe simultáneamente el siguiente `BUG-###` y el siguiente `ISSUE-###`, y conserva el mismo número de PR.
- No se crean fases futuras ni se reservan números PR sin que QA solicite definirlas.

### Control de identificadores

| Registro | Último ID utilizado | Próximo ID disponible | Regla |
| --- | --- | --- | --- |
| Requisito | `REQ-001` | `REQ-002` | Solo se crea otro REQ si QA aprueba un comportamiento independiente. |
| Issue | `ISSUE-019` | `ISSUE-020` | Todo trabajo o defecto nuevo toma el siguiente número; los IDs retirados nunca se reutilizan. |
| BUG | `BUG-004` | `BUG-005` | Cada defecto confirmado toma un consecutivo propio y referencia su ISSUE, REQ y PR. |
| Fase interna | `PR-005` | `PR-006` | Solo se asigna cuando QA solicita describir una nueva fase. No representa un Pull Request de GitHub. |

Por tanto, el próximo defecto será `BUG-005` y, si requiere un ISSUE nuevo, usará `ISSUE-020`. Ninguno de esos números se reutilizará.

### Trazabilidad con Git

- Cada PR tiene uno o más commits cuyos mensajes incluyen `PR-###`.
- Los commits correctivos incluyen además el `ISSUE-###` correspondiente cuando resulte útil.
- El SHA técnico se registra en este archivo mediante un commit documental posterior; un commit no puede contener su propio SHA.
- QA debe desplegar o compilar el SHA documentado. No se reescriben commits ya entregados; cada corrección produce un SHA nuevo.

## Estado funcional actual

- Portal web PHP para listar, crear, editar, visualizar, aprobar, reabrir y eliminar reportes según tipo y estado.
- API REST bajo `/api/v1`, autenticada mediante credencial técnica Bearer almacenada fuera del repositorio.
- Aplicación Android nativa desarrollada con Kotlin y Jetpack Compose para móvil/tablet y ambas orientaciones.
- Elevador y ALIMAK están operativos en web, API y APK.
- La APK gestiona fotografías generales y seis bloques fotográficos ALIMAK, con comentarios opcionales, compresión y almacenamiento privado.
- Los reportes aprobados quedan bloqueados para edición, fotografías y eliminación hasta que la web los devuelva a `PENDIENTE`.
- La aprobación genera un PDF privado y versionado; web y APK abren y comparten su URL temporal mediante WhatsApp.
- El nuevo tipo `llamada` ya es reconocido por backend/API y dispone de alta y edición web.
- La visualización, aprobación, generación de PDF y acciones web de Llamada fueron validadas en `PR-005`. La paginación y los campos sobrantes del PDF horizontal se corrigen en `ISSUE-019`; QA debe validar el PDF regenerado.
- La experiencia Android de Llamada continúa pendiente de una fase que QA todavía no ha creado.
- Para Llamada, la web es un canal fotográfico de solo lectura. La captura, carga, descripción y eliminación serán responsabilidad exclusiva de la APK.

## Arquitectura vigente

```text
Portal web PHP -----------------------+
                                      |
APK Android (Kotlin/Compose) --> API PHP /api/v1 --> MariaDB: tabla reporte
                                      |
                                      +--> storage privado de fotos y PDF
```

Componentes y responsabilidades:

- Web: interfaz administrativa y de consulta; nunca se conecta desde el navegador directamente a MariaDB.
- APK: canal de trabajo de campo; consume únicamente la API HTTPS.
- API: autoridad de autenticación, validación, límites, estados y permisos.
- MariaDB: persiste el reporte y sus estructuras JSON en el esquema existente.
- Almacenamiento privado: conserva fotografías y PDF; se accede mediante controladores y URLs firmadas, no por rutas físicas públicas.
- `config/db.php`: conexión MariaDB local del servidor, excluida de Git.
- `config/auth.php`: credencial técnica y secreto de firmas, excluidos de Git.

Principios:

- La APK nunca accede directamente a MariaDB.
- Los secretos no se versionan ni se entregan al navegador.
- Las consultas nuevas deben ser preparadas.
- API, web y APK deben preservar el tipo real del reporte.
- Incorporar Llamada no puede alterar las reglas ya validadas de Elevador y ALIMAK.

## REQUIREMENTS

### REQ-001 — Incorporar el reporte Llamada de punta a punta

**Estado:** `IN DEVELOPMENT`.

**Comportamiento requerido:** el sistema debe incorporar un tercer tipo `llamada`, visible como `Llamada`, primero en web/API y posteriormente en Android, sin degradar Elevador o ALIMAK.

Criterios aprobados por QA:

1. El listado web presenta un botón llamado exactamente `Llamada`.
2. El reporte utiliza Cliente, Equipo, Fecha, Trabajo realizado, Motivo, Piezas reemplazadas, Observaciones y recomendaciones, `La empresa` y `Cliente`.
3. `La empresa` y `Cliente` son entradas simples de texto, opcionales y alineadas horizontalmente; no son firmas manuscritas.
4. El título se calcula como `LLAMADA - CLIENTE - FECHA`; mientras falten datos se usa `LLAMADA #ID`.
5. Llamada contiene un único bloque de fotografías generales con máximo de diez.
6. Cada fotografía puede llevar una descripción opcional de hasta 500 caracteres.
7. Solo la APK captura, selecciona, sube, cambia la descripción y elimina fotografías mientras el reporte está pendiente.
8. La web únicamente muestra foto, descripción y visor ampliado; no ofrece controles de carga, edición o eliminación fotográfica.
9. Un reporte pendiente puede editarse y eliminarse. Un reporte aprobado queda bloqueado hasta que la web lo devuelva a `PENDIENTE`.
10. La aprobación debe producir un PDF horizontal que reproduzca visualmente el formato físico original de Llamada, con logo, franja de título, campos, fotografías y descripciones. El cuerpo principal debe ocupar una sola página para el formulario normal, sin campos `Aprobado por` ni `Fecha de aprobación` ni pie técnico; las fotografías pueden ocupar anexos adicionales.
11. PDF y WhatsApp solo se habilitan cuando existe un PDF vigente.
12. La APK deberá listar, filtrar, crear, editar, fotografiar, visualizar, aprobar, abrir PDF y compartir Llamada.

**ISSUES relacionados:** `ISSUE-001`, `ISSUE-002`, `ISSUE-003`, `ISSUE-007`, `ISSUE-008`, `ISSUE-009`, `ISSUE-010`, `ISSUE-011`, `ISSUE-013`, `ISSUE-014`, `ISSUE-015`, `ISSUE-017`, `ISSUE-018`, `ISSUE-019`.

No existen otros requisitos activos. La fragmentación documental anterior fue eliminada: todo el comportamiento autorizado del reporte Llamada pertenece únicamente a `REQ-001`.

## ISSUES

### Registro visible de BUGS

| BUG | ISSUE | REQ | Estado | Defecto | PR |
| --- | --- | --- | --- | --- | --- |
| `BUG-001` | `ISSUE-015` | `REQ-001` | `IMPLEMENTED — PENDING QA` | `La empresa` y `Cliente` fueron creados erróneamente como firmas manuscritas. | `PR-003` |
| `BUG-002` | `ISSUE-017` | `REQ-001` | `PARTIALLY VALIDATED — OPEN` | La web permitía gestionar fotografías y aplicaba límite 5 en lugar de 10. | `PR-004` |
| `BUG-003` | `ISSUE-018` | `REQ-001` | `IMPLEMENTED — DEPLOYMENT WEB` | El PDF de Llamada era vertical y no reproducía el formato físico original. | `PR-005` |
| `BUG-004` | `ISSUE-019` | `REQ-001` | `IMPLEMENTED — DEPLOYMENT WEB` | El PDF de Llamada crea una segunda página residual y agrega `Aprobado por` y `Fecha de aprobación`, ausentes del formato físico. | `PR-005` |

**Próximo BUG disponible: `BUG-005`.** Cada BUG mantiene además la referencia al ISSUE que representa el trabajo técnico.

### Registro único

| ID | Tipo | Estado | Descripción resumida | PR |
| --- | --- | --- | --- | --- |
| `ISSUE-001` | Trabajo | `RESOLVED` | Backend y API no reconocían el tipo Llamada. | `PR-002` |
| `ISSUE-002` | Trabajo | `PENDING QA` | Faltaban el botón y alta web de Llamada. | `PR-003` |
| `ISSUE-003` | Trabajo | `RESOLVED` | Faltaba persistencia para los campos específicos. | `PR-002` |
| `ISSUE-004` | Retirado | `SUPERSEDED` | Interpretación incorrecta de firmas; sustituida por `ISSUE-015`. | `PR-003` |
| `ISSUE-005` | Retirado | `SUPERSEDED` | Límite histórico de cinco fotos; sustituido por `ISSUE-017`. | `PR-004` |
| `ISSUE-006` | Retirado | `SUPERSEDED` | Carga web autorizada por error; sustituida por `ISSUE-017`. | `PR-004` |
| `ISSUE-007` | Trabajo | `RESOLVED` | Extender a Llamada el bloqueo de reportes aprobados. | `PR-002` |
| `ISSUE-008` | Trabajo | `RESOLVED` | Faltaban visualización y acciones finales del listado para Llamada. | `PR-005` |
| `ISSUE-009` | Riesgo | `OPEN — UNASSIGNED` | Verificar compatibilidad de clientes ante el nuevo tipo. | Sin PR |
| `ISSUE-010` | Decisión | `RESOLVED` | Definir título automático de Llamada. | `PR-001` |
| `ISSUE-011` | Trabajo | `RESOLVED WITH FOLLOW-UP BUG` | Faltaba la generación funcional del PDF de Llamada; el defecto visual posterior está en `ISSUE-018`. | `PR-005` |
| `ISSUE-012` | Retirado | `SUPERSEDED` | Control de proceso duplicado por Gobierno del proyecto. | Sin PR |
| `ISSUE-013` | Trabajo | `OPEN — UNASSIGNED` | La APK todavía no incorpora el tipo Llamada. | Sin PR |
| `ISSUE-014` | Riesgo | `OPEN — UNASSIGNED` | Falta regresión integral de Elevador y ALIMAK. | Sin PR |
| `ISSUE-015` | BUG | `IMPLEMENTED — PENDING QA` | Los campos finales se implementaron erróneamente como firmas. | `PR-003` |
| `ISSUE-016` | Retirado | `SUPERSEDED` | Gestión fotográfica web basada en alcance incorrecto. | `PR-004` |
| `ISSUE-017` | BUG | `PARTIALLY VALIDATED — OPEN` | La web permitía gestionar fotos y el límite de Llamada era cinco. | `PR-004` |
| `ISSUE-018` | BUG | `IMPLEMENTED — DEPLOYMENT WEB` | El PDF de Llamada no era horizontal ni reproducía el formato físico. | `PR-005` |
| `ISSUE-019` | BUG | `IMPLEMENTED — DEPLOYMENT WEB` | El PDF de Llamada agrega una página residual y una fila de aprobación ajena al formulario físico. | `PR-005` |

**Próximo ISSUE disponible: `ISSUE-020`.**

### ISSUE-008 — Visualización y acciones finales de Llamada

`PR-005` implementó la vista de solo lectura de Llamada, activó el ojo del listado y conectó aprobación, PDF vigente y WhatsApp. QA validó este flujo; el defecto posterior de presentación del PDF se aisló en `ISSUE-018`.

### ISSUE-009 — Compatibilidad con clientes existentes

Antes de exponer Llamada en Android debe comprobarse que listado, parser, filtro y navegación no fallen ante el nuevo tipo.

### ISSUE-011 — PDF definitivo

`PR-005` completó la generación multipágina con logo, título, campos, textos de conformidad, fotos generales y descripciones. La aprobación es transaccional: si el PDF falla, el reporte permanece pendiente y las acciones PDF/WhatsApp continúan deshabilitadas. QA validó la generación y apertura; `ISSUE-018` corrige posteriormente su orientación y fidelidad visual.

### ISSUE-013 — Interfaz Android de Llamada

Android aún debe incorporar creación, edición, bloque general de hasta diez fotos, comentarios opcionales, visor, aprobación, PDF y WhatsApp. Este trabajo no tiene número PR hasta que QA autorice definir una fase Android.

### ISSUE-014 — Regresión

Debe verificarse que incorporar Llamada no modifique rutas, estados, filtros, fotografías, PDF ni acciones de Elevador y ALIMAK.

### ISSUE-015 — Campos finales incorrectos

QA detectó que `La empresa` y `Cliente` fueron creados como áreas de firma manuscrita. `PR-003` los sustituyó por dos campos simples de texto opcionales, uno al lado del otro. La corrección espera validación final de QA.

### ISSUE-017 — Canal y límite fotográfico incorrectos

QA aclaró que la web no captura ni modifica fotografías de Llamada. La implementación original de `PR-004` añadió esos controles y limitó el bloque a cinco.

Corrección implementada:

- Se retiraron del formulario web los controles de captura, selección, carga, edición de descripción y eliminación.
- Se retiraron los handlers web específicos que mutaban las fotografías.
- La web reutiliza el componente de lectura para mostrar miniatura, descripción y visor ampliado.
- La API acepta hasta diez fotos en el bloque general cuando el reporte es Llamada.
- Elevador y cada bloque de ALIMAK mantienen su límite vigente de cinco.
- La foto número once es rechazada por la API.

La corrección técnica está lista para despliegue, pero `PR-004` permanece abierto: la visualización real de fotos y descripciones solo podrá validarse cuando una futura fase Android permita subirlas.

### ISSUE-018 — PDF de Llamada con orientación y diseño incorrectos

**Tipo:** BUG visual y documental detectado por QA después de validar el flujo funcional de `PR-005`.

**BUG:** `BUG-003`.

**Estado:** `IMPLEMENTED — DEPLOYMENT WEB`.

**Comportamiento observado:** el PDF se genera y puede abrirse/compartirse, pero usa una página vertical y una composición genérica. No reproduce la distribución horizontal del reporte físico entregado por QA.

**Comportamiento requerido:** el PDF de Llamada debe generarse en orientación horizontal y reproducir visualmente el formato físico original: encabezado corporativo, franja de título, Cliente/Equipo/Fecha alineados, bloques narrativos en el mismo orden y campos finales `La empresa`/`Cliente`. Las fotografías y sus descripciones deberán incorporarse después del cuerpo principal, usando páginas horizontales adicionales cuando sea necesario.

**Causa técnica confirmada:** el generador actual fija todas las páginas en `595.28 × 841.89` puntos (`MediaBox` vertical) y utiliza una sola composición lineal para los tres tipos de reporte.

**Solución técnica viable:** parametrizar ancho, alto, márgenes y orientación del generador; mantener Elevador/ALIMAK en vertical; crear una plantilla horizontal exclusiva para Llamada; dibujar posiciones, líneas y bloques equivalentes al formato físico; permitir páginas horizontales adicionales para evidencia fotográfica.

**Implementación ejecutada:** `ReportPdfDocument` ahora recibe la orientación y emite el `MediaBox` correspondiente. Llamada usa `841.89 × 595.28` puntos, una plantilla propia con logo y datos corporativos, franja roja, título, filas Cliente/Equipo/Fecha, campos narrativos enmarcados y conformidad. Las fotografías se trasladan a anexos horizontales con su descripción. Elevador y ALIMAK conservan orientación y composición vertical.

**REQ relacionado:** `REQ-001`.
**PR relacionado:** `PR-005`, reabierto por orden de QA hasta corregir y validar el PDF.

### ISSUE-019 — Segunda página residual y campos de aprobación en el PDF de Llamada

**Tipo:** BUG. **Identificador:** `BUG-004`. **REQ:** `REQ-001`. **PR:** `PR-005`. **Estado:** `IMPLEMENTED — DEPLOYMENT WEB`.

**Observado por QA:** un reporte de Llamada sin anexos fotográficos termina con una segunda página casi vacía, titulada «Continuación del reporte». En el cuerpo se imprimen `APROBADO POR` y `FECHA DE APROBACION`, pese a que esos campos no aparecen en el formulario físico aprobado.

**Causa:** la plantilla imprime una fila de aprobación innecesaria y luego un pie técnico; al llegar al margen inferior, `ensureSpace()` crea una página nueva solo para ese pie. La aprobación seguirá registrada en el backend, sin mostrarse en el PDF de Llamada.

**Implementación autorizada:** quitar la fila de aprobación y el pie técnico de la plantilla Llamada. Mantener `LA EMPRESA` y `CLIENTE` como los últimos campos del formulario. Un reporte con datos de longitud habitual y sin fotos debe generar exactamente una página horizontal; con fotos se permiten únicamente las páginas de anexo fotográfico necesarias. No cambiar los PDF de Elevador ni ALIMAK.

**Corrección ejecutada:** la rama Llamada del generador ya no imprime `APROBADO POR`, `FECHA DE APROBACION` ni el pie técnico después del formulario o de las fotos; mantiene el registro interno de aprobación y la generación de anexos solo cuando hay fotografías. Verificar la página única y la ausencia de estos campos en DEMO después de regenerar un PDF: los PDF aprobados anteriormente son instantáneas inmutables. La prueba local de PHP/PDF no pudo ejecutarse porque PHP CLI no está instalado en este entorno.

## IMIPLEMENTATION

> Se conserva la palabra `IMIPLEMENTATION` exactamente como fue solicitada por QA. Los `PR-###` son fases internas, no Pull Requests de GitHub.

### Registro de fases creadas

| PR | Estado | Alcance autorizado | ISSUE | Resultado/pendiente |
| --- | --- | --- | --- | --- |
| `PR-001` | `COMPLETED` | Cerrar el contrato inicial de Llamada. | `ISSUE-010` y decisiones históricas | Validado por QA. |
| `PR-002` | `COMPLETED` | Incorporar Llamada en backend y API. | `ISSUE-001`, `ISSUE-003`, `ISSUE-007` | Desplegado y validado por QA. |
| `PR-003` | `DEPLOYMENT WEB` | Crear botón, alta y formulario web; corregir campos finales. | `ISSUE-002`, `ISSUE-015` | Código entregado; espera validación final de QA. |
| `PR-004` | `TESTING` | Mostrar en web las fotos de Llamada en solo lectura y establecer máximo 10 en API. | `ISSUE-017` | QA validó que la carga web desapareció; PR abierto hasta probar API, fotos y descripciones con Android. |
| `PR-005` | `DEPLOYMENT WEB` | Completar visualización web, aprobación, PDF y acciones finales de Llamada. | `ISSUE-008`, `ISSUE-011`, `ISSUE-018` / `BUG-003`, `ISSUE-019` / `BUG-004` | Corrección de página residual entregada; QA debe desplegar, regenerar y validar el PDF. |

**Próximo PR disponible: `PR-006`.** Todavía no tiene alcance asignado ni autorización técnica.

### Registro Git por PR

| PR | Commit principal | Correcciones y evidencia |
| --- | --- | --- |
| `PR-001` | `696a595e6f37badb4c91fe583bbb96b385f23722` | `24ad246` |
| `PR-002` | `0932b3ed22980bc146c6363b9ee3d67a8f338b4d` | `7d6a509` |
| `PR-003` | `214b74a60c3e538d014d67404deb1318e303438f` | `24f598716f36708c6e4e6c03e526472091ab7a5c` (`ISSUE-015`) |
| `PR-004` | `293eeb21840b0b582eca5a424607233d5c6b15e5` | `03902d2d01e660192df51fcf729cc5e5c5108e4e` (`ISSUE-017`, documentación); `a79a54106fc5a9e0406f9b4c14e7f0be1b03050f` (`ISSUE-017`, corrección técnica) |
| `PR-005` | `87a5a7de2ab711f58fa06027a607880b6b248766` | `b888ff9067eebe0317a9388985cc7cd31ce80867` (definición); `a82118cd2f75b08b70c41884650febc5c3ce0f80` (`ISSUE-018`, documentación); `bad8134c36c6ba837066a2aef8008de0e4e3efbb` (`BUG-003`, corrección técnica) |

### Entrega actual de PR-004

Estado actual: `TESTING`, abierto.

QA validó en DEMO que el formulario web de Llamada ya no contiene controles fotográficos. La prueba de fotos, descripciones, máximo diez y visor quedará pendiente de la futura implementación Android, por lo que PR-004 no debe pasar todavía a `COMPLETED`.

### PR-005 — Visualización, aprobación, PDF y acciones web de Llamada

**Estado:** `DEPLOYMENT WEB`. QA validó el flujo funcional; la corrección de `BUG-004` está lista para desplegar y probar. El PR permanece abierto.

**REQ relacionado:** `REQ-001`.

**ISSUES que resolverá:**

- `ISSUE-008`: habilitar la visualización correcta y las acciones finales de la fila Llamada.
- `ISSUE-011`: crear la plantilla PDF definitiva y vincularla al proceso de aprobación.

**Implementación ejecutada:**

- Activar el icono de visualización para abrir una vista de solo lectura específica de Llamada.
- Mostrar logo, título automático, todos los campos, `La empresa`, `Cliente`, fotos generales y sus descripciones.
- Permitir aprobar el reporte desde su visualización respetando el bloqueo de estado.
- Generar el PDF definitivo en backend al aprobar; si falla, no debe informar aprobación completa ni habilitar acciones dependientes.
- Habilitar el icono PDF y WhatsApp únicamente cuando exista un PDF vigente.
- Compartir por WhatsApp la URL temporal firmada del PDF.
- Mantener eliminación solo para pendientes y conservar la opción web de volver un aprobado a `PENDIENTE`.
- No incorporar todavía la interfaz Android de Llamada.

Detalles técnicos verificados por Dev:

- El listado dirige Llamada exclusivamente a `view_llamada.php` y conserva las rutas existentes de Elevador/ALIMAK.
- La nueva vista valida el ID, la existencia del registro y el tipo antes de mostrar datos.
- Todo texto dinámico de la vista se escapa y los campos narrativos preservan saltos de línea.
- Las fotografías reutilizan las URLs firmadas, miniaturas, descripciones y visor común en modo de solo lectura.
- La aprobación reutiliza la transacción backend existente: primero genera el PDF y solo después confirma el estado aprobado.
- El PDF incluye logo, título automático, datos generales, fotos/descripciones, campos narrativos y textos `La empresa`/`Cliente`.
- El generador PDF preserva saltos de línea explícitos sin alterar el contrato de Elevador o ALIMAK.
- La lista y la vista solo ofrecen PDF/WhatsApp cuando `report_pdf_active_url()` confirma un PDF activo de un reporte aprobado.
- Reabrir invalida el PDF anterior y devuelve el reporte a pendiente.

**Criterios de aceptación propuestos:**

1. El ojo abre el reporte Llamada correcto y nunca una plantilla de Elevador o ALIMAK.
2. Todos los textos se muestran escapados, completos y preservando saltos de línea.
3. Las fotos generales se presentan con descripción y visor, sin controles de edición web.
4. Aprobar genera un PDF vigente y bloquea edición/eliminación.
5. PDF y WhatsApp están deshabilitados antes de aprobar y habilitados después de una generación correcta.
6. Volver a `PENDIENTE` invalida el PDF anterior y vuelve a permitir edición desde el canal autorizado.
7. Elevador y ALIMAK superan una prueba básica de regresión de listado, visualización y acciones.

**Commit principal ejecutado:** `87a5a7de2ab711f58fa06027a607880b6b248766` — `feat(PR-005): completar visualizacion y PDF de Llamada`.
