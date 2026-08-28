<?php

require_once __DIR__ . '/report_photos.php';

/**
 * Generador PDF autocontenido para la instantanea inmutable de un reporte aprobado.
 * No expone el archivo: la entrega publica y sus firmas pertenecen a la fase 5.5.
 */
final class ReportPdfDocument
{
    private $pages = [];
    private $images = [];
    private $imageByHash = [];
    private $pageIndex = 0;
    private $y = 800.0;

    public function __construct()
    {
        $this->pages[] = ['content' => '', 'images' => []];
    }

    public function heading(string $text, int $size = 14): void
    {
        $this->ensureSpace($size + 14);
        $this->text($text, $size, true, 0, 5);
    }

    public function text(string $text, int $size = 10, bool $bold = false, float $indent = 0, float $after = 3): void
    {
        $lines = $this->wrap($text, $size, 515 - $indent);
        foreach ($lines as $line) {
            $this->ensureSpace($size + 5);
            $font = $bold ? 'F2' : 'F1';
            $encoded = $this->encodeText($line);
            $this->pages[$this->pageIndex]['content'] .= sprintf(
                "BT /%s %d Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
                $font,
                $size,
                40 + $indent,
                $this->y,
                $this->escapeText($encoded)
            );
            $this->y -= $size + 3;
        }
        $this->y -= $after;
    }

    public function rule(): void
    {
        $this->ensureSpace(10);
        $this->pages[$this->pageIndex]['content'] .= sprintf(
            "0.75 w 0.75 G 40 %.2F m 555 %.2F l S\n",
            $this->y,
            $this->y
        );
        $this->y -= 10;
    }

    public function image(string $path, string $caption): void
    {
        $prepared = report_pdf_prepare_image($path);
        if ($prepared === null) {
            throw new RuntimeException('Una fotografia registrada no esta disponible o no es valida para el PDF.');
        }

        $maxWidth = 500.0;
        $maxHeight = 310.0;
        $scale = min($maxWidth / $prepared['width'], $maxHeight / $prepared['height'], 1.0);
        $width = max(1.0, $prepared['width'] * $scale);
        $height = max(1.0, $prepared['height'] * $scale);
        $this->ensureSpace($height + 38);

        $hash = hash('sha256', $prepared['data']);
        if (!isset($this->imageByHash[$hash])) {
            $index = count($this->images) + 1;
            $name = 'Im' . $index;
            $this->images[$name] = $prepared;
            $this->imageByHash[$hash] = $name;
        }
        $name = $this->imageByHash[$hash];
        $this->pages[$this->pageIndex]['images'][$name] = true;
        $x = 40 + (($maxWidth - $width) / 2);
        $bottom = $this->y - $height;
        $this->pages[$this->pageIndex]['content'] .= sprintf(
            "q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n",
            $width,
            $height,
            $x,
            $bottom,
            $name
        );
        $this->y = $bottom - 6;
        $this->text($caption, 9, false, 6, 8);
    }

