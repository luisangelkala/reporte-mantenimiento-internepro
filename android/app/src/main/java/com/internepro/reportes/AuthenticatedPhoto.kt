package com.internepro.reportes

import android.graphics.BitmapFactory
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.BrokenImage
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.produceState
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

@Composable
fun AuthenticatedPhoto(
    reportId: Int,
    name: String,
    contentDescription: String,
    modifier: Modifier = Modifier,
    contentScale: ContentScale = ContentScale.Crop,
    maxDecodeDimension: Int? = 512
) {
    val result = produceState<Result<ImageBitmap>?>(initialValue = null, reportId, name) {
        value = withContext(Dispatchers.IO) {
            runCatching {
                val bytes = ReportApi.photoBytes(reportId, name)
                val options = BitmapFactory.Options()
                if (maxDecodeDimension != null) {
                    options.inJustDecodeBounds = true
                    BitmapFactory.decodeByteArray(bytes, 0, bytes.size, options)
                    var sample = 1
                    while (maxOf(options.outWidth, options.outHeight) / sample > maxDecodeDimension * 2) sample *= 2
                    options.inJustDecodeBounds = false
                    options.inSampleSize = sample
                }
                BitmapFactory.decodeByteArray(bytes, 0, bytes.size, options)?.asImageBitmap()
                    ?: error("Imagen invalida")
            }
        }
    }.value

    Box(modifier = modifier.background(Color(0xFFE2E2E2)), contentAlignment = Alignment.Center) {
        when {
            result == null -> CircularProgressIndicator()
            result.isSuccess -> Image(
                bitmap = result.getOrThrow(),
                contentDescription = contentDescription,
                modifier = Modifier.fillMaxSize(),
                contentScale = contentScale
            )
            else -> Icon(
                imageVector = Icons.Filled.BrokenImage,
                contentDescription = "No se pudo cargar la imagen",
                tint = MaterialTheme.colorScheme.error
            )
        }
    }
}
