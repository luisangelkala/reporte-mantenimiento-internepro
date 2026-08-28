<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/report_pdf.php';

function pdf_fail(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo $message;
    exit;
}

$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$name = isset($_GET['name']) && is_string($_GET['name']) ? $_GET['name'] : '';
$expires = isset($_GET['expires']) && ctype_digit((string) $_GET['expires']) ? (int) $_GET['expires'] : 0;
$signature = isset($_GET['signature']) && is_string($_GET['signature']) ? $_GET['signature'] : '';

if ($id < 1 || !preg_match('/^report-[0-9]+-v[0-9]+-[a-f0-9]{24}\.pdf$/', $name) ||
    $expires < time() || $signature === '' || !hash_equals(report_pdf_signature($id, $name, $expires), $signature)) {
    pdf_fail(403, 'El enlace del PDF no es valido o ha expirado.');
}

$db = db();
if (!$db) {
    pdf_fail(500, 'No se pudo consultar el reporte.');
}
$statement = $db->prepare('SELECT state_reporte FROM reporte WHERE id = ?');
if ($statement === false) {
    mysqli_close($db);
    pdf_fail(500, 'No se pudo consultar el reporte.');
}
$statement->bind_param('i', $id);
$statement->execute();
$report = $statement->get_result()->fetch_assoc() ?: null;
$statement->close();
mysqli_close($db);

$state = $report === null ? [] : (json_decode((string) $report['state_reporte'], true) ?: []);
$pdf = isset($state['pdf']) && is_array($state['pdf']) ? $state['pdf'] : [];
if (($state['status'] ?? '') !== 'close' || ($pdf['status'] ?? '') !== 'active' ||
    !hash_equals((string) ($pdf['name'] ?? ''), $name)) {
    pdf_fail(404, 'El PDF solicitado ya no esta vigente.');
}

$path = __DIR__ . '/storage/report-pdfs/' . $id . '/' . $name;
if (!is_file($path) || !is_readable($path)) {
    pdf_fail(404, 'El PDF solicitado no esta disponible.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="reporte-' . $id . '.pdf"');
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