    public function output(): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $nextId = 5;
        $imageIds = [];
        foreach ($this->images as $name => $image) {
            $imageIds[$name] = $nextId;
            $colorSpace = $image['channels'] === 1 ? '/DeviceGray' : '/DeviceRGB';
            $objects[$nextId] = sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace %s /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                $image['width'],
                $image['height'],
                $colorSpace,
                strlen($image['data']),
                $image['data']
            );
            $nextId++;
        }

        $pageIds = [];
        foreach ($this->pages as $page) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $pageIds[] = $pageId;
            $objects[$contentId] = '<< /Length ' . strlen($page['content']) . ">>\nstream\n"
                . $page['content'] . "endstream";
            $xObjects = '';
            foreach (array_keys($page['images']) as $name) {
                $xObjects .= '/' . $name . ' ' . $imageIds[$name] . ' 0 R ';
            }
            $resources = '<< /Font << /F1 3 0 R /F2 4 0 R >>';
            if ($xObjects !== '') {
                $resources .= ' /XObject << ' . $xObjects . '>>';
            }
            $resources .= ' >>';
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources %s /Contents %d 0 R >>',
                $resources,
                $contentId
            );
        }
        $kids = implode(' ', array_map(function ($id) {
            return $id . ' 0 R';
        }, $pageIds));
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= 'startxref' . "\n" . $xref . "\n%%EOF\n";
        return $pdf;
    }

    private function ensureSpace(float $height): void
    {
        if ($this->y - $height >= 42) {
            return;
        }
        $this->pages[] = ['content' => '', 'images' => []];
        $this->pageIndex++;
        $this->y = 800.0;
        $this->text('Internepro S.A. - Continuacion del reporte', 9, true, 0, 10);
    }

    private function wrap(string $text, int $size, float $width): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return [''];
        }
        $limit = max(12, (int) floor($width / ($size * 0.52)));
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if (strlen($this->encodeText($candidate)) <= $limit) {
                $line = $candidate;
                continue;
            }
            if ($line !== '') {
                $lines[] = $line;
            }
            while (strlen($this->encodeText($word)) > $limit) {
                $lines[] = substr($word, 0, $limit);
                $word = substr($word, $limit);
            }
            $line = $word;
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        return $lines ?: [''];
    }

    private function encodeText(string $text): string
    {
        if (function_exists('iconv')) {
            $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($encoded !== false) {
                return $encoded;
            }
        }
        return preg_replace('/[^\x20-\x7E]/', '?', $text);
    }

    private function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $text);
    }
}

function report_pdf_prepare_image(string $path): ?array
{
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    $info = @getimagesize($path);
    if (!is_array($info) || empty($info[0]) || empty($info[1])) {
        return null;
    }
    if (($info[2] ?? 0) === IMAGETYPE_JPEG) {
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }
        return [
            'data' => $data,
            'width' => (int) $info[0],
            'height' => (int) $info[1],
            'channels' => isset($info['channels']) && (int) $info['channels'] === 1 ? 1 : 3,
        ];
    }
    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        throw new RuntimeException('El servidor necesita la extension GD para incorporar una fotografia PNG o WEBP al PDF.');
    }
    $sourceData = @file_get_contents($path);
    $source = $sourceData === false ? false : @imagecreatefromstring($sourceData);
    if ($source === false) {
        return null;
    }
    $canvas = imagecreatetruecolor((int) $info[0], (int) $info[1]);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagecopy($canvas, $source, 0, 0, 0, 0, (int) $info[0], (int) $info[1]);
    ob_start();
    imagejpeg($canvas, null, 88);
    $data = ob_get_clean();
    imagedestroy($canvas);
    imagedestroy($source);
    if (!is_string($data) || $data === '') {
        return null;
    }
    return ['data' => $data, 'width' => (int) $info[0], 'height' => (int) $info[1], 'channels' => 3];
}

