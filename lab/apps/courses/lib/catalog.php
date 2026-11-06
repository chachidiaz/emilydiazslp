<?php
// Courses, lessons, access, progress, downloads and per-user preferences.

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ---------- Access ----------

/** Admins can open every course (to preview); students need an enrollment with access on. */
function can_access_course(array $user, array $course): bool
{
    if ($user['role'] === 'admin' && $user['email_verified_at']) {
        return true;
    }
    return (bool) q('SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ? AND has_access = 1', [$user['id'], $course['id']])->fetchColumn();
}

function course_by_slug(string $slug): ?array
{
    return q('SELECT * FROM courses WHERE slug = ? AND published = 1', [$slug])->fetch() ?: null;
}

function course_lessons(int $courseId): array
{
    return q('SELECT * FROM lessons WHERE course_id = ? ORDER BY position', [$courseId])->fetchAll();
}

// ---------- My courses ----------

/**
 * Courses the user can open, each with lesson_count, done_count and last_activity.
 * $filter: all | in-progress | completed | not-started
 */
function user_courses(array $user, string $filter = 'all'): array
{
    $admin = $user['role'] === 'admin' && $user['email_verified_at'];
    $rows = q('SELECT c.*,
            (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS lesson_count,
            (SELECT COUNT(*) FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id
               WHERE l.course_id = c.id AND p.user_id = :u1 AND p.completed_at IS NOT NULL) AS done_count,
            (SELECT MAX(p.updated_at) FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id
               WHERE l.course_id = c.id AND p.user_id = :u2) AS last_activity
        FROM courses c
        WHERE c.published = 1 AND (' . ($admin ? '1 = 1' : 'EXISTS (SELECT 1 FROM enrollments e WHERE e.course_id = c.id AND e.user_id = :u3 AND e.has_access = 1)') . ')
        ORDER BY c.sort, c.title', $admin ? ['u1' => $user['id'], 'u2' => $user['id']] : ['u1' => $user['id'], 'u2' => $user['id'], 'u3' => $user['id']])->fetchAll();

    return array_values(array_filter($rows, function ($c) use ($filter) {
        $state = course_state($c);
        return $filter === 'all' || $filter === $state;
    }));
}

function course_state(array $c): string
{
    if ((int) $c['lesson_count'] > 0 && (int) $c['done_count'] >= (int) $c['lesson_count']) {
        return 'completed';
    }
    return ($c['done_count'] > 0 || $c['last_activity']) ? 'in-progress' : 'not-started';
}

function percent(int $done, int $total): int
{
    return $total > 0 ? (int) round($done / $total * 100) : 0;
}

/** The lesson to show in "Continue learning": next unfinished lesson of the latest course. */
function continue_target(array $user, array $courses): ?array
{
    $open = array_filter($courses, fn($c) => course_state($c) !== 'completed');
    if (!$open) {
        return null;
    }
    usort($open, fn($a, $b) => strcmp((string) $b['last_activity'], (string) $a['last_activity']));
    $course = $open[0];
    $progress = progress_map($user, (int) $course['id']);
    foreach (course_lessons((int) $course['id']) as $lesson) {
        if (empty($progress[$lesson['id']]['completed_at'])) {
            return ['course' => $course, 'lesson' => $lesson, 'started' => (bool) $course['last_activity']];
        }
    }
    return null;
}

// ---------- Progress ----------

/** lesson_id => progress row for one course. */
function progress_map(array $user, int $courseId): array
{
    $rows = q('SELECT p.* FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id WHERE p.user_id = ? AND l.course_id = ?', [$user['id'], $courseId])->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[(int) $r['lesson_id']] = $r;
    }
    return $map;
}

function set_lesson_complete(array $user, int $lessonId, bool $done): void
{
    q('INSERT INTO lesson_progress (user_id, lesson_id, completed_at, updated_at) VALUES (?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE completed_at = VALUES(completed_at), updated_at = VALUES(updated_at)',
        [$user['id'], $lessonId, $done ? now_utc() : null, now_utc()]);
}

function save_position(array $user, int $lessonId, int $seconds): void
{
    q('INSERT INTO lesson_progress (user_id, lesson_id, position_seconds, updated_at) VALUES (?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE position_seconds = VALUES(position_seconds), updated_at = VALUES(updated_at)',
        [$user['id'], $lessonId, max(0, $seconds), now_utc()]);
}

function lesson_url(array $course, int $position): string
{
    return url('lesson.php') . '?c=' . rawurlencode($course['slug']) . '&l=' . $position;
}

// ---------- Downloads ----------

function lesson_downloads(int $lessonId): array
{
    return q('SELECT * FROM downloads WHERE lesson_id = ? ORDER BY sort, id', [$lessonId])->fetchAll();
}

function course_downloads(int $courseId): array
{
    return q('SELECT d.*, l.position, l.title AS lesson_title FROM downloads d LEFT JOIN lessons l ON l.id = d.lesson_id
              WHERE d.course_id = ? ORDER BY l.position IS NULL, l.position, d.sort, d.id', [$courseId])->fetchAll();
}

function download_row(array $d): string
{
    return '<a class="file" href="' . url('download.php') . '?id=' . (int) $d['id'] . '">'
        . '<span class="file-type">' . e($d['file_type']) . '</span>'
        . '<span style="flex-grow:1;min-width:0"><span class="file-name">' . e($d['label']) . '</span><span class="file-size">' . e($d['size_label']) . '</span></span>'
        . icon('download') . '</a>';
}

/** Cover art: an image if set, else the stored SVG on its background color. */
function course_cover(array $c, string $extraStyle = ''): string
{
    $inner = $c['cover_image']
        ? '<img src="' . e($c['cover_image']) . '" alt="">'
        : (string) $c['cover_art']; // trusted markup from the catalog, never from students
    return '<div class="cover" style="background:' . e($c['cover_bg'] ?: 'var(--surface-sunken)') . ';' . $extraStyle . '">' . $inner . '</div>';
}

// ---------- Preferences ----------

function pref_defaults(): array
{
    return [
        'notify_new' => true,
        'notify_weekly' => true,
        'notify_reminders' => false,
        'magic_link' => true,
        'autoplay' => false,
        'captions' => true,
        'speed' => '1',
    ];
}

function prefs(array $user): array
{
    $saved = json_decode((string) ($user['prefs'] ?? ''), true);
    return array_merge(pref_defaults(), is_array($saved) ? array_intersect_key($saved, pref_defaults()) : []);
}

function set_pref(array $user, string $key, mixed $value): void
{
    $defaults = pref_defaults();
    if (!array_key_exists($key, $defaults)) {
        return;
    }
    if ($key === 'speed') {
        $value = in_array((string) $value, ['0.75', '1', '1.25', '1.5'], true) ? (string) $value : '1';
    } else {
        $value = (bool) $value;
    }
    $p = prefs($user);
    $p[$key] = $value;
    q('UPDATE users SET prefs = ? WHERE id = ?', [json_encode($p), $user['id']]);
}
