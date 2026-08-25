# Contexto del proyecto: Reporte de Mantenimiento Internepro

## Estado y propósito

Aplicación web PHP actualmente accesible en producción, destinada a registrar y consultar mantenimientos de equipos de elevación de Internepro. El entorno operativo indicado es Linux con Apache y MariaDB.

El repositorio contiene una aplicación PHP sin framework ni gestor de dependencias. La persistencia se realiza contra una única tabla MariaDB (`reporte`) y los formularios de inspección se almacenan como JSON.

**Regla de gobierno:** ningún cambio técnico, prueba, comando contra producción, instalación, migración o despliegue se ejecutará sin aprobación previa, explícita y verificable de QA. La documentación y sus fases son propuestas sujetas a dicha aprobación.

## Objetivos

### Objetivo principal

Construir una APK instalable en tablets Android que ofrezca toda la funcionalidad existente de la aplicación web:

- Listar reportes.
- Crear reportes de elevador y ALIMAK.
- Editar formularios completos y sus observaciones.
- Consultar reportes.
- Aprobar reportes.
- Eliminar reportes, respetando el modelo de permisos que QA y negocio definan.
- Compartir el acceso a un reporte por WhatsApp, cuando la tablet tenga esa aplicación disponible.

La APK deberá consumir una capa de API segura del sistema actual. No debe conectarse directamente a MariaDB ni reutilizar las páginas PHP como si fueran una API.

### Objetivo secundario

Conservar el acceso web existente sin interrumpirlo. Las mejoras de seguridad, datos y arquitectura deben ser compatibles con el portal web o aplicarse mediante una migración aprobada por QA. La web se mantendrá como canal operativo y podrá mejorarse gradualmente.

## Funcionalidad actual identificada

| Área | Comportamiento actual | Archivos principales |
| --- | --- | --- |
| Inicio y listado | Carga por AJAX los reportes y presenta enlaces de ver, editar, compartir y borrar. | `index.php`, `process.php` |
| Creación | Crea un registro vacío de tipo `elevador` o `alimak`. | `index.php`, `process.php` |
| Formulario elevador | Captura datos generales, lista de inspección, observaciones, comentarios y recomendaciones. | `edit.php`, `view.php` |
| Formulario ALIMAK | Captura datos generales, lista de inspección ALIMAK, observaciones, comentarios y recomendaciones. | `edit_alimak.php`, `view_alimak.php` |
| Aprobación | Marca el reporte como cerrado y guarda nombre del aprobador y fecha. | `view.php`, `view_alimak.php`, `process.php` |
| Eliminación | Elimina físicamente el reporte desde el listado. | `index.php`, `process.php` |
| Datos | Tabla `reporte` con información general y JSON para formularios/observaciones. | `db_registros_elevadores.sql` |

## Arquitectura actual

```text
Navegador web
  ├─ index.php ── AJAX ──> process.php
  ├─ edit.php / edit_alimak.php ── POST ──> process.php
  └─ view.php / view_alimak.php ── AJAX de aprobación ──> process.php
                                             │
                                             ▼
                                      MariaDB: reporte
```

No se identificó una capa formal de autenticación, API versionada, archivos de configuración segregados, pruebas automatizadas ni control de versiones Git en la carpeta analizada.

## Modelo de datos actual

Tabla `reporte`:

- `id`: identificador autonumérico.
- `state_reporte`: JSON con estado, aprobador, fecha y tipo de reporte.
- Datos generales: título, cliente, fecha, equipo y técnico.
- `data_reporte`: JSON de respuestas del checklist.
- `obs_reporte`: JSON de observaciones, comentarios y recomendaciones.
- `created_at`, `updated_at`: marcas temporales almacenadas como texto.

## Propuesta de APK e integración

Se propone una aplicación Android nativa en **Kotlin**, con interfaz **Jetpack Compose**, arquitectura MVVM y comunicación HTTPS mediante una **API REST PHP** nueva y versionada. Es la opción recomendada para una tablet de operación: permite controles de formulario consistentes, almacenamiento local y evolución independiente del portal web.

