<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

function api_response(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_authenticate(): void
{
    $authConfig = require dirname(__DIR__, 2) . '/config/auth.php';
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $prefix = 'Bearer ';

    if (strpos($header, $prefix) !== 0) {
        api_response(401, ['error' => 'Autenticación requerida.']);
    }

    $token = substr($header, strlen($prefix));
    if (!hash_equals($authConfig['secret'], $token)) {
        api_response(401, ['error' => 'Credencial inválida.']);
    }
}

function api_payload(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        api_response(400, ['error' => 'JSON inválido.']);
    }

    return $payload;
}

function api_id(string $value): int
{
    if (!ctype_digit($value) || (int) $value < 1) {
        api_response(400, ['error' => 'Identificador inválido.']);
    }

    return (int) $value;
}

function api_report(mysqli $connection, int $id): ?array
{
    $statement = $connection->prepare('SELECT * FROM reporte WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $report = $statement->get_result()->fetch_assoc() ?: null;
    $statement->close();
    return $report;
}

function api_decode_report(array $report): array
{
    $report['state_reporte'] = json_decode($report['state_reporte'], true) ?: [];
    $report['data_reporte'] = $report['data_reporte'] === null ? null : json_decode($report['data_reporte'], true);
    $report['obs_reporte'] = $report['obs_reporte'] === null ? null : json_decode($report['obs_reporte'], true);
    return $report;
}

function api_string(array $payload, string $key, int $maxLength = 65535): string
{
    $value = $payload[$key] ?? '';
    if (!is_string($value)) {
        api_response(400, ['error' => "Campo inválido: {$key}"]);
    }

    return substr(trim($value), 0, $maxLength);
}

function api_json_field(array $payload, string $key): ?string
{
    if (!array_key_exists($key, $payload)) {
        return null;
    }
    if (!is_array($payload[$key])) {
        api_response(400, ['error' => "Campo inválido: {$key}"]);
    }

    return json_encode($payload[$key], JSON_UNESCAPED_UNICODE);
}

function api_photo_directory(int $reportId): string
{
    return dirname(__DIR__, 2) . '/storage/report-photos/' . $reportId;
}

function api_photo_response(string $path): void
{
    if (!is_file($path)) {
        api_response(404, ['error' => 'Fotografia no encontrada.']);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        api_response(404, ['error' => 'Fotografia no disponible.']);
    }

    header_remove('Content-Type');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}

function api_store_photo(mysqli $connection, array $report, int $id): array
{
    $upload = $_FILES['photo'] ?? null;
    if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        api_response(400, ['error' => 'Se requiere una fotografia valida.']);
    }
    if (($upload['size'] ?? 0) < 1 || $upload['size'] > 5 * 1024 * 1024) {
        api_response(400, ['error' => 'La fotografia debe pesar entre 1 byte y 5 MB.']);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($upload['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        api_response(400, ['error' => 'Formato no permitido. Use JPEG, PNG o WEBP.']);
    }

    $directory = api_photo_directory($id);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        api_response(500, ['error' => 'No se pudo preparar el almacenamiento de fotografias.']);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = $directory . '/' . $filename;
    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
        api_response(500, ['error' => 'No se pudo guardar la fotografia.']);
    }
    chmod($destination, 0640);

    $data = json_decode($report['data_reporte'] ?? '', true);
    $data = is_array($data) ? $data : [];
    $photos = $data['_photos'] ?? [];
    $photos[] = ['name' => $filename, 'uploaded_at' => date('c')];
    $data['_photos'] = $photos;
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
    $statement = $connection->prepare('UPDATE reporte SET data_reporte = ?, updated_at = NOW() WHERE id = ?');
    $statement->bind_param('si', $encoded, $id);
    $statement->execute();
    $statement->close();

    return ['name' => $filename, 'url' => 'reports/' . $id . '/photos/' . rawurlencode($filename)];
}

api_authenticate();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = trim($_SERVER['PATH_INFO'] ?? '', '/');
$segments = $path === '' ? [] : explode('/', $path);

if (($segments[0] ?? '') !== 'reports') {
    api_response(404, ['error' => 'Recurso no encontrado.']);
}

$connection = db();
if (!$connection) {
    api_response(500, ['error' => 'No se pudo conectar a la base de datos.']);
}

if ($method === 'GET' && count($segments) === 1) {
    $result = $connection->query('SELECT * FROM reporte ORDER BY created_at DESC');
    $reports = [];
    while ($report = $result->fetch_assoc()) {
        $reports[] = api_decode_report($report);
    }
    mysqli_close($connection);
    api_response(200, ['data' => $reports]);
}

if ($method === 'POST' && count($segments) === 1) {
    $payload = api_payload();
    $type = api_string($payload, 'type', 20);
    if (!in_array($type, ['elevador', 'alimak'], true)) {
        api_response(400, ['error' => 'Tipo de reporte inválido.']);
    }
    $state = json_encode(['status' => 'open', 'aprobado' => '', 'fecha' => '', 'reporte' => $type]);
    $title = api_string($payload, 'title', 255) ?: 'Añadir título del reporte...';
    $statement = $connection->prepare('INSERT INTO reporte (title_reporte, state_reporte, created_at) VALUES (?, ?, NOW())');
    $statement->bind_param('ss', $title, $state);
    $statement->execute();
    $id = $connection->insert_id;
    $statement->close();
    $report = api_report($connection, $id);
    mysqli_close($connection);
    api_response(201, ['data' => api_decode_report($report)]);
}

$id = api_id($segments[1] ?? '');

if ($method === 'GET' && count($segments) === 4 && ($segments[2] ?? '') === 'photos') {
    $name = $segments[3] ?? '';
    if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name)) {
        mysqli_close($connection);
        api_response(404, ['error' => 'Fotografia no encontrada.']);
    }
    $report = api_report($connection, $id);
    mysqli_close($connection);
    if ($report === null) {
        api_response(404, ['error' => 'Reporte no encontrado.']);
    }
    api_photo_response(api_photo_directory($id) . '/' . $name);
}

