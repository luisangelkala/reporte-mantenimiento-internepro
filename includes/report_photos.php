<?php

function report_photo_section_labels(): array
{
    return [
        'a_2' => 'CABINA',
        'a_9' => 'CONTROL',
        'a_15' => 'CREMALLERA',
        'a_22' => 'PARACAIDAS',
        'a_28' => 'PUERTAS DE PASILLO',
        'a_32' => 'FOSO',
    ];
}

/**
 * Utilidades compartidas para mostrar las fotografías privadas de los reportes.
 */

function report_photo_entries($dataReporte): array
{
    if (is_string($dataReporte)) {
        $dataReporte = json_decode($dataReporte, true);
    }
    if (!is_array($dataReporte)) {
        return [];
    }

    $photos = isset($dataReporte['_photos']) && is_array($dataReporte['_photos'])
        ? $dataReporte['_photos']
        : [];
    $result = [];
    $seen = [];

    foreach ($photos as $photo) {
        $name = is_array($photo) ? ($photo['name'] ?? '') : '';
        if (!is_string($name) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) || isset($seen[$name])) {
            continue;
        }
        $scope = is_array($photo) && ($photo['scope'] ?? 'general') === 'section' ? 'section' : 'general';
        $sectionKey = $scope === 'section' && is_string($photo['section_key'] ?? null)
            ? $photo['section_key']
            : '';
        if ($scope === 'section' && !array_key_exists($sectionKey, report_photo_section_labels())) {
            continue;
        }
        $seen[$name] = true;
        $result[] = [
            'name' => $name,
            'uploaded_at' => is_array($photo) && is_string($photo['uploaded_at'] ?? null)
                ? $photo['uploaded_at']
                : '',
            'comment' => is_array($photo) && is_string($photo['comment'] ?? null)
                ? trim($photo['comment'])
                : '',
            'scope' => $scope,
            'section_key' => $sectionKey,
        ];
    }

    return $result;
}

function report_photo_secret(): string
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }

    $config = require dirname(__DIR__) . '/config/auth.php';
    $configuredSecret = is_array($config) ? ($config['secret'] ?? '') : '';
    if (!is_string($configuredSecret) || $configuredSecret === '') {
        throw new RuntimeException('La configuración privada de fotografías no está disponible.');
    }

    $secret = hash('sha256', 'web-report-photo|' . $configuredSecret, true);
    return $secret;
}

function report_photo_signature(int $reportId, string $name, int $expires): string
{
    return hash_hmac('sha256', $reportId . "\n" . $name . "\n" . $expires, report_photo_secret());
}

function report_photo_url(int $reportId, string $name, ?int $expires = null): string
{
    $expires = $expires ?? (time() + 28800);
    $query = http_build_query([
        'id' => $reportId,
        'name' => $name,
        'expires' => $expires,
        'signature' => report_photo_signature($reportId, $name, $expires),
    ], '', '&', PHP_QUERY_RFC3986);

    return 'photo.php?' . $query;
}

function report_photo_items(int $reportId, array $photos): array
{
    $expires = time() + 28800;
    $items = [];
    $sectionLabels = report_photo_section_labels();
    foreach ($photos as $photo) {
        if (is_array($photo) && isset($photo['name'])) {
            $scope = ($photo['scope'] ?? 'general') === 'section' ? 'section' : 'general';
            $sectionKey = $scope === 'section' ? (string) ($photo['section_key'] ?? '') : '';
            $items[] = [
                'url' => report_photo_url($reportId, $photo['name'], $expires),
                'comment' => is_string($photo['comment'] ?? null) ? $photo['comment'] : '',
                'group' => $scope === 'section' ? ($sectionLabels[$sectionKey] ?? 'SECCION') : 'GENERALES',
            ];
        }
    }
    return $items;
}

function report_photo_data_attribute(array $items): string
{
    return htmlspecialchars(
        json_encode(array_values($items), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ENT_QUOTES,
        'UTF-8'
    );
}

function report_photo_list_button(int $reportId, array $photos): string
{
    if ($photos === []) {
        return '';
    }

    $items = report_photo_items($reportId, $photos);
    $count = count($items);
    $label = $count === 1 ? 'Ver 1 fotografía' : 'Ver ' . $count . ' fotografías';

    return '<button type="button" class="report-photo-list-button" data-photo-gallery data-photo-index="0" data-report-photos="'
        . report_photo_data_attribute($items) . '" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-picture-o" aria-hidden="true"></i></button>';
}

function report_photo_gallery_markup(int $reportId, array $photos): string
{
    if ($photos === []) {
        return '';
    }

    $groups = [
        'general' => ['title' => 'Fotografías generales', 'photos' => []],
    ];
    foreach (report_photo_section_labels() as $sectionKey => $label) {
        $groups['section-' . $sectionKey] = ['title' => 'Fotografías de ' . $label, 'photos' => []];
    }
    foreach ($photos as $photo) {
        $groupKey = ($photo['scope'] ?? 'general') === 'section'
            ? 'section-' . ($photo['section_key'] ?? '')
            : 'general';
        if (isset($groups[$groupKey])) {
            $groups[$groupKey]['photos'][] = $photo;
        }
    }

    $html = '<section class="report-photo-section" aria-labelledby="report-photo-title-' . $reportId . '">';
    $html .= '<h3 id="report-photo-title-' . $reportId . '">Evidencia fotográfica</h3>';
    foreach ($groups as $group) {
        if ($group['photos'] === []) {
            continue;
        }
        $items = report_photo_items($reportId, $group['photos']);
        $attribute = report_photo_data_attribute($items);
        $html .= '<div class="report-photo-group">';
        $html .= '<h4>' . htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') . '</h4>';
        $html .= '<div class="report-photo-thumbnails" data-report-photos="' . $attribute . '">';
        foreach ($items as $index => $item) {
            $number = $index + 1;
            $escapedUrl = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
            $comment = trim((string) $item['comment']);
            $visibleComment = $comment === '' ? 'Sin comentario' : $comment;
            $html .= '<article class="report-photo-card">';
            $html .= '<button type="button" data-photo-gallery data-photo-index="' . $index
                . '" aria-label="Ampliar fotografía ' . $number . ' de ' . count($items) . '">';
            $html .= '<img src="' . $escapedUrl . '" alt="Fotografía ' . $number . ' del reporte" loading="lazy">';
            $html .= '</button>';
            $html .= '<p>' . htmlspecialchars($visibleComment, ENT_QUOTES, 'UTF-8') . '</p>';
            $html .= '</article>';
        }
        $html .= '</div></div>';
    }

    $html .= '</section>';
    return $html;
}
