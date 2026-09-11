<?php

function report_call_photo_check_request($id, $csrfToken): int
{
    if (!ctype_digit((string) $id) || (int) $id < 1) {
        throw new InvalidArgumentException('El identificador del reporte no es válido.');
    }
    $sessionToken = $_SESSION['call_edit_csrf'] ?? '';
    if (!is_string($csrfToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
        throw new UnexpectedValueException('La sesión del formulario expiró. Recargue la página.');
    }
    return (int) $id;
}

function report_call_photo_comment($value): string
{
    if ($value === null) {
        return '';
    }
    if (!is_string($value)) {
        throw new InvalidArgumentException('El comentario de la fotografía no es válido.');
    }
    $comment = trim($value);
    $length = function_exists('mb_strlen') ? mb_strlen($comment, 'UTF-8') : strlen($comment);
    if ($length > 500) {
        throw new InvalidArgumentException('El comentario no puede superar 500 caracteres.');
    }
    return $comment;
}

function report_call_photo_locked(mysqli $db, int $reportId): array
{
    $statement = $db->prepare('SELECT state_reporte, data_reporte FROM reporte WHERE id = ? FOR UPDATE');
    $statement->bind_param('i', $reportId);
    $statement->execute();
    $report = $statement->get_result()->fetch_assoc() ?: null;
    $statement->close();
    if ($report === null) {
        throw new OutOfBoundsException('El reporte no existe.');
    }
    $state = json_decode((string) $report['state_reporte'], true) ?: [];
    if (($state['reporte'] ?? '') !== 'llamada') {
        throw new InvalidArgumentException('El registro no es un reporte de Llamada.');
    }
    if (($state['status'] ?? '') === 'close') {
        throw new DomainException('Un reporte aprobado no permite cambios fotográficos.');
    }
    return $report;
}

function report_call_photo_raw_entries(array $data): array
{
    return isset($data['_photos']) && is_array($data['_photos']) ? array_values($data['_photos']) : [];
}

function report_call_photo_general_count(array $photos): int
{
    $count = 0;
    foreach ($photos as $photo) {
        if (is_array($photo) && (($photo['scope'] ?? 'general') === 'general')) {
            $count++;
        }
    }
    return $count;
}

function report_call_photo_public_items(int $reportId, array $data): array
{
    $items = [];
    foreach (report_photo_entries($data) as $photo) {
        if (($photo['scope'] ?? 'general') !== 'general') {
            continue;
        }
        $items[] = [
            'name' => $photo['name'],
            'url' => report_photo_url($reportId, $photo['name']),
            'comment' => (string) ($photo['comment'] ?? ''),
            'uploaded_at' => (string) ($photo['uploaded_at'] ?? ''),
            'group' => 'GENERALES',
        ];
    }
    return $items;
}

function report_call_photo_save_data(mysqli $db, int $reportId, array $data): void
{
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        throw new RuntimeException('No se pudieron preparar los datos fotográficos.');
    }
    $statement = $db->prepare('UPDATE reporte SET data_reporte = ?, updated_at = NOW() WHERE id = ?');
    $statement->bind_param('si', $encoded, $reportId);
    if (!$statement->execute()) {
        $statement->close();
        throw new RuntimeException('No se pudieron guardar los datos fotográficos.');
    }
    $statement->close();
}

function report_call_photo_upload($id, $csrfToken, array $files, $commentValue): array
{
    $reportId = report_call_photo_check_request($id, $csrfToken);
    $comment = report_call_photo_comment($commentValue);
    $upload = $files['photo'] ?? null;
    if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Debe seleccionar una fotografía válida.');
    }
    $size = (int) ($upload['size'] ?? 0);
    if ($size < 1 || $size > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('La fotografía debe pesar como máximo 5 MB.');
    }
    $temporaryPath = $upload['tmp_name'] ?? '';
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        throw new InvalidArgumentException('La carga de la fotografía no es válida.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporaryPath);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new InvalidArgumentException('Formato no permitido. Use JPEG, PNG o WEBP.');
    }
    $dimensions = @getimagesize($temporaryPath);
    if ($dimensions === false || $dimensions[0] < 1 || $dimensions[1] < 1
        || $dimensions[0] > 12000 || $dimensions[1] > 12000
        || ($dimensions[0] * $dimensions[1]) > 40000000) {
        throw new InvalidArgumentException('Las dimensiones de la fotografía no son válidas.');
    }

    $db = db();
    $db->begin_transaction();
    $storedPath = null;
    try {
        $report = report_call_photo_locked($db, $reportId);
        $data = json_decode((string) ($report['data_reporte'] ?? ''), true) ?: [];
        $photos = report_call_photo_raw_entries($data);
        if (report_call_photo_general_count($photos) >= 5) {
            throw new OverflowException('Solo se permiten 5 fotografías generales.');
        }
        $directory = dirname(__DIR__) . '/storage/report-photos/' . $reportId;
        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento de fotografías.');
        }
        if (!is_writable($directory)) {
            throw new RuntimeException('El almacenamiento de fotografías no tiene permisos de escritura.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $storedPath = $directory . '/' . $name;
        if (!move_uploaded_file($temporaryPath, $storedPath)) {
            throw new RuntimeException('No se pudo guardar la fotografía.');
        }
        @chmod($storedPath, 0640);
        $photos[] = [
            'name' => $name,
            'uploaded_at' => date('c'),
            'comment' => $comment,
            'scope' => 'general',
        ];
        $data['_photos'] = $photos;
        report_call_photo_save_data($db, $reportId, $data);
        $items = report_call_photo_public_items($reportId, $data);
        if (!$db->commit()) {
            throw new RuntimeException('No se pudo confirmar la fotografía.');
        }
        mysqli_close($db);
        return $items;
    } catch (Throwable $error) {
        $db->rollback();
        mysqli_close($db);
        if ($storedPath !== null && is_file($storedPath)) {
            @unlink($storedPath);
        }
        throw $error;
    }
}

