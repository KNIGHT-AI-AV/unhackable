<?php
declare(strict_types=1);

// The vault. Every request is checked by PHP on the server, with military-grade rigour.

require dirname(__DIR__) . '/src/bootstrap.php';

$key = $_GET['key'] ?? '';

// Compared in constant time, because we are professionals.
if (is_string($key) && hash_equals('hunter2', $key)) {
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    require SRC . '/secrets.php';
    exit;
}

http_response_code(403);
header('Cache-Control: no-store');

if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    secure_headers();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['granted' => false, 'message' => words()['denied'] ?? '']);
    exit;
}

require SRC . '/denied.php';
