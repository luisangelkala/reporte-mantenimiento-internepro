package com.internepro.reportes

import android.graphics.BitmapFactory
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.Image
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp

private const val MAX_PHOTOS_PER_BUCKET = 5
private val ALIMAK_PHOTO_SECTION_KEYS = setOf("a_2", "a_9", "a_15", "a_22", "a_28", "a_32")

private data class PhotoJob(
    val source: Uri,
    val compressed: Uri? = null,
    val status: String,
    val error: String = "",
    val comment: String = "",
    val sectionKey: String? = null
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ReportEditor(
    report: ReportDetail,
    onBack: () -> Unit,
    onSaved: (String) -> Unit,
    onPhotoDeleted: (reportId: Int, nextCover: String?) -> Unit
) {
    val context = LocalContext.current
    var title by remember(report.id) { mutableStateOf(report.title) }
    var client by remember(report.id) { mutableStateOf(report.client) }
    var date by remember(report.id) { mutableStateOf(report.date) }
    var equipment by remember(report.id) { mutableStateOf(report.equipment) }
    var technician by remember(report.id) { mutableStateOf(report.technician) }
    var saving by remember { mutableStateOf(false) }
    var saveError by remember { mutableStateOf("") }
    var stateVersion by remember { mutableIntStateOf(0) }
    val photos = remember { mutableStateListOf<PhotoJob>() }
    val storedPhotos = remember(report.id) { mutableStateListOf<ReportPhoto>().apply { addAll(report.allPhotos()) } }
    var deletePhotoCandidate by remember { mutableStateOf<String?>(null) }
    var photoVersion by remember { mutableIntStateOf(0) }
    var cameraUri by remember { mutableStateOf<Uri?>(null) }
    var cameraSectionKey by remember { mutableStateOf<String?>(null) }
    var pickerSectionKey by remember { mutableStateOf<String?>(null) }
    fun storedInBucket(sectionKey: String?): List<ReportPhoto> = storedPhotos.filter {
        if (sectionKey == null) it.scope == "general" else it.scope == "section" && it.sectionKey == sectionKey
    }
    fun pendingInBucket(sectionKey: String?): List<PhotoJob> = photos.filter { it.sectionKey == sectionKey }
    fun bucketCount(sectionKey: String?): Int = storedInBucket(sectionKey).size + pendingInBucket(sectionKey).size
    fun syncStoredPhotos() {
        val updated = org.json.JSONArray()
        storedPhotos.forEach { updated.put(it.toJson()) }
        report.checklist.put("_photos", updated)
    }
    fun preparePhoto(source: Uri, sectionKey: String?, existingIndex: Int? = null) {
        if (existingIndex == null && bucketCount(sectionKey) >= MAX_PHOTOS_PER_BUCKET) {
            saveError = "Solo se permiten $MAX_PHOTOS_PER_BUCKET fotografias por bloque."
            return
        }
        val index = existingIndex ?: photos.size
        val previousComment = existingIndex?.let { photos[it].comment }.orEmpty()
        if (existingIndex == null) {
            photos.add(PhotoJob(source, status = "Comprimiendo", sectionKey = sectionKey))
        } else {
            photos[index] = PhotoJob(source, status = "Comprimiendo", comment = previousComment, sectionKey = sectionKey)
        }
        Thread {
            try {
                val compressed = PhotoProcessor.compress(context, source)
                val currentComment = photos.getOrNull(index)?.comment ?: previousComment
                photos[index] = PhotoJob(source, compressed, "Lista para guardar", comment = currentComment, sectionKey = sectionKey)
            } catch (error: Exception) {
                val currentComment = photos.getOrNull(index)?.comment ?: previousComment
                photos[index] = PhotoJob(
                    source = source,
                    status = "Error",
                    error = error.message ?: "Error al subir",
                    comment = currentComment,
                    sectionKey = sectionKey
                )
            } finally { photoVersion++ }
        }.start()
    }
    val photoPicker = rememberLauncherForActivityResult(ActivityResultContracts.GetMultipleContents()) { selected ->
        val target = pickerSectionKey
        val remaining = (MAX_PHOTOS_PER_BUCKET - bucketCount(target)).coerceAtLeast(0)
        selected.take(remaining).forEach { preparePhoto(it, target) }
        if (selected.size > remaining) {
            saveError = "Se seleccionaron mas fotografias de las permitidas. El limite por bloque es $MAX_PHOTOS_PER_BUCKET."
        }
    }
    val camera = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { captured ->
        if (captured) cameraUri?.let { preparePhoto(it, cameraSectionKey) }
    }
    fun launchCamera(sectionKey: String?) {
        cameraSectionKey = sectionKey
        val uri = PhotoProcessor.createCameraUri(context)
        cameraUri = uri
        camera.launch(uri)
    }
    fun launchPicker(sectionKey: String?) {
        pickerSectionKey = sectionKey
        photoPicker.launch("image/*")
    }
    fun deleteStoredPhoto(name: String) {
        Thread {
            try {
                ReportApi.deletePhoto(report.id, name)
                storedPhotos.removeAll { it.name == name }
                syncStoredPhotos()
                onPhotoDeleted(report.id, storedPhotos.firstOrNull { it.scope == "general" }?.name)
            } catch (error: Exception) {
                saveError = error.message ?: "No se pudo eliminar la fotografia."
            }
        }.start()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (report.type == "alimak") "Editar reporte ALIMAK" else "Editar reporte Elevador") },
                navigationIcon = { TextButton(onClick = onBack) { Text("Volver") } }
            )
        },
        bottomBar = {
            Surface(shadowElevation = 6.dp) {
                Button(
                    enabled = !saving && photos.none { it.status == "Comprimiendo" || it.status == "Subiendo" },
                    modifier = Modifier.fillMaxWidth().padding(16.dp),
                    onClick = {
                        saveError = ""
                        saving = true
                        report.title = title
                        report.client = client
                        report.date = date
                        report.equipment = equipment
                        report.technician = technician
                        syncStoredPhotos()
                        Thread {
                            try {
                                ReportApi.saveReport(report)
                                photos.filter { it.status == "Lista para guardar" && it.compressed != null }.forEach { photo ->
                                    val index = photos.indexOf(photo)
                                    try {
                                        photos[index] = photo.copy(status = "Subiendo")
                                        val uploaded = ReportApi.uploadPhoto(
                                            context.contentResolver,
                                            report.id,
                                            photo.compressed!!,
                                            photo.comment.trim(),
                                            scope = if (photo.sectionKey == null) "general" else "section",
                                            sectionKey = photo.sectionKey
                                        )
                                        ReportApi.verifyPhoto(uploaded.path)
                                        storedPhotos.add(uploaded.photo.copy(comment = photo.comment.trim()))
                                        photos.removeAt(index)
                                    } catch (error: Exception) {
                                        photos[index] = photo.copy(status = "Error", error = error.message ?: "Error al subir")
                                        throw error
                                    }
                                    }
                                    val serverMetadata = ReportApi.getReport(report.id).allPhotos().associateBy { it.name }
                                    storedPhotos.indices.forEach { storedIndex ->
                                        val local = storedPhotos[storedIndex]
                                        val server = serverMetadata[local.name]
                                        if (server != null && local.uploadedAt.isBlank()) {
                                            storedPhotos[storedIndex] = local.copy(uploadedAt = server.uploadedAt)
                                        }
                                    }
                                    syncStoredPhotos()
                                    ReportApi.saveReport(report)
                                    val persistedPhotos = ReportApi.getReport(report.id).allPhotos().associateBy { it.name }
                                    val missingMetadata = storedPhotos.firstOrNull { expected ->
                                        val persisted = persistedPhotos[expected.name]
                                        persisted == null || persisted.comment.trim() != expected.comment.trim() ||
                                            persisted.scope != expected.scope || persisted.sectionKey != expected.sectionKey
                                    }
                                    if (missingMetadata != null) {
                                        throw IllegalStateException("El servidor no confirmo el comentario de una fotografia.")
                                    }
                                    onSaved("Reporte guardado.")
                            } catch (error: Exception) {
                                saveError = error.message ?: "No se pudo guardar el reporte."
                            } finally {
                                saving = false
                            }
                        }.start()
                    }
                ) { Text(if (saving) "Guardando..." else "Guardar reporte") }
            }
        }
    ) { padding ->
        Column(
            modifier = Modifier.padding(padding).padding(16.dp).verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text("Datos generales", style = MaterialTheme.typography.titleLarge)
            OutlinedTextField(title, { title = it }, Modifier.fillMaxWidth(), label = { Text("Titulo del reporte") })
            OutlinedTextField(client, { client = it }, Modifier.fillMaxWidth(), label = { Text("Cliente") })
            OutlinedTextField(date, { date = it }, Modifier.fillMaxWidth(), label = { Text("Fecha (AAAA-MM-DD)") })
            OutlinedTextField(equipment, { equipment = it }, Modifier.fillMaxWidth(), label = { Text("Equipo") })
            OutlinedTextField(technician, { technician = it }, Modifier.fillMaxWidth(), label = { Text("Tecnico") })

            PhotoBucketEditor(
                title = "Fotografias generales",
                reportId = report.id,
                pendingPhotos = pendingInBucket(null),
                storedPhotos = storedInBucket(null),
                saving = saving,
                canModifyPending = photos.none { it.status == "Comprimiendo" || it.status == "Subiendo" },
                onTakePhoto = { launchCamera(null) },
                onSelectPhotos = { launchPicker(null) },
                onPendingComment = { pending, value ->
                    val index = photos.indexOf(pending)
                    if (index >= 0) photos[index] = photos[index].copy(comment = value.take(500))
                },
                onRemovePending = { pending -> photos.remove(pending) },
                onRetryPending = { pending ->
                    val index = photos.indexOf(pending)
                    if (index >= 0) preparePhoto(pending.source, pending.sectionKey, index)
                },
                onStoredComment = { stored, value ->
                    val index = storedPhotos.indexOfFirst { it.name == stored.name }
                    if (index >= 0) storedPhotos[index] = storedPhotos[index].copy(comment = value.take(500))
                },
                onDeleteStored = { deletePhotoCandidate = it }
            )
            if (saveError.isNotBlank()) Text(saveError, color = MaterialTheme.colorScheme.error)

            Text("Checklist de mantenimiento", style = MaterialTheme.typography.titleLarge)
            ChecklistTemplates.forType(report.type).forEach { section ->
                ChecklistSectionEditor(
                    section = section,
                    checklist = report.checklist,
                    observations = report.observations,
                    onChanged = { stateVersion++ },
                    photoContent = if (report.type == "alimak" && section.key in ALIMAK_PHOTO_SECTION_KEYS) {
                        {
                            PhotoBucketEditor(
                                title = "Fotografias de ${section.title}",
                                reportId = report.id,
                                pendingPhotos = pendingInBucket(section.key),
                                storedPhotos = storedInBucket(section.key),
                                saving = saving,
                                canModifyPending = photos.none { it.status == "Comprimiendo" || it.status == "Subiendo" },
                                onTakePhoto = { launchCamera(section.key) },
                                onSelectPhotos = { launchPicker(section.key) },
                                onPendingComment = { pending, value ->
                                    val index = photos.indexOf(pending)
                                    if (index >= 0) photos[index] = photos[index].copy(comment = value.take(500))
                                },
                                onRemovePending = { pending -> photos.remove(pending) },
                                onRetryPending = { pending ->
                                    val index = photos.indexOf(pending)
                                    if (index >= 0) preparePhoto(pending.source, pending.sectionKey, index)
                                },
                                onStoredComment = { stored, value ->
                                    val index = storedPhotos.indexOfFirst { it.name == stored.name }
                                    if (index >= 0) storedPhotos[index] = storedPhotos[index].copy(comment = value.take(500))
                                },
                                onDeleteStored = { deletePhotoCandidate = it }
                            )
                        }
                    } else null
                )
            }

            var comment by remember(report.id) { mutableStateOf(report.observations.optString("ob_comentario")) }
            var recommendation by remember(report.id) { mutableStateOf(report.observations.optString("ob_recomendacion")) }
            Text("Observaciones", style = MaterialTheme.typography.titleLarge)
            OutlinedTextField(comment, { comment = it; report.observations.put("ob_comentario", it) }, Modifier.fillMaxWidth(), label = { Text("Comentarios") }, minLines = 3)
            OutlinedTextField(recommendation, { recommendation = it; report.observations.put("ob_recomendacion", it) }, Modifier.fillMaxWidth(), label = { Text("Recomendacion") }, minLines = 3)
            Text("Version de checklist: $stateVersion", style = MaterialTheme.typography.labelSmall)
        }
    }
    deletePhotoCandidate?.let { name ->
        AlertDialog(
            onDismissRequest = { deletePhotoCandidate = null },
            title = { Text("Eliminar fotografia") },
            text = { Text("La fotografia se eliminara de la app y del servidor. Esta accion no se puede deshacer.") },
            dismissButton = { TextButton(onClick = { deletePhotoCandidate = null }) { Text("Cancelar") } },
            confirmButton = {
                TextButton(onClick = { deletePhotoCandidate = null; deleteStoredPhoto(name) }) {
                    Text("Eliminar", color = MaterialTheme.colorScheme.error)
                }
            }
        )
    }
}

