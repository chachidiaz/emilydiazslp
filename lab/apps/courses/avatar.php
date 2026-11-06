<?php
// Profile photos live in data/avatars/ (not public). Students see their own; admins see anyone's.
require __DIR__ . '/lib/auth.php';

$user = require_user();
$id = (int) ($_GET['u'] ?? 0);
if ($id !== (int) $user['id'] && !is_admin($user)) {
    http_response_code(403);
    exit;
}
$owner = find_user($id);
$path = $owner && $owner['avatar_file'] ? __DIR__ . '/data/avatars/' . basename($owner['avatar_file']) : '';
if (!$path || !is_file($path)) {
    http_response_code(404);
    exit;
}
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