function report_pdf_sections(string $type): array
{
    $raw = $type === 'alimak' ? [
        ['a_0', 'INSTRUCCIONES GENERALES', null, ['Comportamiento del equipo informado por la persona a cargo', 'Funcionamiento: aceleracion, desaceleracion, vibracion y ruido', 'Inspeccion general en condiciones de operacion']],
        ['a_1', 'CUARTO DE MAQUINAS', 'ab_1', ['Informacion de instrucciones y de seguridad escalera']],
        ['a_2', 'CABINA', 'ab_2', ['Estado de los paneles de la cabina: limpieza y golpes']],
        ['a_3', 'PUERTAS', 'ab_3', ['Puerta de cabina', 'Puerta de escotilla', 'Interlocks', 'Switch actuador', 'Actuador', 'Switch de puerta']],
        ['a_4', 'CABINA', 'ab_4', ['Luz de cabina', 'Abanicos', 'Alarma', 'Display', 'Stop', 'Intercom', 'Botones de llamada']],
        ['a_5', 'LIMITES', 'ab_5', ['Limit switch ref', 'SW Final', 'SW up dw']],
        ['a_6', 'INSPECCION', 'ab_6', ['Caja de inspeccion', 'Stop', 'Switch inspeccion', 'SW emergencia', 'Boton up', 'Dw']],
        ['a_7', 'PANEL', 'ab_7', ['Estado de panel', 'Tapa de regletas', 'Estado cable viajero']],
        ['a_8', 'PANEL BASE', 'ab_8', ['Estado panel Base', 'ACL Base', 'Switch']],
        ['a_9', 'CONTROL', 'ab_9', ['Contactores', 'Auxiliares', 'Breaker', 'Relay', 'Temporizadores', 'Conexiones', 'ACL', 'Tarjeta com']],
        ['a_10', 'ACEITE', 'ab_10', ['Nivel de aceite', 'Temperatura', 'Filtro']],
        ['a_11', 'MAQUINA', 'ab_11', ['Ruidos', 'Vibraciones', 'Pintura']],
        ['a_12', 'FRENO', 'ab_12', ['Freno magnetico', 'Desgaste', 'Parada']],
        ['a_13', 'EMERGENCIA', 'ab_13', ['Recorrido en parada de emergencia']],
        ['a_14', 'MOTOR', 'ab_14', ['Motor electrico', 'Bloque conex']],
        ['a_15', 'CREMALLERA', 'ab_15', ['Pinon', 'Cremallera', 'Contrarueda']],
        ['a_16', 'GUIA', 'ab_16', ['Roller guide conjunto maquina']],
        ['a_17', 'GUIA', 'ab_17', ['Roller guide cabina']],
        ['a_18', 'CABINA', 'ab_18', ['Soportes de cabina']],
        ['a_19', 'FRENO CENTRIFUGO', 'ab_19', ['Freno centrifugo', 'Cables', 'Resortes', 'Varillas', 'Ajuste', 'Coopling monitor']],
        ['a_20', 'SEGURIDAD', 'ab_20', ['Bloque seguridad', 'Contrarueda']],
        ['a_21', 'DOCUMENTACION', 'ab_21', ['Fecha de vencimiento']],
        ['a_22', 'PARACAIDAS', 'ab_22', ['Prueba paracaida fecha']],
        ['a_23', 'CREMALLERA', 'ab_23', ['Estado de la cremallera alineacion']],
        ['a_24', 'MASTIL', 'ab_24', ['Ajuste del mastil: tornillos, tuercas, apriete y fijacion']],
        ['a_25', 'CABLE VIAJERO', 'ab_25', ['Estado del cable viajero']],
        ['a_26', 'CABLE VIAJERO', 'ab_26', ['Soporte del cable viajero y guias']],
        ['a_27', 'PUERTA', 'ab_27', ['Mecanismo de la puerta', 'Cam', 'Bisagras', 'Lock flap', 'Switch pasillo']],
        ['a_28', 'PUERTAS DE PASILLO', 'ab_28', ['Puertas de pasillo: estado y limpieza']],
        ['a_29', 'BUFFER', 'ab_29', ['Buffer superior']],
        ['a_30', 'PARADAS', 'ab_30', ['Ajuste de camas de paradas y banderas']],
        ['a_31', 'BOTONERAS', 'ab_31', ['Botoneras de pasillos', 'Botones', 'Stop']],
        ['a_32', 'FOSO', 'ab_32', ['Stop de foso']],
        ['a_33', 'BUFFER', 'ab_33', ['Buffer']],
        ['a_34', 'CABINA', 'ab_34', ['Estado del marco de cabina']],
        ['a_35', 'TROLLEY', 'ab_35', ['Roller guias trolley', 'Roller de cable']],
        ['a_36', 'TROLLEY', 'ab_36', ['Distancia del trolley de la base']],
        ['a_37', 'TROLLEY', 'ab_37', ['Cremallera', 'Roller', 'Puertas', 'Inter lock', 'Dispositivo de seguridad', 'Mecanismos freno centrifugo', 'Trolley']],
    ] : [
        ['s_0', 'INSTRUCCIONES GENERALES', null, ['Comportamiento del ascensor informado por la persona a cargo', 'Funcionamiento: aceleracion, desaceleracion, vibracion y ruido', 'Inspeccion general en condiciones de operacion']],
        ['s_1', 'CUARTO DE MAQUINAS', 'ob_1', ['Iluminacion', 'Senalizacion', 'Tapa de ductos', 'Tapas de pases de cable', 'Climatizacion', 'Filtraciones', 'Pintura']],
        ['s_2', 'MAQUINA Y FRENO', 'ob_2', ['Ruido', 'Vibraciones', 'Conexiones flojas', 'Desgaste de la zapata del freno', 'Frenado de emergencia', 'Nivel de aceite']],
        ['s_3', 'GOBERNADOR Y CABLE', 'ob_3', ['Ruido', 'Switcher', 'Cable', 'Sello de fabrica', 'Velocidad de disparo m/s']],
        ['s_4', 'TERMINALES DE CABLES', 'ob_4', ['Perros', 'Tuercas', 'Pasapuntas', 'Quitavueltas']],
        ['s_5', 'CABINA', 'ob_5', ['Alarma', 'Interfon', 'Iluminacion', 'Piso', 'Falso techo y paneles flojos', 'Abanicos', 'Display', 'Botones']],
        ['s_6', 'PUERTA DE CABINA', 'ob_6', ['Operador de puerta', 'Correas o cables', 'Ruedas y contrarruedas', 'Zapatos', 'Switch', 'Fotocelda', 'Velocidad', 'Botones', 'Ruido']],
        ['s_7', 'SOBRE CABINA', 'ob_7', ['Switch del paracaida', 'Limites de recorrido', 'Inductores', 'Pesacarga', 'Caja de conexiones', 'Tarjeta de comunicacion']],
        ['s_8', 'SOBRE CABINA', 'ob_8', ['Baranda de proteccion', 'Cover de la polea', 'Polea', 'Terminales de cables', 'Tuercas y pasapuntas', 'Zapatos de cabina y contrapeso', 'Aceiteras']],
        ['s_9', 'SOBRE CABINA', 'ob_9', ['Estado de rieles', 'Empates', 'Brackets', 'Clip', 'Lubricacion']],
        ['s_10', 'BAJO CABINA', 'ob_10', ['Bloque seguridad', 'Cadena de compensacion', 'Tensores']],
        ['s_11', 'BAJO CABINA', 'ob_11', ['Estado del marco rotura', 'Oxidacion', 'Zapatos de cabina', 'Pintura', 'Corrosion']],
        ['s_12', 'PIT', 'ob_12', ['Buffer', 'Pesa del paracaidas', 'Iluminacion', 'Switcher', 'Limite']],
        ['s_13', 'PUERTAS DE PASILLO', 'ob_13', ['Ruedas y contrarruedas', 'Zapatos', 'Switches Sill', 'Pintura', 'Limpieza y lubricacion', 'Corrosion']],
        ['s_14', 'PASILLO', 'ob_14', ['Interfono', 'Bombero', 'Displays', 'Botoneras', 'Tarjetas de comunicacion']],
        ['s_15', 'CALIDAD DE RECORRIDO', 'ob_15', ['Ruidos', 'Golpes', 'Movimientos', 'Nivel de parada']],
    ];

    $sections = [];
    foreach ($raw as $section) {
        $items = [];
        foreach ($section[3] as $index => $label) {
            $items[] = ['key' => $section[0] . '_' . chr(97 + $index), 'label' => $label];
        }
        $sections[] = ['key' => $section[0], 'title' => $section[1], 'observation' => $section[2], 'items' => $items];
    }
    return $sections;
}

