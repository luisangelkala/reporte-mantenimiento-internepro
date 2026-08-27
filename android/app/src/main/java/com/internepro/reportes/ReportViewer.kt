package com.internepro.reportes

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ReportViewer(report: ReportDetail, onBack: () -> Unit, onApproved: (ReportDetail) -> Unit) {
    var approver by remember { mutableStateOf("") }
    var confirmApproval by remember { mutableStateOf(false) }
    var approving by remember { mutableStateOf(false) }
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
}
