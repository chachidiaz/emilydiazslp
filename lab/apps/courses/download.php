<?php
// Serves one download to a student who has access to its course.
// downloads.file is either an https:// link (sent there) or a file name in data/files/.
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/catalog.php';

$user = require_user();
$d = q('SELECT d.*, c.slug FROM downloads d JOIN courses c ON c.id = d.course_id WHERE d.id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
$course = $d ? course_by_slug($d['slug']) : null;
if (!$d || !$course || !can_access_course($user, $course)) {
    flash('You don\'t have access to that file.');
    redirect(url(''));
}
$file = (string) $d['file'];
if (str_starts_with($file, 'https://')) {
    redirect($file);
}
$path = __DIR__ . '/data/files/' . basename($file);
if ($file === '' || !is_file($path)) {
    http_response_code(404);
    page_start('File not ready');
    auth_shell_start();
    echo '<div class="auth-box" style="text-align:center"><h1 class="serif title-md">This file isn\'t uploaded yet.</h1><p class="muted">' . e($d['label']) . ' will be here soon.</p><a class="btn btn-lg btn-block" href="javascript:history.back()">Go back</a></div>';
    auth_shell_end('');
    page_end();
    exit;
}
$name = preg_replace('/[^\w .-]+/', '', $d['label']) . '.' . strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . (mime_content_type($path) ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0');
readfile($path);