if ($method === 'POST' && count($segments) === 3 && ($segments[2] ?? '') === 'photos') {
    $report = api_report($connection, $id);
    if ($report === null) {
        mysqli_close($connection);
        api_response(404, ['error' => 'Reporte no encontrado.']);
    }
    $photo = api_store_photo($connection, $report, $id);
    $updated = api_report($connection, $id);
    mysqli_close($connection);
    api_response(201, ['data' => ['photo' => $photo, 'report' => api_decode_report($updated)]]);
}

if ($method === 'GET' && count($segments) === 2) {
    $report = api_report($connection, $id);
    mysqli_close($connection);
    if ($report === null) {
        api_response(404, ['error' => 'Reporte no encontrado.']);
    }
    api_response(200, ['data' => api_decode_report($report)]);
}

if ($method === 'PUT' && count($segments) === 2) {
    $payload = api_payload();
    $report = api_report($connection, $id);
    if ($report === null) {
        mysqli_close($connection);
        api_response(404, ['error' => 'Reporte no encontrado.']);
    }
    $title = api_string($payload, 'title', 255);
    $client = api_string($payload, 'client', 255);
    $date = api_string($payload, 'date', 20);
    $equipment = api_string($payload, 'equipment', 255);
    $technician = api_string($payload, 'technician', 255);
    $data = api_json_field($payload, 'data') ?? $report['data_reporte'];
    $observations = api_json_field($payload, 'observations') ?? $report['obs_reporte'];
    $statement = $connection->prepare('UPDATE reporte SET title_reporte = ?, cliente_reporte = ?, fecha_reporte = ?, equipo_reporte = ?, tecnico_reporte = ?, data_reporte = ?, obs_reporte = ?, updated_at = NOW() WHERE id = ?');
    $statement->bind_param('sssssssi', $title, $client, $date, $equipment, $technician, $data, $observations, $id);
    $statement->execute();
    $statement->close();
    $updated = api_report($connection, $id);
    mysqli_close($connection);
    api_response(200, ['data' => api_decode_report($updated)]);
}

if ($method === 'POST' && ($segments[2] ?? '') === 'approve') {
    $payload = api_payload();
    $report = api_report($connection, $id);
    if ($report === null) {
        mysqli_close($connection);
        api_response(404, ['error' => 'Reporte no encontrado.']);
    }
    $state = json_decode($report['state_reporte'], true) ?: [];
    $state['status'] = 'close';
    $state['aprobado'] = api_string($payload, 'approved_by', 255);
    $state['fecha'] = date('Y-m-d');
    $state['reporte'] = $state['reporte'] ?? 'elevador';
    $state = json_encode($state, JSON_UNESCAPED_UNICODE);
    $statement = $connection->prepare('UPDATE reporte SET state_reporte = ?, updated_at = NOW() WHERE id = ?');
    $statement->bind_param('si', $state, $id);
    $statement->execute();
    $statement->close();
    $updated = api_report($connection, $id);
    mysqli_close($connection);
    api_response(200, ['data' => api_decode_report($updated)]);
}

if ($method === 'DELETE' && count($segments) === 2) {
    $report = api_report($connection, $id);
    if ($report === null) {
        mysqli_close($connection);
        api_response(404, ['error' => 'Reporte no encontrado.']);
    }
    $state = json_decode($report['state_reporte'], true) ?: [];
    if (($state['status'] ?? '') === 'close') {
        mysqli_close($connection);
        api_response(409, ['error' => 'Un reporte aprobado no puede ser eliminado.']);
    }
    $statement = $connection->prepare('DELETE FROM reporte WHERE id = ?');
    $statement->bind_param('i', $id);
    $statement->execute();
    $deleted = $statement->affected_rows === 1;
    $statement->close();
    mysqli_close($connection);
    if (!$deleted) {
        api_response(404, ['error' => 'Reporte no encontrado.']);
    }
    api_response(200, ['message' => 'Reporte eliminado.']);
}

mysqli_close($connection);
api_response(405, ['error' => 'Método no permitido.']);
