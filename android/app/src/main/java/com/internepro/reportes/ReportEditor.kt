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

private const val MAX_GENERAL_PHOTOS = 5

private data class PhotoJob(
    val source: Uri,
    val compressed: Uri? = null,
    val status: String,
    val error: String = "",
    val comment: String = ""
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
    val storedPhotos = remember(report.id) { mutableStateListOf<ReportPhoto>().apply { addAll(report.photos()) } }
    var deletePhotoCandidate by remember { mutableStateOf<String?>(null) }
    var photoVersion by remember { mutableIntStateOf(0) }
    var cameraUri by remember { mutableStateOf<Uri?>(null) }
    fun syncStoredPhotos() {
        val updated = org.json.JSONArray()
        storedPhotos.forEach { updated.put(it.toJson()) }
        report.checklist.put("_photos", updated)
    }
    fun preparePhoto(source: Uri, existingIndex: Int? = null) {
        if (existingIndex == null && storedPhotos.size + photos.size >= MAX_GENERAL_PHOTOS) {
            saveError = "Solo se permiten $MAX_GENERAL_PHOTOS fotografias generales por reporte."
            return
        }
        val index = existingIndex ?: photos.size
        val previousComment = existingIndex?.let { photos[it].comment }.orEmpty()
        if (existingIndex == null) {
            photos.add(PhotoJob(source, status = "Comprimiendo"))
        } else {
            photos[index] = PhotoJob(source, status = "Comprimiendo", comment = previousComment)
        }
        Thread {
            try {
                val compressed = PhotoProcessor.compress(context, source)
                val currentComment = photos.getOrNull(index)?.comment ?: previousComment
                photos[index] = PhotoJob(source, compressed, "Lista para guardar", comment = currentComment)
            } catch (error: Exception) {
                val currentComment = photos.getOrNull(index)?.comment ?: previousComment
                photos[index] = PhotoJob(
                    source = source,
                    status = "Error",
                    error = error.message ?: "Error al subir",
                    comment = currentComment
                )
            } finally { photoVersion++ }
        }.start()
    }
    val photoPicker = rememberLauncherForActivityResult(ActivityResultContracts.GetMultipleContents()) { selected ->
        val remaining = (MAX_GENERAL_PHOTOS - storedPhotos.size - photos.size).coerceAtLeast(0)
        selected.take(remaining).forEach(::preparePhoto)
        if (selected.size > remaining) {
            saveError = "Se seleccionaron mas fotografias de las permitidas. El limite general es $MAX_GENERAL_PHOTOS."
        }
    }
    val camera = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { captured ->
        if (captured) cameraUri?.let(::preparePhoto)
    }
    fun deleteStoredPhoto(name: String) {
        Thread {
            try {
                ReportApi.deletePhoto(report.id, name)
                storedPhotos.removeAll { it.name == name }
                syncStoredPhotos()
                onPhotoDeleted(report.id, storedPhotos.firstOrNull()?.name)
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
                                        val path = ReportApi.uploadPhoto(
                                            context.contentResolver,
                                            report.id,
                                            photo.compressed!!,
                                            photo.comment.trim()
                                        )
                                        ReportApi.verifyPhoto(path)
                                        storedPhotos.add(
                                            ReportPhoto(
                                                name = path.substringAfterLast('/'),
                                                comment = photo.comment.trim()
                                            )
                                        )
                                        photos.removeAt(index)
                                    } catch (error: Exception) {
                                        photos[index] = photo.copy(status = "Error", error = error.message ?: "Error al subir")
                                        throw error
                                    }
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

            val generalPhotoCount = storedPhotos.size + photos.size
            Text("Fotografias generales ($generalPhotoCount/$MAX_GENERAL_PHOTOS)", style = MaterialTheme.typography.titleLarge)
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(
                    enabled = generalPhotoCount < MAX_GENERAL_PHOTOS && !saving,
                    onClick = { val uri = PhotoProcessor.createCameraUri(context); cameraUri = uri; camera.launch(uri) }
                ) { Text("Tomar fotografia") }
                OutlinedButton(
                    enabled = generalPhotoCount < MAX_GENERAL_PHOTOS && !saving,
                    onClick = { photoPicker.launch("image/*") }
                ) { Text("Seleccionar") }
            }
            if (generalPhotoCount >= MAX_GENERAL_PHOTOS) {
                Text("Limite de 5 fotografias generales alcanzado.", style = MaterialTheme.typography.bodySmall)
            }
            if (photos.isEmpty()) {
                Text("No hay fotografias nuevas pendientes.", style = MaterialTheme.typography.bodySmall)
            } else {
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    photos.forEachIndexed { index, photo ->
                        Column(modifier = Modifier.width(200.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            photo.compressed?.let { uri -> PhotoPreview(uri) }
                            OutlinedTextField(
                                value = photo.comment,
                                onValueChange = { value -> photos[index] = photos[index].copy(comment = value.take(500)) },
                                modifier = Modifier.fillMaxWidth(),
                                label = { Text("Comentario (opcional)") },
                                minLines = 2,
                                maxLines = 3,
                                supportingText = { Text("${photo.comment.length}/500") }
                            )
                            Text(photo.status, style = MaterialTheme.typography.labelSmall)
                            if (photos.none { it.status == "Comprimiendo" || it.status == "Subiendo" } &&
                                (photo.status == "Lista para guardar" || photo.status == "Error")) IconButton(onClick = { photos.removeAt(index) }) {
                                Icon(Icons.Filled.Close, contentDescription = "Quitar fotografia local")
                            }
                            if (photo.status == "Error") Text(photo.error, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.error)
                            if (photo.status == "Error") TextButton(onClick = { preparePhoto(photo.source, index) }) { Text("Reintentar") }
                        }
                    }
                }
            }
            if (storedPhotos.isNotEmpty()) {
                Text("Fotografias guardadas", style = MaterialTheme.typography.titleMedium)
                LazyRow(
                    modifier = Modifier.fillMaxWidth().height(260.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(storedPhotos, key = { it.name }) { storedPhoto ->
                        val index = storedPhotos.indexOfFirst { it.name == storedPhoto.name }
                        Column(
                            modifier = Modifier.width(200.dp),
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(4.dp)
                        ) {
                            AuthenticatedPhoto(
                                reportId = report.id,
                                name = storedPhoto.name,
                                contentDescription = "Fotografia guardada",
                                modifier = Modifier.fillMaxWidth().height(96.dp)
                            )
                            OutlinedTextField(
                                value = storedPhoto.comment,
                                onValueChange = { value ->
                                    if (index >= 0) storedPhotos[index] = storedPhotos[index].copy(comment = value.take(500))
                                },
                                modifier = Modifier.fillMaxWidth(),
                                label = { Text("Comentario (opcional)") },
                                minLines = 2,
                                maxLines = 3
                            )
                            IconButton(onClick = { deletePhotoCandidate = storedPhoto.name }) {
                                Icon(Icons.Filled.Delete, contentDescription = "Eliminar fotografia", tint = MaterialTheme.colorScheme.error)
                            }
                        }
                    }
                }
            }
            if (saveError.isNotBlank()) Text(saveError, color = MaterialTheme.colorScheme.error)

            Text("Checklist de mantenimiento", style = MaterialTheme.typography.titleLarge)
            ChecklistTemplates.forType(report.type).forEach { section ->
                ChecklistSectionEditor(
                    section = section,
                    checklist = report.checklist,
                    observations = report.observations,
                    onChanged = { stateVersion++ }
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
    onChanged: () -> Unit
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
