<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/report_signatures.php';

function signature_error(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

$id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? '';
$name = $_GET['name'] ?? '';
$expires = $_GET['expires'] ?? '';
$signature = $_GET['signature'] ?? '';

if (!ctype_digit((string) $id) || (int) $id < 1
    || !is_string($type) || !in_array($type, report_signature_types(), true)
    || !is_string($name) || !preg_match('/^[a-f0-9]{32}\.png$/', $name)
    || !ctype_digit((string) $expires) || (int) $expires < time()
    || !is_string($signature) || !preg_match('/^[a-f0-9]{64}$/', $signature)
    || !hash_equals(report_signature_hash((int) $id, $type, $name, (int) $expires), $signature)) {
    signature_error(403, 'El acceso a la firma no es válido o expiró.');
}

$db = db();
$reportId = (int) $id;
$statement = $db->prepare('SELECT data_reporte FROM reporte WHERE id = ?');
$statement->bind_param('i', $reportId);
$statement->execute();
$report = $statement->get_result()->fetch_assoc() ?: null;
$statement->close();
mysqli_close($db);
if ($report === null) {
    signature_error(404, 'Firma no disponible.');
}
$data = json_decode((string) ($report['data_reporte'] ?? ''), true) ?: [];
if (report_signature_reference($data, $type) !== $name) {
    signature_error(404, 'Firma no disponible.');
}
$path = report_signature_directory($reportId) . '/' . $name;
if (!is_file($path)) {
    signature_error(404, 'Firma no disponible.');
}
header('Content-Type: image/png');
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: inline; filename="firma-' . $type . '.png"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($path);
