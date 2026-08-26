package com.internepro.reportes

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.foundation.layout.*
import androidx.compose.ui.unit.dp
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

class MainActivity : ComponentActivity() {
 override fun onCreate(savedInstanceState: Bundle?) { super.onCreate(savedInstanceState); setContent { App() } }
}

data class ReportSummary(val id: Int, val title: String, val type: String, val status: String)

private fun loadReports(baseUrl: String, token: String): List<ReportSummary> {
 val connection = (URL(baseUrl.trimEnd('/') + "/reports").openConnection() as HttpURLConnection).apply {
  requestMethod = "GET"; setRequestProperty("Authorization", "Bearer $token"); connectTimeout = 15000; readTimeout = 15000
 }
 if (connection.responseCode != 200) throw IllegalStateException("API respondió HTTP ${connection.responseCode}")
 val json = JSONObject(connection.inputStream.bufferedReader().use { it.readText() }).getJSONArray("data")
 return List(json.length()) { index ->
  val item = json.getJSONObject(index); val state = item.getJSONObject("state_reporte")
  ReportSummary(item.getInt("id"), item.optString("title_reporte"), state.optString("reporte", "elevador"), state.optString("status"))
 }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable fun App() {
 var reports by remember { mutableStateOf(emptyList<ReportSummary>()) }
 var message by remember { mutableStateOf("Listo para cargar reportes.") }
 var loading by remember { mutableStateOf(false) }
 MaterialTheme(colorScheme = lightColorScheme(primary = androidx.compose.ui.graphics.Color(0xFFC8102E), secondary = androidx.compose.ui.graphics.Color(0xFF8A0E20))) {
 Scaffold(
  topBar = {
   TopAppBar(
    title = {
     Column {
      Text("Internepro S.A.", color = androidx.compose.ui.graphics.Color(0xFFC8102E))
      Text("Negociando con Profesionales", style = MaterialTheme.typography.labelSmall)
     }
    }
   )
  }
 ) { padding ->
  Column(Modifier.padding(padding).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
   Text("Registro de mantenimiento", style = MaterialTheme.typography.headlineSmall)
   Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
    Button(onClick = { }, modifier = Modifier.weight(1f)) { Text("Nuevo Elevador") }
    OutlinedButton(onClick = { }, modifier = Modifier.weight(1f)) { Text("Nuevo ALIMAK") }
   }
   Button(enabled = !loading, onClick = {
    loading = true; message = "Cargando..."
    Thread { try { reports = loadReports(BuildConfig.API_BASE_URL, BuildConfig.API_TOKEN); message = "${reports.size} reportes cargados." } catch (error: Exception) { message = error.message ?: "No se pudo conectar." } finally { loading = false } }.start()
   }, modifier = Modifier.fillMaxWidth()) { Text(if (loading) "Cargando..." else "Conectar y cargar reportes") }
   Text(message)
   LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) { items(reports) { report ->
    Card(colors = CardDefaults.cardColors(containerColor = androidx.compose.ui.graphics.Color(0xFFFFFBFB))) {
     Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(5.dp)) {
      Text("#${report.id} · ${report.type.uppercase()}", color = MaterialTheme.colorScheme.primary, style = MaterialTheme.typography.labelLarge)
      Text(report.title, style = MaterialTheme.typography.titleMedium)
      AssistChip(onClick = { }, label = { Text(if (report.status == "close") "APROBADO" else "PENDIENTE") })
      Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) { TextButton(onClick = { }) { Text("Ver") }; TextButton(onClick = { }) { Text("Editar") }; TextButton(onClick = { }) { Text("Compartir") } }
     }
    }
   } }
  }
 }
 }
}
