package com.internepro.reportes

import android.content.ContentResolver
import android.net.Uri
import org.json.JSONArray
import org.json.JSONObject
import java.io.DataOutputStream
import java.net.HttpURLConnection
import java.net.URL
import java.util.UUID

data class ReportPhoto(
    val name: String,
    val comment: String = "",
    val uploadedAt: String = "",
    val scope: String = "general"
) {
    fun toJson(): JSONObject = JSONObject()
        .put("name", name)
        .put("comment", comment)
        .put("scope", scope)
        .also { json -> if (uploadedAt.isNotBlank()) json.put("uploaded_at", uploadedAt) }
}

data class ReportDetail(
    val id: Int,
    val type: String,
    var title: String,
    var client: String,
    var date: String,
    var equipment: String,
    var technician: String,
    var status: String,
    var approvedBy: String,
    var approvedDate: String,
    val checklist: JSONObject,
    val observations: JSONObject
) {
    fun photos(): List<ReportPhoto> = checklist.optJSONArray("_photos").reportPhotos()
    fun photoNames(): List<String> = photos().map { it.name }
}

private fun JSONArray?.reportPhotos(): List<ReportPhoto> {
    if (this == null) return emptyList()
    return List(length()) { index ->
        val item = optJSONObject(index) ?: return@List null
        val name = item.optString("name")
        val scope = item.optString("scope", "general")
        if (!name.matches(Regex("^[a-f0-9]{32}\\.(jpg|png|webp)$")) || scope != "general") {
            return@List null
        }
        ReportPhoto(
            name = name,
            comment = item.optString("comment"),
            uploadedAt = item.optString("uploaded_at"),
            scope = scope
        )
    }.filterNotNull()
}

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
                status = state.optString("status"),
                createdAt = item.optString("created_at"),
                coverPhoto = item.optJSONObject("data_reporte")?.optJSONArray("_photos").reportPhotos().firstOrNull()?.name
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

    fun deleteReport(id: Int) {
        jsonRequest("reports/$id", "DELETE")
    }

    fun approveReport(id: Int, approvedBy: String): ReportDetail =
        parseReport(jsonRequest("reports/$id/approve", "POST", JSONObject().put("approved_by", approvedBy)).getJSONObject("data"))

    @Synchronized
    fun uploadPhoto(resolver: ContentResolver, reportId: Int, uri: Uri, comment: String): String {
        val boundary = "----Internepro${UUID.randomUUID()}"
        val connection = connection("reports/$reportId/photos", "POST").apply {
            doOutput = true
            setRequestProperty("Content-Type", "multipart/form-data; boundary=$boundary")
        }
        val input = resolver.openInputStream(uri) ?: throw IllegalStateException("No se pudo leer la fotografia seleccionada.")
        DataOutputStream(connection.outputStream).use { output ->
            output.writeBytes("--$boundary\r\n")
            output.writeBytes("Content-Disposition: form-data; name=\"comment\"\r\n")
            output.writeBytes("Content-Type: text/plain; charset=UTF-8\r\n\r\n")
            output.write(comment.toByteArray(Charsets.UTF_8))
            output.writeBytes("\r\n--$boundary\r\n")
            output.writeBytes("Content-Disposition: form-data; name=\"photo\"; filename=\"foto.jpg\"\r\n")
            output.writeBytes("Content-Type: image/jpeg\r\n\r\n")
            input.use { it.copyTo(output) }
            output.writeBytes("\r\n--$boundary--\r\n")
        }
        val text = responseText(connection)
        if (connection.responseCode !in 200..299) {
            throw IllegalStateException(JSONObject(text.ifBlank { "{}" }).optString("error", "No se pudo cargar la fotografia."))
        }
        return JSONObject(text).getJSONObject("data").getJSONObject("photo").getString("url")
    }

    fun verifyPhoto(path: String) {
        val connection = connection(path, "GET")
        val stream = if (connection.responseCode in 200..299) connection.inputStream else connection.errorStream
        stream?.close()
        if (connection.responseCode !in 200..299) {
            throw IllegalStateException("El servidor no confirmo la fotografia cargada.")
        }
    }

    fun photoBytes(reportId: Int, name: String): ByteArray {
        val connection = connection("reports/$reportId/photos/$name", "GET")
        val stream = if (connection.responseCode in 200..299) connection.inputStream else connection.errorStream
        val bytes = stream?.use { it.readBytes() } ?: byteArrayOf()
        if (connection.responseCode !in 200..299 || bytes.isEmpty()) {
            throw IllegalStateException("No se pudo recuperar la fotografia.")
        }
        return bytes
    }

    fun deletePhoto(reportId: Int, name: String) {
        jsonRequest("reports/$reportId/photos/$name", "DELETE")
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
            status = state.optString("status"),
            approvedBy = state.optString("aprobado"),
            approvedDate = state.optString("fecha"),
            checklist = item.optJSONObject("data_reporte") ?: JSONObject(),
            observations = item.optJSONObject("obs_reporte") ?: JSONObject()
        )
    }
}
