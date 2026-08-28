package com.internepro.reportes

import android.os.Handler
import android.os.Looper
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowLeft
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.filled.Close
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.unit.dp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ReportViewer(report: ReportDetail, onBack: () -> Unit, onApproved: (ReportDetail) -> Unit) {
    val mainHandler = remember { Handler(Looper.getMainLooper()) }
    var approver by remember { mutableStateOf("") }
    var confirmApproval by remember { mutableStateOf(false) }
    var approving by remember { mutableStateOf(false) }
    var approvalError by remember { mutableStateOf("") }
    var fullScreenPhotos by remember { mutableStateOf<List<ReportPhoto>>(emptyList()) }
    var fullScreenIndex by remember { mutableIntStateOf(0) }
    Scaffold(topBar = { TopAppBar(title = { Text("Reporte #${report.id}") }, navigationIcon = { TextButton(onClick = onBack) { Text("Volver") } }) }) { padding ->
        Column(Modifier.padding(padding).padding(16.dp).verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Text(report.title, style = MaterialTheme.typography.headlineSmall)
            Text("Cliente: ${report.client}")
            Text("Fecha: ${report.date}")
            Text("Equipo: ${report.equipment}")
            Text("Tecnico: ${report.technician}")
            if (report.status == "close") {
                AssistChip(onClick = { }, label = { Text("APROBADO: ${report.approvedBy} - ${report.approvedDate}") })
            } else {
                OutlinedTextField(approver, { approver = it }, Modifier.fillMaxWidth(), label = { Text("Nombre de quien aprueba") })
                Button(enabled = approver.isNotBlank() && !approving, onClick = { confirmApproval = true }, modifier = Modifier.fillMaxWidth()) { Text("Aprobar reporte") }
                if (approving) {
                    LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
                    Text("Generando y verificando PDF...")
                }
                if (approvalError.isNotBlank()) {
                    Text(approvalError, color = MaterialTheme.colorScheme.error)
                }
            }
            ViewerPhotoGroup(
                reportId = report.id,
                title = "Fotografias generales",
                photos = report.photos(),
                onOpen = { group, index -> fullScreenPhotos = group; fullScreenIndex = index }
            )
            Text("Checklist", style = MaterialTheme.typography.titleLarge)
            ChecklistTemplates.forType(report.type).forEach { section ->
                Card {
                    Column(Modifier.padding(10.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
                        Text(section.title, style = MaterialTheme.typography.titleMedium)
                        section.items.forEach { item ->
                            Row(Modifier.fillMaxWidth()) {
                                Text(item.label, modifier = Modifier.weight(1f))
                                Text(report.checklist.optString(item.key).ifBlank { "Sin marcar" })
                            }
                        }
                        section.observationKey?.let { key -> report.observations.optString(key).takeIf { it.isNotBlank() }?.let { Text("Observaciones: $it") } }
                        if (report.type == "alimak" && section.key in ALIMAK_PHOTO_SECTIONS) {
                            ViewerPhotoGroup(
                                reportId = report.id,
                                title = "Fotografias de ${ALIMAK_PHOTO_SECTIONS[section.key]}",
                                photos = report.sectionPhotos(section.key),
                                onOpen = { group, index -> fullScreenPhotos = group; fullScreenIndex = index }
                            )
                        }
                    }
                }
            }
            report.observations.optString("ob_comentario").takeIf { it.isNotBlank() }?.let { Text("Comentarios: $it") }
            report.observations.optString("ob_recomendacion").takeIf { it.isNotBlank() }?.let { Text("Recomendacion: $it") }
        }
    }
    if (confirmApproval) AlertDialog(
        onDismissRequest = { confirmApproval = false },
        title = { Text("Aprobar reporte") },
        text = { Text("El reporte sera aprobado por $approver y ya no podra eliminarse.") },
        dismissButton = { TextButton(onClick = { confirmApproval = false }) { Text("Cancelar") } },
        confirmButton = {
            TextButton(onClick = {
                approving = true
                approvalError = ""
                confirmApproval = false
                Thread {
                    try {
                        val approved = ReportApi.approveReport(report.id, approver)
                        mainHandler.post {
                            approving = false
                            onApproved(approved)
                        }
                    } catch (error: Exception) {
                        mainHandler.post {
                            approving = false
                            approvalError = error.message ?: "No se pudo aprobar ni generar el PDF."
                        }
                    }
                }.start()
            }) { Text("Aprobar") }
        }
    )
    fullScreenPhotos.getOrNull(fullScreenIndex)?.let { photo ->
        Dialog(
            onDismissRequest = { fullScreenPhotos = emptyList() },
            properties = DialogProperties(usePlatformDefaultWidth = false)
        ) {
            Box(
                modifier = Modifier.fillMaxSize().background(Color.Black),
                contentAlignment = Alignment.Center
            ) {
                AuthenticatedPhoto(
                    reportId = report.id,
                    name = photo.name,
                    contentDescription = "Fotografia a pantalla completa",
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Fit,
                    maxDecodeDimension = null
                )
                IconButton(
                    onClick = { fullScreenPhotos = emptyList() },
                    modifier = Modifier.align(Alignment.TopEnd).padding(12.dp)
                ) {
                    Icon(Icons.Filled.Close, contentDescription = "Cerrar fotografia", tint = Color.White)
                }
                if (fullScreenPhotos.size > 1) {
                    IconButton(
                        onClick = { fullScreenIndex = (fullScreenIndex - 1 + fullScreenPhotos.size) % fullScreenPhotos.size },
                        modifier = Modifier.align(Alignment.CenterStart).padding(8.dp)
                    ) {
                        Icon(Icons.AutoMirrored.Filled.KeyboardArrowLeft, contentDescription = "Fotografia anterior", tint = Color.White)
                    }
                    IconButton(
                        onClick = { fullScreenIndex = (fullScreenIndex + 1) % fullScreenPhotos.size },
                        modifier = Modifier.align(Alignment.CenterEnd).padding(8.dp)
                    ) {
                        Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = "Fotografia siguiente", tint = Color.White)
                    }
                }
                Column(
                    modifier = Modifier.align(Alignment.BottomCenter).fillMaxWidth()
                        .background(Color.Black.copy(alpha = 0.72f)).padding(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(
                        if (photo.scope == "general") "Fotografias generales" else ALIMAK_PHOTO_SECTIONS[photo.sectionKey].orEmpty(),
                        color = Color.White,
                        style = MaterialTheme.typography.titleMedium
                    )
                    Text(photo.comment.ifBlank { "Sin comentario" }, color = Color.White)
                    Text("${fullScreenIndex + 1} / ${fullScreenPhotos.size}", color = Color.White)
                }
            }
        }
    }
}

@Composable
private fun ViewerPhotoGroup(
    reportId: Int,
    title: String,
    photos: List<ReportPhoto>,
    onOpen: (List<ReportPhoto>, Int) -> Unit
) {
    if (photos.isEmpty()) return
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        Text(title, style = MaterialTheme.typography.titleMedium)
        LazyRow(
            modifier = Modifier.fillMaxWidth().height(180.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            items(photos, key = { it.name }) { photo ->
                val index = photos.indexOfFirst { it.name == photo.name }
                Card(modifier = Modifier.width(180.dp)) {
                    Column {
                        AuthenticatedPhoto(
                            reportId = reportId,
                            name = photo.name,
                            contentDescription = "Abrir fotografia de $title",
                            modifier = Modifier.fillMaxWidth().height(112.dp).clickable { onOpen(photos, index) }
                        )
                        Text(
                            photo.comment.ifBlank { "Sin comentario" },
                            modifier = Modifier.padding(8.dp),
                            style = MaterialTheme.typography.bodySmall,
                            maxLines = 3
                        )
                    }
                }
            }
        }
    }
}