function report_pdf_type(array $state, $dataReporte): string
{
    if (($state['reporte'] ?? '') === 'alimak') {
        return 'alimak';
    }
    $data = is_string($dataReporte) ? json_decode($dataReporte, true) : $dataReporte;
    if (is_array($data)) {
        foreach (array_keys($data) as $key) {
            if (is_string($key) && strpos($key, 'a_') === 0) {
                return 'alimak';
            }
        }
    }
    return 'elevador';
}

function report_pdf_display_value($value): string
{
    if (is_bool($value)) {
        return $value ? 'Si' : 'No';
    }
    if (is_scalar($value)) {
        $value = trim((string) $value);
        return $value === '' ? 'Sin marcar' : html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return 'Sin marcar';
}

function report_pdf_generate(array $report, array $approvalState, int $version): array
{
    $reportId = (int) ($report['id'] ?? 0);
    if ($reportId < 1) {
        throw new RuntimeException('No se puede generar el PDF de un reporte invalido.');
    }
    $type = report_pdf_type($approvalState, $report['data_reporte'] ?? null);
    $data = json_decode((string) ($report['data_reporte'] ?? ''), true) ?: [];
    $observations = json_decode((string) ($report['obs_reporte'] ?? ''), true) ?: [];
    $photos = report_photo_entries($data);
    $document = new ReportPdfDocument();

    $document->text('INTERNEPRO S.A.', 18, true, 0, 2);
    $document->text('Reporte de mantenimiento ' . ($type === 'alimak' ? 'ALIMAK' : 'ELEVADOR'), 13, true, 0, 8);
    $document->rule();
    $document->text('Reporte #' . $reportId . ' - ' . report_pdf_display_value($report['title_reporte'] ?? ''), 14, true, 0, 7);
    $document->text('Cliente: ' . report_pdf_display_value($report['cliente_reporte'] ?? ''), 10, true);
    $document->text('Fecha del mantenimiento: ' . report_pdf_display_value($report['fecha_reporte'] ?? ''), 10);
    $document->text('Equipo: ' . report_pdf_display_value($report['equipo_reporte'] ?? ''), 10);
    $document->text('Tecnico: ' . report_pdf_display_value($report['tecnico_reporte'] ?? ''), 10);
    $document->text('Aprobado por: ' . report_pdf_display_value($approvalState['aprobado'] ?? ''), 10, true);
    $document->text('Fecha de aprobacion: ' . report_pdf_display_value($approvalState['fecha'] ?? ''), 10, false, 0, 8);
    $document->text('Nomenclatura: OK = inspeccionado y en optimas condiciones; X = requiere otras acciones; R = reparacion realizada.', 9, false, 0, 10);

    $generalPhotos = array_values(array_filter($photos, function ($photo) {
        return ($photo['scope'] ?? 'general') === 'general';
    }));
    if ($generalPhotos !== []) {
        $document->heading('FOTOGRAFIAS GENERALES', 13);
        report_pdf_add_photos($document, $reportId, $generalPhotos);
    }

    foreach (report_pdf_sections($type) as $index => $section) {
        $document->heading(($index + 1) . '. ' . $section['title'], 12);
        foreach ($section['items'] as $item) {
            $document->text($item['label'] . ': ' . report_pdf_display_value($data[$item['key']] ?? ''), 9, false, 8, 2);
        }
        if ($section['observation'] !== null) {
            $document->text('Observaciones: ' . report_pdf_display_value($observations[$section['observation']] ?? ''), 9, true, 8, 5);
        }
        $sectionPhotos = array_values(array_filter($photos, function ($photo) use ($section) {
            return ($photo['scope'] ?? '') === 'section' && ($photo['section_key'] ?? '') === $section['key'];
        }));
        if ($sectionPhotos !== []) {
            $document->text('Evidencia fotografica de ' . $section['title'], 10, true, 8, 5);
            report_pdf_add_photos($document, $reportId, $sectionPhotos);
        }
        $document->rule();
    }

    $document->heading('OBSERVACIONES GENERALES', 13);
    $document->text('Comentarios: ' . report_pdf_display_value($observations['ob_comentario'] ?? ''), 10, false, 0, 5);
    $document->text('Recomendaciones: ' . report_pdf_display_value($observations['ob_recomendacion'] ?? ''), 10, false, 0, 8);
    $document->text('Documento generado por el backend de Internepro al aprobar el reporte. Version ' . $version . '.', 8);

    $directory = dirname(__DIR__) . '/storage/report-pdfs/' . $reportId;
    if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento privado de PDF.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('El almacenamiento privado de PDF no tiene permisos de escritura.');
    }
    $random = bin2hex(random_bytes(12));
    $name = 'report-' . $reportId . '-v' . $version . '-' . $random . '.pdf';
    $path = $directory . '/' . $name;
    $temporary = tempnam($directory, '.pdf-');
    if ($temporary === false) {
        throw new RuntimeException('No se pudo crear el archivo temporal del PDF.');
    }
    try {
        $written = @file_put_contents($temporary, $document->output(), LOCK_EX);
        if ($written === false || $written < 100) {
            throw new RuntimeException('No se pudo escribir el PDF completo.');
        }
        if (!@rename($temporary, $path)) {
            throw new RuntimeException('No se pudo confirmar el archivo PDF generado.');
        }
        @chmod($path, 0660);
    } catch (Throwable $error) {
        if (is_file($temporary)) {
            @unlink($temporary);
        }
        throw $error;
    }

    return [
        'name' => $name,
        'path' => $path,
        'generated_at' => date('c'),
        'version' => $version,
    ];
}

