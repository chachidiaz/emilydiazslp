<?php
// Saves lesson progress: "Mark as completed" (done=0|1) and video resume position (seconds).
// Answers JSON to fetch() calls, or redirects back to the lesson for plain form posts.
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/catalog.php';

$user = require_user();
if (!is_post()) {
    redirect(url(''));
}
check_csrf();

$lesson = q('SELECT l.*, c.slug FROM lessons l JOIN courses c ON c.id = l.course_id WHERE l.id = ?', [(int) ($_POST['lesson'] ?? 0)])->fetch();
$course = $lesson ? course_by_slug($lesson['slug']) : null;
if (!$lesson || !$course || !can_access_course($user, $course)) {
    http_response_code(403);
    exit('No access to this lesson.');
}

if (isset($_POST['done'])) {
    set_lesson_complete($user, (int) $lesson['id'], $_POST['done'] === '1');
}
if (isset($_POST['seconds'])) {
    save_position($user, (int) $lesson['id'], min((int) $_POST['seconds'], 60 * 60 * 12));
}

if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    $row = q('SELECT COUNT(*) AS total, SUM(p.completed_at IS NOT NULL) AS done FROM lessons l
              LEFT JOIN lesson_progress p ON p.lesson_id = l.id AND p.user_id = ? WHERE l.course_id = ?', [$user['id'], $course['id']])->fetch();
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'done' => (int) $row['done'], 'total' => (int) $row['total']]);
    exit;
}
redirect(lesson_url($course, (int) $lesson['position']));
