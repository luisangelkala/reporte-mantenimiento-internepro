import java.util.Properties

plugins { id("com.android.application"); id("org.jetbrains.kotlin.android"); id("org.jetbrains.kotlin.plugin.compose") }

val localProperties = Properties().apply {
    val file = rootProject.file("local.properties")
    if (file.exists()) file.inputStream().use { load(it) }
}
val apiUrl = localProperties.getProperty("INTERNEPRO_API_URL", "")
val apiToken = localProperties.getProperty("INTERNEPRO_API_TOKEN", "")

android { namespace = "com.internepro.reportes"; compileSdk = 37
    defaultConfig { applicationId = "com.internepro.reportes"; minSdk = 26; targetSdk = 37; versionCode = 1; versionName = "0.1.0-demo" }
    buildFeatures { compose = true; buildConfig = true }
    defaultConfig {
        buildConfigField("String", "API_BASE_URL", "\"$apiUrl\"")
        buildConfigField("String", "API_TOKEN", "\"$apiToken\"")
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

kotlin { jvmToolchain(17) }
dependencies {
    implementation(platform("androidx.compose:compose-bom:2024.12.01"))
    implementation("androidx.activity:activity-compose:1.10.0")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.ui:ui-tooling-preview")
    debugImplementation("androidx.compose.ui:ui-tooling")
}
