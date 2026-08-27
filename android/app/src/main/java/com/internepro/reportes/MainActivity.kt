package com.internepro.reportes

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Image
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.unit.dp

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { App() }
    }
}

data class ReportSummary(
    val id: Int,
    val title: String,
    val type: String,
    val status: String,
    val createdAt: String,
    val coverPhoto: String? = null
)

@Composable
fun App() {
    var reports by remember { mutableStateOf(emptyList<ReportSummary>()) }
    var message by remember { mutableStateOf("Listo para cargar reportes.") }
    var loading by remember { mutableStateOf(false) }
    var editor by remember { mutableStateOf<ReportDetail?>(null) }
    var viewer by remember { mutableStateOf<ReportDetail?>(null) }
    var deleteCandidate by remember { mutableStateOf<ReportSummary?>(null) }
    var filterExpanded by remember { mutableStateOf(false) }
    var selectedFilter by remember { mutableStateOf("Todos") }
    val filteredReports = when (selectedFilter) {
        "Elevador" -> reports.filter { it.type.equals("elevador", ignoreCase = true) }
        "ALIMAK" -> reports.filter { it.type.equals("alimak", ignoreCase = true) }
        else -> reports
    }

    MaterialTheme(colorScheme = lightColorScheme(primary = Color(0xFFC8102E), secondary = Color(0xFF8A0E20))) {
        editor?.let { report ->
            ReportEditor(
                report = report,
                onBack = { editor = null },
                onSaved = { result ->
                    editor = null
                    loading = true
                    message = "Actualizando listado..."
                    Thread {
                        try {
                            reports = ReportApi.loadReports()
                            message = "$result ${reports.size} reportes cargados."
                        } catch (error: Exception) {
                            message = "$result No se pudo actualizar el listado: ${error.message ?: "error"}"
                        } finally {
                            loading = false
                        }
                    }.start()
                },
                onPhotoDeleted = { reportId, nextCover ->
                    reports = reports.map { if (it.id == reportId) it.copy(coverPhoto = nextCover) else it }
                }
            )
        } ?: viewer?.let { report ->
            ReportViewer(
                report = report,
                onBack = { viewer = null },
                onApproved = { approved ->
                    viewer = approved
                    message = "Reporte aprobado por ${approved.approvedBy}."
                    reports = reports.map { if (it.id == approved.id) it.copy(status = "close") else it }
                }
            )
        } ?: Scaffold { padding ->
            Column(
                modifier = Modifier.padding(padding).padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Image(
                    painter = painterResource(R.drawable.logo_internepro),
                    contentDescription = "Internepro",
                    modifier = Modifier.height(55.dp).width(180.dp).align(Alignment.CenterHorizontally)
                )
                Button(
                    enabled = !loading,
                    onClick = {
                        loading = true
                        message = "Cargando..."
                        Thread {
                            try {
                                reports = ReportApi.loadReports()
                                message = "${reports.size} reportes cargados."
                            } catch (error: Exception) {
                                message = error.message ?: "No se pudieron cargar los reportes."
                            } finally {
                                loading = false
                            }
                        }.start()
                    },
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Conectar y cargar reportes") }
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    NewReportButton("Nuevo Reporte", "elevador", Modifier.weight(1f)) { type ->
                        loading = true
                        Thread {
                            try { editor = ReportApi.createReport(type) } catch (error: Exception) { message = error.message ?: "No se pudo crear el reporte." } finally { loading = false }
                        }.start()
                    }
                    NewReportButton("Nuevo Reporte ALIMAK", "alimak", Modifier.weight(1f)) { type ->
                        loading = true
                        Thread {
                            try { editor = ReportApi.createReport(type) } catch (error: Exception) { message = error.message ?: "No se pudo crear el reporte." } finally { loading = false }
                        }.start()
                    }
                }
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                    Text(message, style = MaterialTheme.typography.bodySmall, modifier = Modifier.weight(1f))
                    Box {
                        OutlinedButton(onClick = { filterExpanded = true }) { Text("Filtro: $selectedFilter", style = MaterialTheme.typography.bodySmall) }
                        DropdownMenu(expanded = filterExpanded, onDismissRequest = { filterExpanded = false }) {
                            listOf("Todos", "Elevador", "ALIMAK").forEach { option ->
                                DropdownMenuItem(text = { Text(option) }, onClick = { selectedFilter = option; filterExpanded = false })
                            }
                        }
                    }
                }
                Box(modifier = Modifier.weight(1f)) {
                    if (filteredReports.isEmpty()) {
                        Text(
                            text = "No hay reportes para el filtro $selectedFilter.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = Color(0xFF666666),
                            modifier = Modifier.align(Alignment.TopCenter).padding(top = 24.dp)
                        )
                    } else {
                        LazyVerticalGrid(
                            columns = GridCells.Fixed(2),
                            modifier = Modifier.fillMaxSize(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(filteredReports, key = { it.id }) { report ->
                                ReportCard(
                                    report = report,
                                    onEdit = {
                                        loading = true
                                        Thread {
                                            try { editor = ReportApi.getReport(report.id) } catch (error: Exception) { message = error.message ?: "No se pudo abrir el reporte." } finally { loading = false }
                                        }.start()
                                    },
                                    onView = {
                                        loading = true
                                        Thread {
                                            try { viewer = ReportApi.getReport(report.id) } catch (error: Exception) { message = error.message ?: "No se pudo abrir el reporte." } finally { loading = false }
                                        }.start()
                                    },
                                    onDelete = { deleteCandidate = report }
                                )
                            }
                        }
                    }
                }
            }
        }
        deleteCandidate?.let { report ->
            AlertDialog(
                onDismissRequest = { deleteCandidate = null },
                title = { Text("Eliminar reporte #${report.id}") },
                text = { Text("Se eliminara permanentemente \"${report.title.ifBlank { "Sin titulo" }}\". Esta accion no se puede deshacer.") },
                dismissButton = { TextButton(onClick = { deleteCandidate = null }) { Text("Cancelar") } },
                confirmButton = {
                    TextButton(
                        onClick = {
                            deleteCandidate = null
                            loading = true
                            message = "Eliminando reporte..."
                            Thread {
                                try {
                                    ReportApi.deleteReport(report.id)
                                    reports = ReportApi.loadReports()
                                    message = "Reporte eliminado. ${reports.size} reportes cargados."
                                } catch (error: Exception) {
                                    message = error.message ?: "No se pudo eliminar el reporte."
                                } finally {
                                    loading = false
                                }
                            }.start()
                        }
                    ) { Text("Eliminar", color = MaterialTheme.colorScheme.error) }
                }
            )
        }
    }
}