function report_pdf_add_photos(ReportPdfDocument $document, int $reportId, array $photos): void
{
    foreach ($photos as $index => $photo) {
        $comment = trim((string) ($photo['comment'] ?? ''));
        $caption = 'Foto ' . ($index + 1) . ': ' . ($comment === '' ? 'Sin comentario' : $comment);
        $path = dirname(__DIR__) . '/storage/report-photos/' . $reportId . '/' . $photo['name'];
        $document->image($path, $caption);
    }
}

function report_approve_with_pdf(mysqli $connection, int $reportId, string $approvedBy): array
{
    $approvedBy = trim($approvedBy);
    if ($reportId < 1 || $approvedBy === '') {
        throw new InvalidArgumentException('El reporte y el nombre de quien aprueba son obligatorios.');
    }
    if (strlen($approvedBy) > 255) {
        $approvedBy = substr($approvedBy, 0, 255);
    }

    $generatedPath = null;
    if (!$connection->begin_transaction()) {
        throw new RuntimeException('No se pudo iniciar la aprobacion transaccional.');
    }
    try {
        $statement = $connection->prepare('SELECT * FROM reporte WHERE id = ? FOR UPDATE');
        if ($statement === false) {
            throw new RuntimeException('No se pudo preparar la consulta del reporte.');
        }
        $statement->bind_param('i', $reportId);
        $statement->execute();
        $report = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        if ($report === null) {
            throw new OutOfBoundsException('Reporte no encontrado.');
        }

        $state = json_decode((string) ($report['state_reporte'] ?? ''), true) ?: [];
        if (($state['status'] ?? '') === 'close') {
            throw new DomainException('El reporte ya esta aprobado.');
        }
        $previousVersion = isset($state['pdf']['version']) ? (int) $state['pdf']['version'] : 0;
        $state['status'] = 'close';
        $state['aprobado'] = $approvedBy;
        $state['fecha'] = date('Y-m-d');
        $state['reporte'] = report_pdf_type($state, $report['data_reporte'] ?? null);

        $pdf = report_pdf_generate($report, $state, $previousVersion + 1);
        $generatedPath = $pdf['path'];
        $state['pdf'] = [
            'name' => $pdf['name'],
            'generated_at' => $pdf['generated_at'],
            'version' => $pdf['version'],
            'status' => 'active',
        ];
        $encodedState = json_encode($state, JSON_UNESCAPED_UNICODE);
        if ($encodedState === false) {
            throw new RuntimeException('No se pudo registrar el estado del PDF.');
        }
        $statement = $connection->prepare('UPDATE reporte SET state_reporte = ?, updated_at = NOW() WHERE id = ?');
        if ($statement === false) {
            throw new RuntimeException('No se pudo preparar la aprobacion del reporte.');
        }
        $statement->bind_param('si', $encodedState, $reportId);
        $statement->execute();
        $updated = $statement->affected_rows === 1;
        $statement->close();
        if (!$updated) {
            throw new RuntimeException('No se pudo registrar la aprobacion y su PDF.');
        }
        if (!$connection->commit()) {
            throw new RuntimeException('No se pudo confirmar la aprobacion y su PDF.');
        }
        $report['state_reporte'] = $encodedState;
        $report['updated_at'] = date('Y-m-d H:i:s');
        return ['report' => $report, 'state' => $state];
    } catch (Throwable $error) {
        $connection->rollback();
        if (is_string($generatedPath) && is_file($generatedPath)) {
            @unlink($generatedPath);
        }
        throw $error;
    }
}

function report_pdf_invalidate_state(array $state): array
{
    if (isset($state['pdf']) && is_array($state['pdf'])) {
        $state['pdf']['status'] = 'invalidated';
        $state['pdf']['invalidated_at'] = date('c');
    }
    return $state;
}