@Composable
private fun PhotoBucketEditor(
    title: String,
    reportId: Int,
    pendingPhotos: List<PhotoJob>,
    storedPhotos: List<ReportPhoto>,
    saving: Boolean,
    canModifyPending: Boolean,
    onTakePhoto: () -> Unit,
    onSelectPhotos: () -> Unit,
    onPendingComment: (PhotoJob, String) -> Unit,
    onRemovePending: (PhotoJob) -> Unit,
    onRetryPending: (PhotoJob) -> Unit,
    onStoredComment: (ReportPhoto, String) -> Unit,
    onDeleteStored: (String) -> Unit
) {
    val count = pendingPhotos.size + storedPhotos.size
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text("$title ($count/$MAX_PHOTOS_PER_BUCKET)", style = MaterialTheme.typography.titleMedium)
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Button(enabled = count < MAX_PHOTOS_PER_BUCKET && !saving, onClick = onTakePhoto) {
                Text("Tomar fotografia")
            }
            OutlinedButton(enabled = count < MAX_PHOTOS_PER_BUCKET && !saving, onClick = onSelectPhotos) {
                Text("Seleccionar")
            }
        }
        if (count >= MAX_PHOTOS_PER_BUCKET) {
            Text("Limite de 5 fotografias alcanzado para este bloque.", style = MaterialTheme.typography.bodySmall)
        }
        if (pendingPhotos.isNotEmpty()) {
            Row(
                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                pendingPhotos.forEach { photo ->
                    Column(modifier = Modifier.width(200.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        photo.compressed?.let { uri -> PhotoPreview(uri) }
                        OutlinedTextField(
                            value = photo.comment,
                            onValueChange = { onPendingComment(photo, it) },
                            modifier = Modifier.fillMaxWidth(),
                            label = { Text("Comentario (opcional)") },
                            minLines = 2,
                            maxLines = 3,
                            supportingText = { Text("${photo.comment.length}/500") }
                        )
                        Text(photo.status, style = MaterialTheme.typography.labelSmall)
                        if (canModifyPending && (photo.status == "Lista para guardar" || photo.status == "Error")) {
                            IconButton(onClick = { onRemovePending(photo) }) {
                                Icon(Icons.Filled.Close, contentDescription = "Quitar fotografia local")
                            }
                        }
                        if (photo.status == "Error") {
                            Text(photo.error, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.error)
                            TextButton(onClick = { onRetryPending(photo) }) { Text("Reintentar") }
                        }
                    }
                }
            }
        }
        if (storedPhotos.isNotEmpty()) {
            Text("Fotografias guardadas", style = MaterialTheme.typography.labelLarge)
            LazyRow(
                modifier = Modifier.fillMaxWidth().height(260.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(storedPhotos, key = { it.name }) { storedPhoto ->
                    Column(
                        modifier = Modifier.width(200.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        AuthenticatedPhoto(
                            reportId = reportId,
                            name = storedPhoto.name,
                            contentDescription = "Fotografia guardada en $title",
                            modifier = Modifier.fillMaxWidth().height(96.dp)
                        )
                        OutlinedTextField(
                            value = storedPhoto.comment,
                            onValueChange = { onStoredComment(storedPhoto, it.take(500)) },
                            enabled = !saving,
                            modifier = Modifier.fillMaxWidth(),
                            label = { Text("Comentario (opcional)") },
                            minLines = 2,
                            maxLines = 3
                        )
                        IconButton(enabled = !saving, onClick = { onDeleteStored(storedPhoto.name) }) {
                            Icon(Icons.Filled.Delete, contentDescription = "Eliminar fotografia", tint = MaterialTheme.colorScheme.error)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ChecklistControl(item: ChecklistItem, value: String, onValue: (String) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
        Text(item.label, modifier = Modifier.weight(1f), style = MaterialTheme.typography.bodyMedium)
        Box {
            OutlinedButton(onClick = { expanded = true }) { Text(value.ifBlank { "Marcar" }) }
            DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                listOf("", "OK", "X", "R").forEach { option ->
                    DropdownMenuItem(
                        text = { Text(option.ifBlank { "Marcar" }) },
                        onClick = { onValue(option); expanded = false }
                    )
                }
            }
        }
    }
}

@Composable
private fun ChecklistSectionEditor(
    section: ChecklistSection,
    checklist: org.json.JSONObject,
    observations: org.json.JSONObject,
    onChanged: () -> Unit,
    photoContent: (@Composable () -> Unit)? = null
) {
    var expanded by remember(section.title, section.observationKey) { mutableStateOf(section.observationKey == null) }
    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)) {
        Column(modifier = Modifier.padding(10.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            TextButton(onClick = { expanded = !expanded }, modifier = Modifier.fillMaxWidth()) {
                Text(if (expanded) "− ${section.title}" else "+ ${section.title}", modifier = Modifier.fillMaxWidth())
            }
            if (expanded) {
                section.items.forEach { item ->
                    ChecklistControl(
                        item = item,
                        value = checklist.optString(item.key),
                        onValue = { value -> checklist.put(item.key, value); onChanged() }
                    )
                }
                section.observationKey?.let { key ->
                    var observation by remember(key) { mutableStateOf(observations.optString(key)) }
                    OutlinedTextField(
                        value = observation,
                        onValueChange = { value -> observation = value; observations.put(key, value); onChanged() },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Observaciones de ${section.title}") },
                        minLines = 2
                    )
                }
                photoContent?.invoke()
            }
        }
    }
}

@Composable
private fun PhotoPreview(uri: Uri) {
    val context = LocalContext.current
    val bitmap = remember(uri) {
        context.contentResolver.openInputStream(uri)?.use { BitmapFactory.decodeStream(it)?.asImageBitmap() }
    }
    if (bitmap != null) {
        Card(modifier = Modifier.size(96.dp)) {
            Image(bitmap = bitmap, contentDescription = "Vista previa de fotografia", modifier = Modifier.fillMaxSize(), contentScale = ContentScale.Crop)
        }
    }
}
