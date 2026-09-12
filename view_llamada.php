<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/report_photos.php';
require_once __DIR__ . '/includes/report_pdf.php';
session_start();

function call_view_fail(int $status, string $message): void
{
    http_response_code($status);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Reporte Llamada</title><link rel="stylesheet" href="assets/css/bootstrap5/bootstrap.min.css"></head>'
        . '<body><main class="container py-5"><div class="alert alert-danger">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        . '</div><a class="btn btn-secondary" href="index.php">Volver al listado</a></main></body></html>';
    exit;
}

function call_view_value($value, string $empty = '—'): string
{
    $value = trim((string) ($value ?? ''));
    return htmlspecialchars($value === '' ? $empty : $value, ENT_QUOTES, 'UTF-8');
}

$idValue = $_GET['id'] ?? '';
if (!is_string($idValue) || !ctype_digit($idValue) || (int) $idValue < 1) {
    call_view_fail(400, 'El identificador del reporte no es válido.');
}
$reportId = (int) $idValue;

$db = db();
$statement = $db->prepare('SELECT * FROM reporte WHERE id = ?');
if ($statement === false) {
    mysqli_close($db);
    call_view_fail(500, 'No se pudo consultar el reporte.');
}
$statement->bind_param('i', $reportId);
$statement->execute();
$report = $statement->get_result()->fetch_assoc() ?: null;
$statement->close();
mysqli_close($db);

if ($report === null) {
    call_view_fail(404, 'El reporte no existe.');
}
$state = json_decode((string) ($report['state_reporte'] ?? ''), true) ?: [];
if (($state['reporte'] ?? '') !== 'llamada') {
    call_view_fail(400, 'El registro solicitado no es un reporte de Llamada.');
}
$data = json_decode((string) ($report['data_reporte'] ?? ''), true) ?: [];
$photos = report_photo_entries($data);
$pdfUrl = report_pdf_active_url($reportId, $state);
$shareUrl = $pdfUrl === null ? null : 'https://api.whatsapp.com/send?text=' . rawurlencode(
    'Reporte #' . $reportId . ' - ' . (string) ($report['title_reporte'] ?? '') . ': ' . $pdfUrl
);
if (!isset($_SESSION['reopen_csrf']) || !is_string($_SESSION['reopen_csrf'])) {
    $_SESSION['reopen_csrf'] = bin2hex(random_bytes(32));
}
$reopenCsrf = $_SESSION['reopen_csrf'];
$isApproved = ($state['status'] ?? '') === 'close';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo call_view_value($report['title_reporte'], 'Reporte Llamada'); ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap5/bootstrap.min.css">
    <link rel="stylesheet" href="assets/plugins/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css?ver=0.65">
    <script src="assets/js/jquery-3.2.1.min.js"></script>
