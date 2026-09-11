<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/report_photos.php';
require_once __DIR__ . '/includes/report_call_photos.php';
session_start();

function call_edit_error(int $status, string $message): void
{
    http_response_code($status);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Reporte Llamada</title><link rel="stylesheet" href="assets/css/bootstrap5/bootstrap.min.css"></head>'
        . '<body><main class="container py-5"><div class="alert alert-danger">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        . '</div><a class="btn btn-secondary" href="index.php">Volver al listado</a></main></body></html>';
    exit;
}

$idValue = $_GET['id'] ?? '';
if (!ctype_digit((string) $idValue) || (int) $idValue < 1) {
    call_edit_error(400, 'El identificador del reporte no es válido.');
}
$reportId = (int) $idValue;
$db = db();
$statement = $db->prepare('SELECT * FROM reporte WHERE id = ?');
$statement->bind_param('i', $reportId);
$statement->execute();
$report = $statement->get_result()->fetch_assoc() ?: null;
$statement->close();
mysqli_close($db);
if ($report === null) {
    call_edit_error(404, 'El reporte no existe.');
}
$state = json_decode((string) $report['state_reporte'], true) ?: [];
if (($state['reporte'] ?? '') !== 'llamada') {
    call_edit_error(400, 'El registro solicitado no es un reporte de Llamada.');
}
if (($state['status'] ?? '') === 'close') {
    call_edit_error(409, 'El reporte está aprobado y no puede editarse. Debe volverlo a PENDIENTE desde su visualización.');
}
$data = json_decode((string) ($report['data_reporte'] ?? ''), true) ?: [];
if (!isset($_SESSION['call_edit_csrf']) || !is_string($_SESSION['call_edit_csrf'])) {
    $_SESSION['call_edit_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['call_edit_csrf'];
$photoItems = report_call_photo_public_items($reportId, $data);
function call_value($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar reporte Llamada #<?php echo $reportId; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap5/bootstrap.min.css">
    <link rel="stylesheet" href="assets/plugins/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css?ver=0.64">
</head>
<body>
<main class="call-page">
    <div class="container call-container">
        <header class="call-header">
            <a href="index.php"><img src="images/logo-internepro.jpg" height="100" alt="Internepro S.A."></a>
            <div>
                <p class="call-kicker">REPORTE DE TRABAJO, MANTENIMIENTO Y CORRECTIVOS</p>
                <h1 id="call-title"><?php echo call_value($report['title_reporte']); ?></h1>
            </div>
        </header>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success" role="status">Reporte guardado correctamente.</div>
        <?php endif; ?>

        <form id="call-form" action="process.php" method="post" novalidate>
            <input type="hidden" name="type" value="insert_llamada">
            <input type="hidden" name="id" value="<?php echo $reportId; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo call_value($csrf); ?>">

            <section class="call-photos" id="call-photos" data-report-id="<?php echo $reportId; ?>" data-csrf-token="<?php echo call_value($csrf); ?>" aria-labelledby="call-photos-title">
                <div class="call-photos-heading">
                    <div>
                        <h2 id="call-photos-title">Fotografías generales</h2>
                        <p>Hasta 5 fotografías. El comentario es opcional.</p>
                    </div>
                    <span class="call-photo-count" data-photo-count><?php echo count($photoItems); ?> / 5</span>
                </div>
                <div class="call-photo-upload">
                    <label class="call-photo-picker">Tomar o seleccionar fotografía
                        <input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" data-photo-file>
                    </label>
                    <label>Comentario opcional
                        <textarea class="form-control" maxlength="500" rows="2" data-photo-new-comment></textarea>
                    </label>
                    <button class="btn btn-danger" type="button" data-photo-upload disabled><i class="fa fa-camera" aria-hidden="true"></i> Subir fotografía</button>
                </div>
                <div class="call-photo-progress" data-photo-progress hidden>
                    <div data-photo-progress-bar></div>
                </div>
                <p class="call-photo-status" data-photo-status role="status" aria-live="polite"></p>
                <div class="call-photo-grid" data-photo-grid></div>
            </section>

            <section class="call-fields" aria-label="Datos del reporte">
                <label>Cliente
                    <input class="form-control" id="call-client" name="cliente" maxlength="255" value="<?php echo call_value($report['cliente_reporte']); ?>">
                </label>
                <label>Equipo
                    <input class="form-control" name="equipo" maxlength="255" value="<?php echo call_value($report['equipo_reporte']); ?>">
                </label>
                <label>Fecha
                    <input class="form-control" id="call-date" type="date" name="fecha" value="<?php echo call_value($report['fecha_reporte']); ?>">
                </label>
            </section>

            <section class="call-narrative">
                <label>Trabajo realizado
                    <textarea class="form-control" name="trabajo_realizado" maxlength="10000" rows="5"><?php echo call_value($data['trabajo_realizado'] ?? ''); ?></textarea>
                </label>
                <label>Motivo
                    <textarea class="form-control" name="motivo" maxlength="10000" rows="4"><?php echo call_value($data['motivo'] ?? ''); ?></textarea>
                </label>
                <label>Piezas reemplazadas
                    <textarea class="form-control" name="piezas_reemplazadas" maxlength="10000" rows="4"><?php echo call_value($data['piezas_reemplazadas'] ?? ''); ?></textarea>
                </label>
                <label>Observaciones y recomendaciones
                    <textarea class="form-control" name="observaciones_recomendaciones" maxlength="10000" rows="5"><?php echo call_value($data['observaciones_recomendaciones'] ?? ''); ?></textarea>
                </label>
            </section>

            <section class="call-conformity" aria-label="Conformidad de empresa y cliente">
                <label>La empresa
                    <input class="form-control" name="firma_empresa" maxlength="255" value="<?php echo call_value(preg_match('/^[a-f0-9]{32}\.png$/', (string) ($data['firma_empresa'] ?? '')) ? '' : ($data['firma_empresa'] ?? '')); ?>">
                </label>
                <label>Cliente
                    <input class="form-control" name="firma_cliente" maxlength="255" value="<?php echo call_value(preg_match('/^[a-f0-9]{32}\.png$/', (string) ($data['firma_cliente'] ?? '')) ? '' : ($data['firma_cliente'] ?? '')); ?>">
                </label>
            </section>

            <div class="call-form-actions">
                <a class="btn btn-outline-secondary" href="index.php">Volver al listado</a>
                <button class="btn btn-danger" type="submit"><i class="fa fa-save" aria-hidden="true"></i> Guardar reporte</button>
            </div>
        </form>
    </div>
</main>
<script id="call-photo-data" type="application/json"><?php echo json_encode($photoItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<script src="assets/js/report-gallery.js?ver=1.0"></script>
<script src="assets/js/call-photos.js?ver=1.0"></script>
<script>
(function () {
    'use strict';
    var reportId = <?php echo $reportId; ?>;
    var client = document.getElementById('call-client');
    var date = document.getElementById('call-date');
    var title = document.getElementById('call-title');
    function refreshTitle() {
        var normalizedClient = client.value.trim().replace(/\s+/g, ' ');
        title.textContent = normalizedClient && date.value
            ? 'LLAMADA - ' + normalizedClient + ' - ' + date.value
            : 'LLAMADA #' + reportId;
    }
    client.addEventListener('input', refreshTitle);
    date.addEventListener('change', refreshTitle);

}());
</script>
</body>
</html>
