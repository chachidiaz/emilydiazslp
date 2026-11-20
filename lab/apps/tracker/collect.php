<?php
// Public endpoint the pixel (p.js) sends events to. Always answers 204 with no body.

declare(strict_types=1);

require_once __DIR__ . '/lib/core.php';

header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(204);
    exit;
}

$raw = file_get_contents('php://input', false, null, 0, 8192);
$event = is_string($raw) ? json_decode($raw, true) : null;

if (is_array($event)) {
    try {
        pt_ingest($event, (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), time());
    } catch (Throwable $e) {
        error_log('tracker collect: ' . $e->getMessage());
    }
}

http_response_code(204);
