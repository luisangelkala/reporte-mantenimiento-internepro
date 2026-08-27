<?php

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
        $seen[$name] = true;
        $result[] = [
            'name' => $name,
            'uploaded_at' => is_array($photo) && is_string($photo['uploaded_at'] ?? null)
                ? $photo['uploaded_at']
                : '',
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

function report_photo_urls(int $reportId, array $photos): array
{
    $expires = time() + 28800;
    $urls = [];
    foreach ($photos as $photo) {
        if (is_array($photo) && isset($photo['name'])) {
            $urls[] = report_photo_url($reportId, $photo['name'], $expires);
        }
    }
    return $urls;
}

function report_photo_data_attribute(array $urls): string
{
    return htmlspecialchars(
        json_encode(array_values($urls), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ENT_QUOTES,
        'UTF-8'
    );
}

function report_photo_list_button(int $reportId, array $photos): string
{
    if ($photos === []) {
        return '';
    }

    $urls = report_photo_urls($reportId, $photos);
    $count = count($urls);
    $label = $count === 1 ? 'Ver 1 fotografía' : 'Ver ' . $count . ' fotografías';

    return '<button type="button" class="report-photo-list-button" data-photo-gallery data-photo-index="0" data-report-photos="'
        . report_photo_data_attribute($urls) . '" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-picture-o" aria-hidden="true"></i></button>';
}

function report_photo_gallery_markup(int $reportId, array $photos): string
{
    if ($photos === []) {
        return '';
    }

    $urls = report_photo_urls($reportId, $photos);
    $attribute = report_photo_data_attribute($urls);
    $html = '<section class="report-photo-section" aria-labelledby="report-photo-title-' . $reportId . '">';
    $html .= '<h3 id="report-photo-title-' . $reportId . '">Fotografías</h3>';
    $html .= '<div class="report-photo-thumbnails" data-report-photos="' . $attribute . '">';

    foreach ($urls as $index => $url) {
        $number = $index + 1;
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $html .= '<button type="button" data-photo-gallery data-photo-index="' . $index
            . '" aria-label="Ampliar fotografía ' . $number . ' de ' . count($urls) . '">';
        $html .= '<img src="' . $escapedUrl . '" alt="Fotografía ' . $number . ' del reporte" loading="lazy">';
        $html .= '</button>';
    }

    $html .= '</div></section>';
    return $html;
}