function report_call_photo_update_comment($id, $csrfToken, $name, $commentValue): array
{
    $reportId = report_call_photo_check_request($id, $csrfToken);
    if (!is_string($name) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name)) {
        throw new InvalidArgumentException('La fotografía no es válida.');
    }
    $comment = report_call_photo_comment($commentValue);
    $db = db();
    $db->begin_transaction();
    try {
        $report = report_call_photo_locked($db, $reportId);
        $data = json_decode((string) ($report['data_reporte'] ?? ''), true) ?: [];
        $photos = report_call_photo_raw_entries($data);
        $found = false;
        foreach ($photos as &$photo) {
            if (is_array($photo) && ($photo['name'] ?? '') === $name && (($photo['scope'] ?? 'general') === 'general')) {
                $photo['comment'] = $comment;
                $found = true;
                break;
            }
        }
        unset($photo);
        if (!$found) {
            throw new OutOfBoundsException('La fotografía no pertenece al reporte.');
        }
        $data['_photos'] = $photos;
        report_call_photo_save_data($db, $reportId, $data);
        $items = report_call_photo_public_items($reportId, $data);
        if (!$db->commit()) {
            throw new RuntimeException('No se pudo confirmar el comentario.');
        }
        mysqli_close($db);
        return $items;
    } catch (Throwable $error) {
        $db->rollback();
        mysqli_close($db);
        throw $error;
    }
}

function report_call_photo_delete($id, $csrfToken, $name): array
{
    $reportId = report_call_photo_check_request($id, $csrfToken);
    if (!is_string($name) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name)) {
        throw new InvalidArgumentException('La fotografía no es válida.');
    }
    $db = db();
    $db->begin_transaction();
    try {
        $report = report_call_photo_locked($db, $reportId);
        $data = json_decode((string) ($report['data_reporte'] ?? ''), true) ?: [];
        $photos = report_call_photo_raw_entries($data);
        $found = false;
        $photos = array_values(array_filter($photos, function ($photo) use ($name, &$found) {
            if (is_array($photo) && ($photo['name'] ?? '') === $name && (($photo['scope'] ?? 'general') === 'general')) {
                $found = true;
                return false;
            }
            return true;
        }));
        if (!$found) {
            throw new OutOfBoundsException('La fotografía no pertenece al reporte.');
        }
        $data['_photos'] = $photos;
        report_call_photo_save_data($db, $reportId, $data);
        $items = report_call_photo_public_items($reportId, $data);
        if (!$db->commit()) {
            throw new RuntimeException('No se pudo confirmar la eliminación.');
        }
        mysqli_close($db);
        $path = dirname(__DIR__) . '/storage/report-photos/' . $reportId . '/' . $name;
        if (is_file($path) && !@unlink($path)) {
            error_log('No se pudo retirar archivo fotográfico huérfano: ' . $path);
        }
        return $items;
    } catch (Throwable $error) {
        $db->rollback();
        mysqli_close($db);
        throw $error;
    }
}

function report_call_photo_error_response(Throwable $error): array
{
    if ($error instanceof UnexpectedValueException) {
        return [403, $error->getMessage()];
    }
    if ($error instanceof DomainException || $error instanceof OverflowException) {
        return [409, $error->getMessage()];
    }
    if ($error instanceof OutOfBoundsException) {
        return [404, $error->getMessage()];
    }
    if ($error instanceof InvalidArgumentException) {
        return [400, $error->getMessage()];
    }
    error_log('Error en fotografías web de Llamada: ' . $error->getMessage());
    return [500, 'No se pudo completar la operación fotográfica.'];
}
