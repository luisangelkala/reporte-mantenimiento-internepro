package com.internepro.reportes

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.net.Uri
import androidx.core.content.FileProvider
import java.io.File
import java.io.FileOutputStream

object PhotoProcessor {
    private const val maxBytes = 950 * 1024L

    fun createCameraUri(context: Context): Uri {
        val directory = File(context.cacheDir, "camera").apply { mkdirs() }
        val file = File.createTempFile("capture_", ".jpg", directory)
        return FileProvider.getUriForFile(context, "${context.packageName}.files", file)
    }

    fun compress(context: Context, source: Uri): Uri {
        val original = context.contentResolver.openInputStream(source)?.use { BitmapFactory.decodeStream(it) }
            ?: throw IllegalStateException("No se pudo leer la fotografia.")
        var bitmap = scale(original, 1600)
        val directory = File(context.cacheDir, "uploads").apply { mkdirs() }
        val output = File.createTempFile("upload_", ".jpg", directory)
        var quality = 85
        do {
            FileOutputStream(output).use { bitmap.compress(Bitmap.CompressFormat.JPEG, quality, it) }
            if (output.length() <= maxBytes) break
            if (quality > 45) quality -= 10 else {
                bitmap = scale(bitmap, (bitmap.width * 0.8f).toInt())
                quality = 85
            }
        } while (bitmap.width > 320)
        if (output.length() > maxBytes) throw IllegalStateException("No fue posible optimizar la fotografia a menos de 1 MB.")
        return FileProvider.getUriForFile(context, "${context.packageName}.files", output)
    }

    private fun scale(bitmap: Bitmap, maximum: Int): Bitmap {
        val largest = maxOf(bitmap.width, bitmap.height)
        if (largest <= maximum) return bitmap
        val ratio = maximum.toFloat() / largest
        return Bitmap.createScaledBitmap(bitmap, (bitmap.width * ratio).toInt(), (bitmap.height * ratio).toInt(), true)
    }
}