</head>
<body>
<main class="call-page call-view-page">
    <div class="container call-container">
        <header class="call-header">
            <a href="index.php"><img src="images/logo-internepro.jpg" height="100" alt="Internepro S.A."></a>
            <div>
                <p class="call-kicker">REPORTE DE TRABAJO, MANTENIMIENTO Y CORRECTIVOS</p>
                <h1><?php echo call_view_value($report['title_reporte'], 'LLAMADA #' . $reportId); ?></h1>
            </div>
        </header>

        <section class="call-view-summary" aria-label="Datos generales">
            <div><strong>Cliente</strong><span><?php echo call_view_value($report['cliente_reporte']); ?></span></div>
            <div><strong>Equipo</strong><span><?php echo call_view_value($report['equipo_reporte']); ?></span></div>
            <div><strong>Fecha</strong><span><?php echo call_view_value($report['fecha_reporte']); ?></span></div>
            <div><strong>Estado</strong><span class="call-view-state <?php echo $isApproved ? 'is-approved' : 'is-pending'; ?>"><?php echo $isApproved ? 'APROBADO' : 'PENDIENTE'; ?></span></div>
        </section>

        <?php echo report_photo_gallery_markup($reportId, $photos); ?>

        <section class="call-view-narrative" aria-label="Contenido del reporte">
            <article><h2>Trabajo realizado</h2><p><?php echo call_view_value($data['trabajo_realizado'] ?? ''); ?></p></article>
            <article><h2>Motivo</h2><p><?php echo call_view_value($data['motivo'] ?? ''); ?></p></article>
            <article><h2>Piezas reemplazadas</h2><p><?php echo call_view_value($data['piezas_reemplazadas'] ?? ''); ?></p></article>
            <article><h2>Observaciones y recomendaciones</h2><p><?php echo call_view_value($data['observaciones_recomendaciones'] ?? ''); ?></p></article>
        </section>

        <section class="call-view-conformity" aria-label="Conformidad">
            <div><strong>La empresa</strong><span><?php echo call_view_value($data['firma_empresa'] ?? ''); ?></span></div>
            <div><strong>Cliente</strong><span><?php echo call_view_value($data['firma_cliente'] ?? ''); ?></span></div>
        </section>

        <section class="call-view-approval" aria-label="Aprobación del reporte">
            <div class="status" role="status" aria-live="polite">
                <?php if ($isApproved): ?>
                    <p>Reporte aprobado el <?php echo call_view_value($state['fecha'] ?? ''); ?> por <?php echo call_view_value($state['aprobado'] ?? ''); ?>.</p>
                    <div class="call-view-actions">
                        <?php if ($pdfUrl !== null): ?>
                            <a class="btn btn-outline-danger" href="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="fa fa-file-pdf-o" aria-hidden="true"></i> Abrir PDF</a>
                            <a class="btn btn-success" href="<?php echo htmlspecialchars((string) $shareUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="fa fa-whatsapp" aria-hidden="true"></i> Compartir</a>
                        <?php else: ?>
                            <p class="alert alert-warning">El reporte está aprobado, pero no tiene un PDF vigente.</p>
                        <?php endif; ?>
                        <button type="button" class="btn btn-danger reopen-report report-reopen-button">Volver a PENDIENTE</button>
                    </div>
                <?php else: ?>
                    <label for="approved-by">Nombre de quien aprueba</label>
                    <input type="text" class="form-control" id="approved-by" maxlength="255" autocomplete="name">
                    <button type="button" class="btn btn-danger approve-report">Aprobar reporte</button>
                <?php endif; ?>
            </div>
        </section>

        <div class="call-view-footer"><a class="btn btn-outline-secondary" href="index.php">Volver al listado</a></div>
    </div>
</main>
<script>
(function ($) {
    'use strict';
    var reportId = <?php echo $reportId; ?>;
    var $status = $('.call-view-approval .status');

    $('.call-view-approval').on('click', '.approve-report', function () {
        var approvedBy = ($('#approved-by').val() || '').trim();
        if (!approvedBy) {
            alert('Introduzca el nombre de quien aprueba el reporte.');
            return;
        }
        if (!confirm('¿Seguro que desea aprobar este reporte? Después de aprobarlo quedará bloqueado.')) {
            return;
        }
        $status.addClass('is-busy');
        $.ajax({
            url: 'process.php',
            type: 'post',
            dataType: 'json',
            data: { type: 'aprobando', id: reportId, cliente: approvedBy },
            success: function (response) {
                if (response.status === 200) {
                    window.location.reload();
                    return;
                }
                $status.removeClass('is-busy').append($('<p class="alert alert-danger"></p>').text(response.message || 'No se pudo aprobar el reporte.'));
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                $status.removeClass('is-busy').append($('<p class="alert alert-danger"></p>').text(response.message || 'No se pudo generar el PDF. El reporte permanece PENDIENTE.'));
            }
        });
    });

    $('.call-view-approval').on('click', '.reopen-report', function () {
        if (!confirm('¿Seguro que desea volver este reporte a PENDIENTE? El PDF actual dejará de estar vigente.')) {
            return;
        }
        $status.addClass('is-busy');
        $.ajax({
            url: 'process.php',
            type: 'post',
            dataType: 'json',
            data: { type: 'reopen', id: reportId, csrf_token: <?php echo json_encode($reopenCsrf); ?> },
            success: function (response) {
                if (response.status === 200) {
                    window.location.reload();
                    return;
                }
                $status.removeClass('is-busy').append($('<p class="alert alert-danger"></p>').text(response.message || 'No se pudo cambiar el estado.'));
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                $status.removeClass('is-busy').append($('<p class="alert alert-danger"></p>').text(response.message || 'No se pudo cambiar el estado.'));
            }
        });
    });
}(jQuery));
</script>
<script src="assets/js/report-gallery.js?ver=1.0"></script>
</body>
</html>
