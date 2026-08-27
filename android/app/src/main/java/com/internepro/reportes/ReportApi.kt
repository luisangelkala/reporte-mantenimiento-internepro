package com.internepro.reportes

import android.content.ContentResolver
import android.net.Uri
import org.json.JSONObject
import java.io.DataOutputStream
import java.net.HttpURLConnection
import java.net.URL
import java.util.UUID

data class ReportDetail(
    val id: Int,
    val type: String,
    var title: String,
    var client: String,
    var date: String,
    var equipment: String,
    var technician: String,
    val checklist: JSONObject,
    val observations: JSONObject
)

object ReportApi {
    private fun endpoint(path: String): String = BuildConfig.API_BASE_URL.trimEnd('/') + "/" + path

    private fun connection(path: String, method: String): HttpURLConnection =
        (URL(endpoint(path)).openConnection() as HttpURLConnection).apply {
            requestMethod = method
            setRequestProperty("Authorization", "Bearer ${BuildConfig.API_TOKEN}")
            setRequestProperty("Accept", "application/json")
            connectTimeout = 15000
            readTimeout = 30000
        }

    private fun responseText(connection: HttpURLConnection): String {
        val stream = if (connection.responseCode in 200..299) connection.inputStream else connection.errorStream
        return stream?.bufferedReader()?.use { it.readText() } ?: ""
    }

    private fun jsonRequest(path: String, method: String, payload: JSONObject? = null): JSONObject {
        val connection = connection(path, method)
        if (payload != null) {
            connection.doOutput = true
            connection.setRequestProperty("Content-Type", "application/json; charset=utf-8")
            connection.outputStream.bufferedWriter().use { it.write(payload.toString()) }
        }
        val text = responseText(connection)
        if (connection.responseCode !in 200..299) {
            throw IllegalStateException(JSONObject(text.ifBlank { "{}" }).optString("error", "Error HTTP ${connection.responseCode}"))
        }
        return JSONObject(text)
    }

    fun loadReports(): List<ReportSummary> {
        val json = jsonRequest("reports", "GET").getJSONArray("data")
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

    fun createReport(type: String): ReportDetail {
        val response = jsonRequest("reports", "POST", JSONObject().put("type", type))
        return parseReport(response.getJSONObject("data"))
    }

    fun getReport(id: Int): ReportDetail = parseReport(jsonRequest("reports/$id", "GET").getJSONObject("data"))

    fun saveReport(report: ReportDetail): ReportDetail {
        val payload = JSONObject()
            .put("title", report.title)
            .put("client", report.client)
            .put("date", report.date)
            .put("equipment", report.equipment)
            .put("technician", report.technician)
            .put("data", report.checklist)
            .put("observations", report.observations)
        return parseReport(jsonRequest("reports/${report.id}", "PUT", payload).getJSONObject("data"))
    }

    fun uploadPhoto(resolver: ContentResolver, reportId: Int, uri: Uri) {
        val boundary = "----Internepro${UUID.randomUUID()}"
        val connection = connection("reports/$reportId/photos", "POST").apply {
            doOutput = true
            setRequestProperty("Content-Type", "multipart/form-data; boundary=$boundary")
        }
        val input = resolver.openInputStream(uri) ?: throw IllegalStateException("No se pudo leer la fotografia seleccionada.")
        DataOutputStream(connection.outputStream).use { output ->
            output.writeBytes("--$boundary\r\n")
            output.writeBytes("Content-Disposition: form-data; name=\"photo\"; filename=\"foto.jpg\"\r\n")
            output.writeBytes("Content-Type: image/jpeg\r\n\r\n")
            input.use { it.copyTo(output) }
            output.writeBytes("\r\n--$boundary--\r\n")
        }
        val text = responseText(connection)
        if (connection.responseCode !in 200..299) {
            throw IllegalStateException(JSONObject(text.ifBlank { "{}" }).optString("error", "No se pudo cargar la fotografia."))
        }
    }

    private fun parseReport(item: JSONObject): ReportDetail {
        val state = item.getJSONObject("state_reporte")
        return ReportDetail(
            id = item.getInt("id"),
            type = state.optString("reporte", "elevador"),
            title = item.optString("title_reporte"),
            client = item.optString("cliente_reporte"),
            date = item.optString("fecha_reporte"),
            equipment = item.optString("equipo_reporte"),
            technician = item.optString("tecnico_reporte"),
            checklist = item.optJSONObject("data_reporte") ?: JSONObject(),
            observations = item.optJSONObject("obs_reporte") ?: JSONObject()
        )
    }
}
