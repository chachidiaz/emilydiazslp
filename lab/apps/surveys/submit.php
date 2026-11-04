<?php
// Public endpoint survey pages post to. Takes form fields, answers JSON.

declare(strict_types=1);

require_once __DIR__ . '/lib/core.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

function sv_reply(int $code, array $body): never
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sv_reply(405, ['ok' => false, 'error' => 'Send answers with POST.']);
}

$slug = is_string($_POST['survey'] ?? null) ? $_POST['survey'] : '';
$survey = SV_SURVEYS[$slug] ?? null;
if (!$survey) {
    sv_reply(404, ['ok' => false, 'error' => 'That survey doesn\'t exist.']);
}

// Honeypot: a hidden field people never see. Bots fill it; pretend it worked.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    sv_reply(200, ['ok' => true]);
}

[$answers, $error] = sv_clean($survey, $_POST);
if ($error) {
    sv_reply(422, ['ok' => false, 'error' => $error]);
}

try {
    sv_save($slug, sv_email($_POST['email'] ?? null), $answers);
} catch (Throwable $e) {
    error_log('survey save failed: ' . $e->getMessage());
    sv_reply(500, ['ok' => false, 'error' => 'Something went wrong saving your answers. Please try again.']);
}

sv_reply(200, ['ok' => true]);
