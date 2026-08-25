<?php
declare(strict_types=1);

function db(): mysqli
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'db_registros_elevadores';
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');

    if ($user === false || $user === '' || $password === false || $password === '') {
        throw new RuntimeException('La configuración de base de datos no está disponible.');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli($host, $user, $password, $name);
    $connection->set_charset('utf8mb4');
    return $connection;
}

function report_id($value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('Identificador de reporte inválido.');
    }

    return $id;
}

function post_string(string $key, int $maxLength = 65535): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException("El campo {$key} es inválido.");
    }

    return mb_substr(trim($value), 0, $maxLength, 'UTF-8');
}

function escape_html(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
