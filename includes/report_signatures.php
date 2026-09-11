<?php

function report_signature_types(): array
{
    return ['empresa', 'cliente'];
}

function report_signature_reference(array $data, string $type): ?string
{
    if (!in_array($type, report_signature_types(), true)) {
        return null;
    }
    $name = $data['firma_' . $type] ?? null;
    return is_string($name) && preg_match('/^[a-f0-9]{32}\.png$/', $name) ? $name : null;
}

function report_signature_directory(int $reportId): string
{
    return dirname(__DIR__) . '/storage/report-signatures/' . $reportId;
}

function report_signature_secret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }
    $config = require dirname(__DIR__) . '/config/auth.php';
    $configured = is_array($config) ? ($config['secret'] ?? '') : '';
    if (!is_string($configured) || $configured === '') {
        throw new RuntimeException('La configuración privada de firmas no está disponible.');
    }
    $secret = hash('sha256', 'web-report-signature|' . $configured, true);
    return $secret;
}

function report_signature_hash(int $reportId, string $type, string $name, int $expires): string
{
    return hash_hmac('sha256', $reportId . "\n" . $type . "\n" . $name . "\n" . $expires, report_signature_secret());
}

function report_signature_url(int $reportId, string $type, string $name, ?int $expires = null): string
{
    $expires = $expires ?? (time() + 28800);
    return 'signature.php?' . http_build_query([
        'id' => $reportId,
        'type' => $type,
        'name' => $name,
        'expires' => $expires,
        'signature' => report_signature_hash($reportId, $type, $name, $expires),
    ], '', '&', PHP_QUERY_RFC3986);
}

function report_signature_store_data_url(int $reportId, string $dataUrl): string
{
    if (!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $matches)) {
        throw new InvalidArgumentException('La firma enviada no es una imagen PNG válida.');
    }
    $binary = base64_decode($matches[1], true);
    if ($binary === false || strlen($binary) < 100 || strlen($binary) > 2097152) {
        throw new InvalidArgumentException('La firma está vacía o supera el límite de 2 MB.');
    }
    $image = @getimagesizefromstring($binary);
    if ($image === false || ($image['mime'] ?? '') !== 'image/png' || $image[0] < 10 || $image[1] < 10 || $image[0] > 2400 || $image[1] > 1200) {
        throw new InvalidArgumentException('Las dimensiones de la firma no son válidas.');
    }
    $directory = report_signature_directory($reportId);
    if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento privado de firmas.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('El almacenamiento privado de firmas no tiene permisos de escritura.');
    }
    $name = bin2hex(random_bytes(16)) . '.png';
    $temporary = tempnam($directory, '.signature-');
    if ($temporary === false) {
        throw new RuntimeException('No se pudo crear el archivo temporal de firma.');
    }
    $written = @file_put_contents($temporary, $binary, LOCK_EX);
    if ($written !== strlen($binary) || !@rename($temporary, $directory . '/' . $name)) {
        @unlink($temporary);
        throw new RuntimeException('No se pudo guardar la firma completa.');
    }
    @chmod($directory . '/' . $name, 0660);
    return $name;
}

function report_signature_delete(int $reportId, ?string $name): void
{
    if ($name === null || !preg_match('/^[a-f0-9]{32}\.png$/', $name)) {
        return;
    }
    $path = report_signature_directory($reportId) . '/' . $name;
    if (is_file($path)) {
        @unlink($path);
    }
}
