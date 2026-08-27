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
import androidx.compose.material.icons.filled.Image as ImageIcon
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.unit.dp
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

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
    val status: String
)

private fun loadReports(baseUrl: String, token: String): List<ReportSummary> {
    val connection = (URL(baseUrl.trimEnd('/') + "/reports").openConnection() as HttpURLConnection).apply {
        requestMethod = "GET"
        setRequestProperty("Authorization", "Bearer $token")
        connectTimeout = 15000
        readTimeout = 15000
    }
    if (connection.responseCode != 200) {
        throw IllegalStateException("API respondio HTTP ${connection.responseCode}")
    }
    val json = JSONObject(connection.inputStream.bufferedReader().use { it.readText() }).getJSONArray("data")
    return List(json.length()) { index ->
        val item = json.getJSONObject(index)
        val state = item.getJSONObject("state_reporte")
        ReportSummary(
            id = item.getInt("id"),
            title = item.optString("title_reporte"),
            type = state.optString("reporte", "elevador"),
            status = state.optString("status")
        )
    }
}

@Composable
fun App() {
    var reports by remember { mutableStateOf(emptyList<ReportSummary>()) }
    var message by remember { mutableStateOf("Listo para cargar reportes.") }
    var loading by remember { mutableStateOf(false) }

    MaterialTheme(
        colorScheme = lightColorScheme(
            primary = Color(0xFFC8102E),
            secondary = Color(0xFF8A0E20),
            surface = Color(0xFFF8F8F8)
        )
    ) {
        Scaffold { padding ->
            Column(
                modifier = Modifier.padding(padding).padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Image(
                    painter = painterResource(R.drawable.logo_internepro),
                    contentDescription = "Internepro",
                    modifier = Modifier.height(55.dp).width(180.dp).align(Alignment.CenterHorizontally)
                )
                Text("Registro de mantenimiento", style = MaterialTheme.typography.headlineSmall)

                Button(
                    enabled = !loading,
                    onClick = {
                        loading = true
                        message = "Cargando..."
                        Thread {
                            try {
                                reports = loadReports(BuildConfig.API_BASE_URL, BuildConfig.API_TOKEN)
                                message = "${reports.size} reportes cargados."
                            } catch (e: Exception) {
                                message = e.message ?: "Error"
                            } finally {
                                loading = false
                            }
                        }.start()
                    },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Conectar y cargar reportes")
                }

                Row(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    OutlinedButton(onClick = { }, modifier = Modifier.weight(1f)) {
                        Text("Nuevo Reporte", style = MaterialTheme.typography.labelSmall, maxLines = 2)
                    }
                    OutlinedButton(onClick = { }, modifier = Modifier.weight(1f)) {
                        Text("Nuevo Reporte ALIMAK", style = MaterialTheme.typography.labelSmall, maxLines = 2)
                    }
                }

                Row(
                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    FilterChip(selected = true, onClick = { }, label = { Text("Todos") })
                    FilterChip(selected = false, onClick = { }, label = { Text("Elevador") })
                    FilterChip(selected = false, onClick = { }, label = { Text("ALIMAK") })
                }
                Text(message, style = MaterialTheme.typography.bodySmall)

                Box(modifier = Modifier.weight(1f)) {
                    LazyVerticalGrid(
                        columns = GridCells.Fixed(2),
                        modifier = Modifier.fillMaxSize(),
                        horizontalArrangement = Arrangement.spacedBy(10.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        items(reports) { report ->
                            ReportCard(report)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun ReportCard(report: ReportSummary) {
    val approved = report.status == "close"
    val statusColor = if (approved) Color(0xFF5E7D3A) else Color(0xFF8A0E20)

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFFF0F0F0))
    ) {
        Column {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(92.dp)
                    .background(Color(0xFFE2E2E2)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = ImageIcon,
                    contentDescription = "Imagen pendiente del reporte",
                    tint = Color(0xFF9A9A9A),
                    modifier = Modifier.size(36.dp)
                )
                Surface(
                    modifier = Modifier.align(Alignment.TopEnd).padding(8.dp),
                    shape = RoundedCornerShape(10.dp),
                    color = statusColor,
                    contentColor = Color.White
                ) {
                    Text(
                        text = if (approved) "APROBADO" else "PENDIENTE",
                        style = MaterialTheme.typography.labelSmall,
                        modifier = Modifier.padding(horizontal = 7.dp, vertical = 4.dp)
                    )
                }
            }
            Column(
                modifier = Modifier.padding(12.dp),
                verticalArrangement = Arrangement.spacedBy(5.dp)
            ) {
                Text(
                    "#${report.id} - ${report.type.uppercase()}",
                    color = MaterialTheme.colorScheme.primary,
                    style = MaterialTheme.typography.labelLarge
                )
                Text(
                    report.title.ifBlank { "Añadir título del reporte..." },
                    style = MaterialTheme.typography.titleMedium,
                    maxLines = 2
                )
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceEvenly,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    ReportActionIcon(Icons.Filled.Share, "Compartir")
                    ReportActionIcon(Icons.Filled.Visibility, "Ver")
                    ReportActionIcon(Icons.Filled.Edit, "Editar")
                    ReportActionIcon(Icons.Filled.Delete, "Eliminar")
                }
            }
        }
    }
}

@Composable
private fun ReportActionIcon(icon: androidx.compose.ui.graphics.vector.ImageVector, description: String) {
    Icon(
        imageVector = icon,
        contentDescription = description,
        tint = MaterialTheme.colorScheme.primary,
        modifier = Modifier.size(22.dp)
    )
}