Alternativa aceptable: Flutter. No se recomienda empaquetar el sitio como WebView, porque conserva las debilidades actuales, limita el uso sin conexión y no proporciona una experiencia adecuada de tablet.

### Arquitectura objetivo

```text
APK Android (Kotlin)
  ├─ autenticación y sesión segura
  ├─ formularios de elevador y ALIMAK
  ├─ Room: borradores y cola de sincronización
  └─ HTTPS /api/v1
             │
             ▼
PHP API versionada
  ├─ validación y autorización
  ├─ consultas preparadas
  └─ contrato compartido con portal web
             │
             ▼
MariaDB
             ▲
             │
Portal web actual, migrado gradualmente a la misma capa de servicio/API
```

### Capacidades previstas para la APK

- Pantalla de acceso y perfiles definidos por negocio: técnico, supervisor/aprobador y administrador.
- Listado con búsqueda, estado, tipo y fecha.
- Creación y edición de ambos formularios.
- Validación de campos obligatorios y de valores permitidos en cada ítem del checklist.
- Borradores locales y sincronización posterior si se aprueba el uso sin conexión.
- Consulta de reportes aprobados y pendientes.
- Aprobación identificando al aprobador autenticado; no solo mediante un texto libre.
- Eliminación sujeta a autorización, confirmación y trazabilidad.
- Compartir enlaces web compatibles mediante Android Sharesheet/WhatsApp.
- Registro de errores sin exponer datos personales ni secretos.

### Decisiones que QA y negocio deben aprobar antes de implementar la APK

1. Estrategia de autenticación y perfiles.
2. Uso sin conexión, duración de los borradores y reglas de conflicto de sincronización.
3. Si la eliminación física se sustituye por archivado/auditoría.
4. Propiedad del reporte tras aprobación: editable, bloqueado o editable mediante reapertura autorizada.
5. Compatibilidad requerida con versiones Android, modelo de tablet y método de distribución (MDM, Play privado o instalación administrada).
6. Requisitos de evidencia: firma, foto, geolocalización, PDF o exportación. No forman parte de la funcionalidad actual y requieren alcance explícito.

## Plan de implementación propuesto

Todas las fases permanecen en `pending` hasta aprobación de QA.

| Fase | Estado | Alcance | Criterio de salida para QA |
| --- | --- | --- | --- |
| 0. Descubrimiento y contrato funcional | pending | Inventario de cada campo/regla de ambos formularios y definición de roles. | Matriz funcional aprobada. |
| 1. Estabilización y seguridad del backend | pending | Resolver los hallazgos de `DEPLOY_PROJECT.md` y crear configuración segura. | Pruebas de regresión y revisión de seguridad aprobadas. |
| 2. API REST versionada | pending | Endpoints autenticados para listado, detalle, crear, editar, aprobar y eliminar/archivar. | Contrato OpenAPI y pruebas de API aprobadas. |
| 3. Compatibilidad web | pending | Adaptar el portal existente de forma incremental para usar servicios seguros sin romper URLs ni flujos. | Pruebas funcionales web aprobadas. |
| 4. APK Android base | pending | Navegación, autenticación, listados y detalle. | APK de QA instalada y casos base aprobados. |
| 5. Formularios completos | pending | Implementación de checklists elevador y ALIMAK, validaciones y borradores. | Paridad funcional con web aprobada. |
| 6. Sincronización y distribución | pending | Persistencia local si se aprueba, sincronización, firma y distribución controlada. | Prueba en tablet objetivo y plan de reversión aprobados. |
| 7. Puesta en producción | pending | Despliegue gradual, monitoreo y soporte inicial. | Evidencia de liberación firmada por QA. |

## Protocolo de cambios

1. Se documenta una propuesta y su impacto.
2. QA aprueba explícitamente la fase y el alcance técnico.
3. Se implementa solo el alcance aprobado, sin cambios colaterales.
4. QA valida en su ambiente designado.
5. Se actualiza el estado de la fase y la evidencia de revisión.