@Composable
private fun NewReportButton(label: String, type: String, modifier: Modifier, onCreate: (String) -> Unit) {
    OutlinedButton(onClick = { onCreate(type) }, modifier = modifier) {
        Text(label, style = MaterialTheme.typography.labelSmall, maxLines = 2)
    }
}

@Composable
private fun ReportCard(report: ReportSummary, onEdit: () -> Unit, onView: () -> Unit, onDelete: () -> Unit) {
    val approved = report.status == "close"
    val creationDate = report.createdAt.trim().take(10).ifBlank { "Sin fecha" }
    Card(modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp), colors = CardDefaults.cardColors(containerColor = Color(0xFFF0F0F0))) {
        Column {
            Box(
                modifier = Modifier.fillMaxWidth().height(92.dp).background(Color(0xFFE2E2E2)),
                contentAlignment = Alignment.Center
            ) {
                if (report.coverPhoto != null) {
                    AuthenticatedPhoto(
                        reportId = report.id,
                        name = report.coverPhoto,
                        contentDescription = "Portada del reporte #${report.id}",
                        modifier = Modifier.fillMaxSize()
                    )
                } else {
                    Icon(Icons.Filled.Image, contentDescription = "Imagen pendiente del reporte", tint = Color(0xFF9A9A9A), modifier = Modifier.size(36.dp))
                }
                Surface(
                    modifier = Modifier.align(Alignment.TopEnd).padding(8.dp),
                    shape = RoundedCornerShape(10.dp),
                    color = if (approved) Color(0xFF5E7D3A) else Color(0xFF8A0E20),
                    contentColor = Color.White
                ) { Text(if (approved) "APROBADO" else "PENDIENTE", style = MaterialTheme.typography.labelSmall, modifier = Modifier.padding(horizontal = 7.dp, vertical = 4.dp)) }
            }
            Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        "#${report.id} - ${report.type.uppercase()}",
                        color = MaterialTheme.colorScheme.primary,
                        style = MaterialTheme.typography.labelLarge,
                        maxLines = 1,
                        modifier = Modifier.weight(1f)
                    )
                    Text(
                        "Creado: $creationDate",
                        color = Color(0xFF666666),
                        style = MaterialTheme.typography.labelSmall,
                        maxLines = 1
                    )
                }
                Text(report.title.ifBlank { "Añadir título del reporte..." }, style = MaterialTheme.typography.titleMedium, maxLines = 2)
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceEvenly) {
                    Icon(Icons.Filled.Share, contentDescription = "Compartir", tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(22.dp))
                    IconButton(onClick = onView, modifier = Modifier.size(26.dp)) { Icon(Icons.Filled.Visibility, contentDescription = "Ver", tint = MaterialTheme.colorScheme.primary) }
                    IconButton(onClick = onEdit, modifier = Modifier.size(26.dp)) { Icon(Icons.Filled.Edit, contentDescription = "Editar", tint = MaterialTheme.colorScheme.primary) }
                    if (approved) {
                        Icon(Icons.Filled.Delete, contentDescription = "Un reporte aprobado no puede ser eliminado", tint = Color.Gray, modifier = Modifier.size(22.dp))
                    } else {
                        IconButton(onClick = onDelete, modifier = Modifier.size(26.dp)) { Icon(Icons.Filled.Delete, contentDescription = "Eliminar", tint = MaterialTheme.colorScheme.error) }
                    }
                }
            }
        }
    }
}
