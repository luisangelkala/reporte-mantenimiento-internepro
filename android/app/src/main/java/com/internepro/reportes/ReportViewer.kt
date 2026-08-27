package com.internepro.reportes

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
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
    var approver by remember { mutableStateOf("") }
    var confirmApproval by remember { mutableStateOf(false) }
    var approving by remember { mutableStateOf(false) }
    var fullScreenPhoto by remember { mutableStateOf<String?>(null) }
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
            }
            if (report.photoNames().isNotEmpty()) {
                Text("Fotografias", style = MaterialTheme.typography.titleLarge)
                LazyRow(
                    modifier = Modifier.fillMaxWidth().height(104.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(report.photoNames(), key = { it }) { name ->
                        AuthenticatedPhoto(
                            reportId = report.id,
                            name = name,
                            contentDescription = "Abrir fotografia",
                            modifier = Modifier.size(104.dp).clickable { fullScreenPhoto = name }
                        )
                    }
                }
            }
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
        confirmButton = { TextButton(onClick = { approving = true; confirmApproval = false; Thread { try { onApproved(ReportApi.approveReport(report.id, approver)) } finally { approving = false } }.start() }) { Text("Aprobar") } }
    )
    fullScreenPhoto?.let { name ->
        Dialog(
            onDismissRequest = { fullScreenPhoto = null },
            properties = DialogProperties(usePlatformDefaultWidth = false)
        ) {
            Box(
                modifier = Modifier.fillMaxSize().background(Color.Black),
                contentAlignment = Alignment.Center
            ) {
                AuthenticatedPhoto(
                    reportId = report.id,
                    name = name,
                    contentDescription = "Fotografia a pantalla completa",
                    modifier = Modifier.fillMaxSize(),
                    contentScale = ContentScale.Fit,
                    maxDecodeDimension = null
                )
                IconButton(
                    onClick = { fullScreenPhoto = null },
                    modifier = Modifier.align(Alignment.TopEnd).padding(12.dp)
                ) {
                    Icon(Icons.Filled.Close, contentDescription = "Cerrar fotografia", tint = Color.White)
                }
            }
        }
    }
}
