<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/report_signatures.php';
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
$signatureUrls = [];
foreach (report_signature_types() as $signatureType) {
    $reference = report_signature_reference($data, $signatureType);
    $signatureUrls[$signatureType] = $reference === null ? '' : report_signature_url($reportId, $signatureType, $reference);
}
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
    <link rel="stylesheet" href="assets/css/style.css?ver=0.62">
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

            <section class="call-signatures" aria-labelledby="signature-title">
                <h2 id="signature-title">Firmas opcionales</h2>
                <p>Las firmas no son obligatorias para guardar ni aprobar el reporte.</p>
                <?php foreach (['empresa' => 'La empresa', 'cliente' => 'Cliente'] as $signatureType => $signatureLabel): ?>
                    <div class="call-signature" data-signature-pad data-existing-url="<?php echo call_value($signatureUrls[$signatureType]); ?>">
                        <h3><?php echo $signatureLabel; ?></h3>
                        <canvas width="900" height="260" aria-label="Firma de <?php echo call_value($signatureLabel); ?>"></canvas>
                        <input type="hidden" name="firma_<?php echo $signatureType; ?>_data" value="">
                        <input type="hidden" name="firma_<?php echo $signatureType; ?>_clear" value="0">
                        <div class="call-signature-actions">
                            <button class="btn btn-outline-secondary" type="button" data-signature-clear>Limpiar</button>
                            <span data-signature-status><?php echo $signatureUrls[$signatureType] === '' ? 'Sin firma' : 'Firma guardada'; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <div class="call-form-actions">
                <a class="btn btn-outline-secondary" href="index.php">Volver al listado</a>
                <button class="btn btn-danger" type="submit"><i class="fa fa-save" aria-hidden="true"></i> Guardar reporte</button>
            </div>
        </form>
    </div>
</main>
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

    document.querySelectorAll('[data-signature-pad]').forEach(function (pad) {
        var canvas = pad.querySelector('canvas');
        var context = canvas.getContext('2d');
        var dataInput = pad.querySelector('input[name$="_data"]');
        var clearInput = pad.querySelector('input[name$="_clear"]');
        var status = pad.querySelector('[data-signature-status]');
        var drawing = false;
        var dirty = false;
        var existing = pad.getAttribute('data-existing-url') || '';
        context.lineWidth = 4;
        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.strokeStyle = '#202020';
        if (existing) {
            var image = new Image();
            image.onload = function () { context.drawImage(image, 0, 0, canvas.width, canvas.height); };
            image.src = existing;
        }
        function point(event) {
            var rect = canvas.getBoundingClientRect();
            return {
                x: (event.clientX - rect.left) * canvas.width / rect.width,
                y: (event.clientY - rect.top) * canvas.height / rect.height
            };
        }
        canvas.addEventListener('pointerdown', function (event) {
            drawing = true;
            dirty = true;
            clearInput.value = '0';
            canvas.setPointerCapture(event.pointerId);
            var current = point(event);
            context.beginPath();
            context.moveTo(current.x, current.y);
            status.textContent = 'Firma modificada';
        });
        canvas.addEventListener('pointermove', function (event) {
            if (!drawing) return;
            var current = point(event);
            context.lineTo(current.x, current.y);
            context.stroke();
        });
        function finish() { drawing = false; }
        canvas.addEventListener('pointerup', finish);
        canvas.addEventListener('pointercancel', finish);
        pad.querySelector('[data-signature-clear]').addEventListener('click', function () {
            context.clearRect(0, 0, canvas.width, canvas.height);
            dataInput.value = '';
            clearInput.value = existing ? '1' : '0';
            dirty = false;
            status.textContent = 'Sin firma';
        });
        pad.closest('form').addEventListener('submit', function () {
            if (dirty) {
                dataInput.value = canvas.toDataURL('image/png');
            }
        });
    });
}());
</script>
</body>
</html>
