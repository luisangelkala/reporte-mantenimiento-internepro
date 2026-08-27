<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/report_photos.php';

function photo_error(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $message;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    photo_error(405, 'Método no permitido.');
}

$rawId = $_GET['id'] ?? '';
$name = $_GET['name'] ?? '';
$rawExpires = $_GET['expires'] ?? '';
$signature = $_GET['signature'] ?? '';

if (!is_string($rawId) || !ctype_digit($rawId) || (int) $rawId < 1
    || !is_string($name) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name)
    || !is_string($rawExpires) || !ctype_digit($rawExpires)
    || !is_string($signature) || !preg_match('/^[a-f0-9]{64}$/', $signature)) {
    photo_error(404, 'Fotografía no disponible.');
}

$reportId = (int) $rawId;
$expires = (int) $rawExpires;
$now = time();
if ($expires < $now || $expires > $now + 43200
    || !hash_equals(report_photo_signature($reportId, $name, $expires), $signature)) {
    photo_error(403, 'El acceso a la fotografía no es válido o expiró.');
}

$connection = db();
if (!$connection) {
    photo_error(500, 'No se pudo consultar la fotografía.');
}

$statement = $connection->prepare('SELECT data_reporte FROM reporte WHERE id = ?');
$statement->bind_param('i', $reportId);
$statement->execute();
$report = $statement->get_result()->fetch_assoc() ?: null;
$statement->close();
mysqli_close($connection);

if ($report === null) {
    photo_error(404, 'Fotografía no disponible.');
}

$belongsToReport = false;
foreach (report_photo_entries($report['data_reporte']) as $photo) {
    if ($photo['name'] === $name) {
        $belongsToReport = true;
        break;
    }
}
if (!$belongsToReport) {
    photo_error(404, 'Fotografía no disponible.');
}

$path = __DIR__ . '/storage/report-photos/' . $reportId . '/' . $name;
if (!is_file($path)) {
    photo_error(404, 'Fotografía no disponible.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    photo_error(404, 'Fotografía no disponible.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: inline; filename="report-photo.' . pathinfo($name, PATHINFO_EXTENSION) . '"');
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
readfile($path);

