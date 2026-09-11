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
- Un BUG encontrado en un PR abierto devuelve ese PR a `DEVELOPING`, recibe un ISSUE nuevo y conserva el mismo número de PR.
- No se crean fases futuras ni se reservan números PR sin que QA solicite definirlas.

### Control de identificadores

| Registro | Último ID utilizado | Próximo ID disponible | Regla |
| --- | --- | --- | --- |
| Requisito | `REQ-001` | `REQ-002` | Solo se crea otro REQ si QA aprueba un comportamiento independiente. |
| Issue o BUG | `ISSUE-017` | `ISSUE-018` | Todo trabajo o defecto nuevo toma el siguiente número; los IDs retirados nunca se reutilizan. |
| Fase interna | `PR-004` | `PR-005` | Solo se asigna cuando QA solicita describir una nueva fase. No representa un Pull Request de GitHub. |

Por tanto, el próximo BUG que QA identifique será `ISSUE-018`, salvo que sea exactamente otra manifestación de un ISSUE ya abierto.

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
- La visualización final, aprobación, PDF definitivo, acciones de listado y experiencia Android de Llamada continúan pendientes de fases que QA todavía no ha creado.
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
10. La aprobación debe producir un PDF definitivo con logo, título, campos, fotografías y descripciones.
11. PDF y WhatsApp solo se habilitan cuando existe un PDF vigente.
12. La APK deberá listar, filtrar, crear, editar, fotografiar, visualizar, aprobar, abrir PDF y compartir Llamada.

**ISSUES relacionados:** `ISSUE-001`, `ISSUE-002`, `ISSUE-003`, `ISSUE-007`, `ISSUE-008`, `ISSUE-009`, `ISSUE-010`, `ISSUE-011`, `ISSUE-013`, `ISSUE-014`, `ISSUE-015`, `ISSUE-017`.

No existen otros requisitos activos. Los anteriores `REQ-002` a `REQ-011` eran una fragmentación documental no autorizada y quedaron absorbidos en `REQ-001`; esos números no se consideran requisitos aprobados.

## ISSUES

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
| `ISSUE-008` | Trabajo | `OPEN — UNASSIGNED` | Faltan visualización y acciones finales del listado para Llamada. | Sin PR |
| `ISSUE-009` | Riesgo | `OPEN — UNASSIGNED` | Verificar compatibilidad de clientes ante el nuevo tipo. | Sin PR |
| `ISSUE-010` | Decisión | `RESOLVED` | Definir título automático de Llamada. | `PR-001` |
| `ISSUE-011` | Trabajo | `OPEN — UNASSIGNED` | Falta plantilla PDF definitiva de Llamada. | Sin PR |
| `ISSUE-012` | Retirado | `SUPERSEDED` | Control de proceso duplicado por Gobierno del proyecto. | Sin PR |
| `ISSUE-013` | Trabajo | `OPEN — UNASSIGNED` | La APK todavía no incorpora el tipo Llamada. | Sin PR |
| `ISSUE-014` | Riesgo | `OPEN — UNASSIGNED` | Falta regresión integral de Elevador y ALIMAK. | Sin PR |
| `ISSUE-015` | BUG | `IMPLEMENTED — PENDING QA` | Los campos finales se implementaron erróneamente como firmas. | `PR-003` |
| `ISSUE-016` | Retirado | `SUPERSEDED` | Gestión fotográfica web basada en alcance incorrecto. | `PR-004` |
| `ISSUE-017` | BUG | `IMPLEMENTED — DEPLOYMENT WEB` | La web permitía gestionar fotos y el límite de Llamada era cinco. | `PR-004` |

**Próximo ISSUE disponible: `ISSUE-018`.**

### ISSUE-008 — Visualización y acciones finales de Llamada

La fila todavía no puede abrir una vista final de Llamada ni completar aprobación, PDF y WhatsApp. No es un BUG de `PR-003`, porque esas funciones no pertenecían al formulario de alta/edición. Permanece sin PR hasta que QA solicite describir la siguiente fase.

### ISSUE-009 — Compatibilidad con clientes existentes

Antes de exponer Llamada en Android debe comprobarse que listado, parser, filtro y navegación no fallen ante el nuevo tipo.

### ISSUE-011 — PDF definitivo

Debe existir una plantilla multipágina que represente campos, textos de conformidad, fotos generales y sus descripciones. La aprobación no debe habilitar WhatsApp si el PDF no quedó generado correctamente.

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

## IMIPLEMENTATION

> Se conserva la palabra `IMIPLEMENTATION` exactamente como fue solicitada por QA. Los `PR-###` son fases internas, no Pull Requests de GitHub.

### Registro de fases creadas

| PR | Estado | Alcance autorizado | ISSUE | Resultado/pendiente |
| --- | --- | --- | --- | --- |
| `PR-001` | `COMPLETED` | Cerrar el contrato inicial de Llamada. | `ISSUE-010` y decisiones históricas | Validado por QA. |
| `PR-002` | `COMPLETED` | Incorporar Llamada en backend y API. | `ISSUE-001`, `ISSUE-003`, `ISSUE-007` | Desplegado y validado por QA. |
| `PR-003` | `DEPLOYMENT WEB` | Crear botón, alta y formulario web; corregir campos finales. | `ISSUE-002`, `ISSUE-015` | Código entregado; espera validación final de QA. |
| `PR-004` | `DEPLOYMENT WEB` | Mostrar en web las fotos de Llamada en solo lectura y establecer máximo 10 en API. | `ISSUE-017` | Corrección lista para DEMO; PR abierto hasta poder probar fotos cargadas por Android. |

**Próximo PR disponible: `PR-005`.** No tiene alcance asignado ni está autorizado. QA deberá solicitar su descripción antes de crearlo.

### Registro Git por PR

| PR | Commit principal | Correcciones y evidencia |
| --- | --- | --- |
| `PR-001` | `696a595e6f37badb4c91fe583bbb96b385f23722` | `24ad246` |
| `PR-002` | `0932b3ed22980bc146c6363b9ee3d67a8f338b4d` | `7d6a509` |
| `PR-003` | `214b74a60c3e538d014d67404deb1318e303438f` | `24f598716f36708c6e4e6c03e526472091ab7a5c` (`ISSUE-015`) |
| `PR-004` | `293eeb21840b0b582eca5a424607233d5c6b15e5` | `03902d2d01e660192df51fcf729cc5e5c5108e4e` (`ISSUE-017`, documentación); `a79a54106fc5a9e0406f9b4c14e7f0be1b03050f` (`ISSUE-017`, corrección técnica) |

### Entrega actual de PR-004

Estado de salida: `DEPLOYMENT WEB`.

QA deberá desplegar la corrección en DEMO. En esta etapa puede verificar que el formulario web de Llamada ya no contiene controles fotográficos. La prueba de fotos, descripciones, máximo diez y visor quedará pendiente de la futura implementación Android, por lo que PR-004 no debe pasar todavía a `COMPLETED`.
