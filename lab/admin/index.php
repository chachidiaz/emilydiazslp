<?php
// Lab dashboard: every page, doc and artifact on lab.emilydiazslp.com, what needs attention,
// and tools to organize them. Uses the deadline timers app's login, so one password covers both.

declare(strict_types=1);

require_once __DIR__ . '/../apps/deadlines/lib/auth.php';

dl_session_start();
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: SAMEORIGIN');

const LAB_SECTIONS = [
    'landing-pages' => 'Landing pages',
    'docs' => 'Docs',
    'artifacts' => 'Artifacts',
];
const LAB_REPO = 'https://github.com/chachidiaz/emilydiazslp';
const LAB_TYPES = ['sales' => 'Sales page', 'optin' => 'Opt-in', 'other' => 'Other'];

$root = dirname(__DIR__);
$self = strtok($_SERVER['REQUEST_URI'] ?? '/admin/', '?');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    dl_check_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'login') {
        $result = dl_login((string) ($_POST['password'] ?? ''));
        if ($result !== true) {
            lab_flash(is_string($result) ? $result : 'That password didn\'t work.');
        }
    } elseif ($action === 'logout' && dl_is_admin()) {
        dl_logout();
    } elseif (dl_is_admin()) {
        header('Location: ' . $self . lab_organize($root, $action));
        exit;
    }
    header('Location: ' . $self);
    exit;
}

// A message for the next page view, shown as a toast. $undo lists trash ids that can be restored.
function lab_flash(string $text, array $undo = []): void
{
    $_SESSION['flash'] = ['text' => $text, 'undo' => $undo];
}

// ---------- storage: page types, folders, names, trash ----------

// Lives in the same SQLite file as the deadline timers.
function lab_db(): PDO
{
    $db = dl_db();
    $db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS lab_folders (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  created_at INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS lab_pages (
  url TEXT PRIMARY KEY,
  type TEXT,                                       -- sales | optin | other; NULL = unsorted
  folder_id INTEGER REFERENCES lab_folders(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS lab_trash (
  id INTEGER PRIMARY KEY,
  file TEXT NOT NULL,                              -- original path under lab/, e.g. /docs/guide/index.html
  url TEXT NOT NULL,
  title TEXT NOT NULL,
  trash_name TEXT NOT NULL,                        -- file name inside admin/trash/
  deleted_at INTEGER NOT NULL
);
SQL);
    $cols = array_column($db->query('PRAGMA table_info(lab_pages)')->fetchAll(), 'name');
    if (!in_array('name', $cols, true)) {
        $db->exec('ALTER TABLE lab_pages ADD COLUMN name TEXT'); // dashboard-only display name
    }
    if (!in_array('first_seen', $cols, true)) {
        $db->exec('ALTER TABLE lab_pages ADD COLUMN first_seen INTEGER'); // fallback "published" date
    }
    return $db;
}

function lab_trash_dir(): string
{
    return __DIR__ . '/trash';
}

// Every page the dashboard lists, keyed by URL.
function lab_all_pages(string $root): array
{
    $all = [];
    foreach (array_keys(LAB_SECTIONS) as $dir) {
        foreach (lab_pages($root, $dir) as $p) {
            $all[$p['url']] = $p;
        }
    }
    return $all;
}

function lab_set_meta(string $url, array $fields): void
{
    $db = lab_db();
    $db->prepare('INSERT INTO lab_pages (url, first_seen) VALUES (?, ?) ON CONFLICT(url) DO NOTHING')->execute([$url, time()]);
    foreach ($fields as $col => $val) {
        $db->prepare("UPDATE lab_pages SET $col = ? WHERE url = ?")->execute([$val, $url]);
    }
}

function lab_folder_exists(int $id): bool
{
    $st = lab_db()->prepare('SELECT 1 FROM lab_folders WHERE id = ?');
    $st->execute([$id]);
    return (bool) $st->fetchColumn();
}

function lab_new_folder(string $name): int
{
    lab_db()->prepare('INSERT INTO lab_folders (name, created_at) VALUES (?, ?)')->execute([substr($name, 0, 80), time()]);
    return (int) lab_db()->lastInsertId();
}

function lab_meta(string $url): ?array
{
    $st = lab_db()->prepare('SELECT * FROM lab_pages WHERE url = ?');
    $st->execute([$url]);
    return $st->fetch() ?: null;
}

// Takes a page offline by moving its file to admin/trash/. Returns the trash id, or null.
function lab_delete_page(string $root, array $p): ?int
{
    $trash = lab_trash_dir();
    $trashName = date('Ymd-His') . '-' . substr(md5($p['file'] . microtime()), 0, 6) . '-' . trim(preg_replace('/[^a-z0-9.]+/i', '-', $p['file']), '-');
    if ((!is_dir($trash) && !mkdir($trash, 0755, true)) || !rename($root . $p['file'], $trash . '/' . $trashName)) {
        return null;
    }
    // Remove the page's folder too if nothing else is left in it.
    $dir = dirname($root . $p['file']);
    if (!in_array(basename($dir), array_keys(LAB_SECTIONS), true) && count(scandir($dir)) === 2) {
        rmdir($dir);
    }
    lab_db()->prepare('INSERT INTO lab_trash (file, url, title, trash_name, deleted_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$p['file'], $p['url'], lab_page_name($p, lab_meta($p['url'])), $trashName, time()]);
    return (int) lab_db()->lastInsertId();
}

// Puts a deleted page back. Returns an error message, or null when it worked.
function lab_restore_page(string $root, int $id): ?string
{
    $db = lab_db();
    $st = $db->prepare('SELECT * FROM lab_trash WHERE id = ?');
    $st->execute([$id]);
    $t = $st->fetch();
    if (!$t) {
        return 'That page isn\'t in Recently deleted any more.';
    }
    $from = lab_trash_dir() . '/' . $t['trash_name'];
    $to = $root . $t['file'];
    if (file_exists($to)) {
        return 'A page already exists at ' . $t['url'] . ', so "' . $t['title'] . '" wasn\'t restored.';
    }
    if (!is_dir(dirname($to))) {
        mkdir(dirname($to), 0755, true);
    }
    if (!is_file($from) || !rename($from, $to)) {
        return '"' . $t['title'] . '" couldn\'t be restored.';
    }
    $db->prepare('DELETE FROM lab_trash WHERE id = ?')->execute([$id]);
    return null;
}

// Handles the dashboard's actions and returns the anchor to land back on.
function lab_organize(string $root, string $action): string
{
    $db = lab_db();
    $name = trim((string) ($_POST['name'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);
    $urls = array_values(array_filter(array_map('strval', (array) ($_POST['urls'] ?? [$_POST['url'] ?? '']))));
    $landing = array_column(lab_pages($root, 'landing-pages'), null, 'url');

    switch ($action) {
        case 'set_type':
            $type = isset(LAB_TYPES[$_POST['type'] ?? '']) ? $_POST['type'] : null;
            foreach ($urls as $url) {
                if (isset($landing[$url])) {
                    lab_set_meta($url, ['type' => $type]);
                }
            }
            lab_flash($type ? 'Type set to ' . strtolower(LAB_TYPES[$type]) . '.' : 'Type cleared.');
            return '#landing-pages';

        case 'move_pages':
            $folder = (int) ($_POST['folder'] ?? 0);
            $newFolder = trim((string) ($_POST['new_folder'] ?? ''));
            if ($newFolder !== '') {
                $folder = lab_new_folder($newFolder);
            }
            $folder = $folder && lab_folder_exists($folder) ? $folder : null;
            $moved = 0;
            foreach ($urls as $url) {
                if (isset($landing[$url])) {
                    lab_set_meta($url, ['folder_id' => $folder]);
                    $moved++;
                }
            }
            $st = $db->prepare('SELECT name FROM lab_folders WHERE id = ?');
            $st->execute([$folder]);
            $fname = $st->fetchColumn();
            lab_flash(($moved === 1 ? '1 page' : $moved . ' pages') . ' moved ' . ($fname ? 'to ' . $fname : 'out of folders') . '.');
            return '#landing-pages';

        case 'rename_page':
            $pages = lab_all_pages($root);
            $url = $urls[0] ?? '';
            if (isset($pages[$url])) {
                lab_set_meta($url, ['name' => $name !== '' && $name !== $pages[$url]['title'] ? substr($name, 0, 120) : null]);
                lab_flash($name !== '' ? 'Renamed.' : 'Back to the page\'s own title.');
            }
            return '#landing-pages';

        case 'delete_pages':
            $pages = lab_all_pages($root);
            $done = [];
            $failed = 0;
            foreach ($urls as $url) {
                if (!isset($pages[$url])) {
                    continue;
                }
                $tid = lab_delete_page($root, $pages[$url]);
                $tid ? $done[] = $tid : $failed++;
            }
            if ($failed) {
                lab_flash($failed . ' page' . ($failed === 1 ? '' : 's') . ' couldn\'t be deleted: the server didn\'t allow it.', $done);
            } elseif ($done) {
                lab_flash((count($done) === 1 ? '1 page is' : count($done) . ' pages are') . ' offline now.', $done);
            }
            return '';

        case 'restore_pages':
            $errors = [];
            $ids = array_map('intval', (array) ($_POST['ids'] ?? [$id]));
            foreach ($ids as $tid) {
                if ($e = lab_restore_page($root, $tid)) {
                    $errors[] = $e;
                }
            }
            lab_flash($errors ? implode(' ', $errors) : (count($ids) === 1 ? 'Restored. It\'s back online.' : 'Restored. They\'re back online.'));
            return '';

        case 'purge_page':
            $st = $db->prepare('SELECT * FROM lab_trash WHERE id = ?');
            $st->execute([$id]);
            if ($t = $st->fetch()) {
                $from = lab_trash_dir() . '/' . $t['trash_name'];
                if (is_file($from)) {
                    unlink($from);
                }
                $db->prepare('DELETE FROM lab_trash WHERE id = ?')->execute([$id]);
                $db->prepare('DELETE FROM lab_pages WHERE url = ?')->execute([$t['url']]);
                lab_flash('"' . $t['title'] . '" was deleted for good.');
            }
            return '#trash';

        case 'create_folder':
            if ($name !== '') {
                lab_new_folder($name);
                lab_flash('Folder "' . $name . '" created. Move pages into it from a row\'s More menu.');
            }
            return '#landing-pages';

        case 'rename_folder':
            if ($name !== '') {
                $db->prepare('UPDATE lab_folders SET name = ? WHERE id = ?')->execute([substr($name, 0, 80), $id]);
                lab_flash('Folder renamed.');
            }
            return '#landing-pages';

        case 'delete_folder':
            $db->prepare('UPDATE lab_pages SET folder_id = NULL WHERE folder_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM lab_folders WHERE id = ?')->execute([$id]);
            lab_flash('Folder deleted. Its pages are now in "Not in a folder".');
            return '#landing-pages';
    }
    return '';
}

// ---------- scanning ----------

// Every HTML page under a section folder, skipping asset folders. A folder's index.html
// is listed under the folder's URL.
function lab_pages(string $root, string $section): array
{
    $dir = $root . '/' . $section;
    if (!is_dir($dir)) {
        return [];
    }
    $pages = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            fn ($f) => !($f->isDir() && in_array($f->getFilename(), ['assets', 'img', 'images', 'fonts', 'data', 'lib'], true))
        )
    );
    foreach ($it as $file) {
        if (!preg_match('/\.html?$/i', $file->getFilename())) {
            continue;
        }
        $rel = substr($file->getPathname(), strlen($root));
        $pages[] = lab_page_info($root, $rel) + ['section' => $section];
    }
    usort($pages, fn ($a, $b) => strcmp($a['id'] ?: 'ZZZ' . $a['url'], $b['id'] ?: 'ZZZ' . $b['url']));
    return $pages;
}

function lab_page_info(string $root, string $rel): array
{
    $path = $root . $rel;
    $html = (string) file_get_contents($path);
    $url = preg_replace('#/index\.html?$#i', '/', $rel);
    preg_match('#<title>(.*?)</title>#is', $html, $m);
    $title = trim(html_entity_decode($m[1] ?? '', ENT_QUOTES)) ?: basename($rel);

    // Local files the page asks for that aren't on the server.
    $missing = [];
    preg_match_all('~(?:src|href)=["\']([^"\'#?]+)["\']|url\(\s*["\']?([^"\')?#]+)~i', $html, $refs, PREG_SET_ORDER);
    foreach ($refs as $r) {
        $ref = $r[1] !== '' ? $r[1] : ($r[2] ?? '');
        if ($ref === '' || preg_match('#^(https?:|//|data:|mailto:|tel:|javascript:|\{\{)#i', $ref) || !preg_match('#\.(png|jpe?g|webp|gif|svg|otf|ttf|woff2?|css|js|mp4)$#i', $ref)) {
            continue;
        }
        $target = $ref[0] === '/' ? $root . $ref : dirname($path) . '/' . $ref;
        if (!file_exists($target)) {
            $missing[$ref] = true;
        }
    }

    preg_match('#<meta[^>]+name=["\']lab-id["\'][^>]+content=["\']([A-Z]+-\d+)["\']#i', $html, $idm);
    preg_match('#<meta[^>]+name=["\']lab-published["\'][^>]+content=["\'](\d{4}-\d{2}-\d{2})["\']#i', $html, $pub);

    return [
        'id' => strtoupper($idm[1] ?? ''),
        'title' => $title,
        'url' => $url,
        'file' => $rel,
        'updated' => filemtime($path),
        'published' => isset($pub[1]) ? strtotime($pub[1] . ' 12:00:00') : null,
        'noindex' => (bool) preg_match('#<meta[^>]+name=["\']robots["\'][^>]+noindex#i', $html),
        'countdown' => str_contains($html, 'role="timer"'),
        'deadline_app' => str_contains($html, 'data-deadline-timer') || str_contains($html, 'data-deadline-campaign'),
        'missing' => array_keys($missing),
    ];
}

function lab_page_name(array $p, ?array $meta): string
{
    return ($meta['name'] ?? '') !== '' ? $meta['name'] : $p['title'];
}

function ago(int $ts): string
{
    $d = time() - $ts;
    if ($d < 3600) return max(1, intdiv($d, 60)) . ' min ago';
    if ($d < 86400) return intdiv($d, 3600) . ' hr ago';
    if ($d < 86400 * 30) return intdiv($d, 86400) . ' day' . (intdiv($d, 86400) === 1 ? '' : 's') . ' ago';
    return date('M j, Y', $ts);
}

// Inline Lucide outline icons.
function icon(string $name, int $size = 20): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'pages' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
        'docs' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v4a2 2 0 0 0 2 2h4M10 9H8M16 13H8M16 17H8"/>',
        'artifacts' => '<path d="M9.9 2.6 8 8l-5.4 1.9a.5.5 0 0 0 0 .9L8 12.7l1.9 5.4a.5.5 0 0 0 .9 0l1.9-5.4 5.4-1.9a.5.5 0 0 0 0-.9L12.7 8l-1.9-5.4a.5.5 0 0 0-.9 0z"/><path d="M20 3v4M22 5h-4"/>',
        'timer' => '<path d="M10 2h4M12 14l3-3"/><circle cx="12" cy="14" r="8"/>',
        'palette' => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2a10 10 0 1 0 0 20 1.7 1.7 0 0 0 1.7-1.7c0-.4-.2-.8-.4-1.1-.3-.3-.4-.7-.4-1.1a1.7 1.7 0 0 1 1.7-1.7h2a5.6 5.6 0 0 0 5.6-5.6C22 6 17.5 2 12 2z"/>',
        'external' => '<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
        'image-off' => '<path d="M2 2l20 20M10.4 3H19a2 2 0 0 1 2 2v8.6M21 15.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5c0-.6.2-1.1.6-1.4M13.5 13.5 6 21M18 12l-1-1"/>',
        'folder' => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9l-.8-1.2A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/>',
        'folder-plus' => '<path d="M12 10v6M9 13h6M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9l-.8-1.2A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'copy' => '<rect x="8" y="8" width="14" height="14" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'sparkle' => '<path d="M9.9 2.6 8 8l-5.4 1.9a.5.5 0 0 0 0 .9L8 12.7l1.9 5.4a.5.5 0 0 0 .9 0l1.9-5.4 5.4-1.9a.5.5 0 0 0 0-.9L12.7 8l-1.9-5.4a.5.5 0 0 0-.9 0z"/>',
        'pencil' => '<path d="M21.2 6.8a2.8 2.8 0 0 0-4-4L4 16l-1 5 5-1z"/>',
        'tag' => '<path d="M12.6 2.6A2 2 0 0 0 11.2 2H4a2 2 0 0 0-2 2v7.2a2 2 0 0 0 .6 1.4l8.7 8.7a2.4 2.4 0 0 0 3.4 0l6.6-6.6a2.4 2.4 0 0 0 0-3.4z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
        'code' => '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>',
        'trash' => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'eye-off' => '<path d="M10.7 5.1A10.7 10.7 0 0 1 22 12a14 14 0 0 1-2.2 3.2M14.1 14.1a3 3 0 1 1-4.2-4.2M17.5 17.5A10.7 10.7 0 0 1 2 12a14 14 0 0 1 4.5-5.4M2 2l20 20"/>',
        'undo' => '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/>',
        'party' => '<path d="M5.8 11.3 2 22l10.7-3.8M4 3h.01M22 8h.01M15 2h.01M22 20h.01M22 2l-2.2.8a2.9 2.9 0 0 0-1.9 3.3c.1.9-.6 1.7-1.5 1.7h-.4c-.9 0-1.6.8-1.5 1.7"/><path d="M9.8 9.8 14 14c1.8 1.8 2.4 4 1.4 5s-3.2.4-5-1.4L6 13.4c-1.8-1.8-2.4-4-1.4-5s3.2-.4 5 1.4"/>',
    ];
    if ($name === 'dots') {
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>';
    }
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

// ---------- views ----------

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$csrf = dl_csrf();

function csrf_field(): string
{
    global $csrf;
    return '<input type="hidden" name="csrf" value="' . dl_h($csrf) . '">';
}

function page_head(string $title): void
{
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= dl_h($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700&amp;display=swap">
<link rel="stylesheet" href="admin.css">
</head>
<?php
}

function toast_markup(): void
{
    global $flash;
    ?>
<div class="toast" id="toast" role="status" aria-live="polite"<?= $flash ? '' : ' hidden' ?>>
  <span class="toast-text"><?= $flash ? dl_h($flash['text']) : '' ?></span>
  <?php if ($flash && $flash['undo']): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="restore_pages"><?php foreach ($flash['undo'] as $tid): ?><input type="hidden" name="ids[]" value="<?= (int) $tid ?>"><?php endforeach; ?><button class="toast-btn"><?= icon('undo', 16) ?>Undo</button></form>
  <?php endif; ?>
  <button type="button" class="toast-close" aria-label="Dismiss"><?= icon('x', 16) ?></button>
</div>
<?php
}

if (!dl_has_password()) {
    header('Location: /apps/deadlines/');
    exit;
}

// --- login ---

if (!dl_is_admin()) {
    page_head('Log in · Lab admin');
    ?>
<body class="login-body">
<main class="login">
  <form method="post" class="card login-card">
    <span class="monogram monogram-lg" aria-hidden="true">e</span>
    <h1 class="h-page">Lab admin</h1>
    <p class="muted">Same password as your deadline timers.</p>
    <?= csrf_field() ?>
    <label class="field">
      <span class="field-label">Password</span>
      <span class="input"><input type="password" name="password" required autofocus autocomplete="current-password"></span>
    </label>
    <button class="btn btn-primary" name="action" value="login">Log in</button>
  </form>
</main>
<?php toast_markup(); ?>
<script>
  (function () {
    var t = document.getElementById('toast');
    t.querySelector('.toast-close').addEventListener('click', function () { t.hidden = true; });
  })();
</script>
</body>
</html>
<?php
    exit;
}

// --- data ---

$db = lab_db();
$now = time();
$sections = [];
foreach (LAB_SECTIONS as $dir => $title) {
    $sections[$dir] = lab_pages($root, $dir);
}
$folders = $db->query('SELECT * FROM lab_folders ORDER BY name COLLATE NOCASE')->fetchAll();
$folderNames = array_column($folders, 'name', 'id');
$meta = array_column($db->query('SELECT * FROM lab_pages')->fetchAll(), null, 'url');

// Remember when each page was first seen, as a fallback "published" date.
foreach ($sections as $list) {
    foreach ($list as $p) {
        if (!isset($meta[$p['url']]['first_seen'])) {
            lab_set_meta($p['url'], []);
            $db->prepare('UPDATE lab_pages SET first_seen = ? WHERE url = ? AND first_seen IS NULL')->execute([$now, $p['url']]);
            $meta[$p['url']] = ($meta[$p['url']] ?? []) + ['first_seen' => $now];
        }
    }
}

// Attention: pages with missing files, and live timer campaigns with no views in a week.
$attention = [];
foreach ($sections as $list) {
    foreach ($list as $p) {
        if (!$p['missing']) {
            continue;
        }
        $name = lab_page_name($p, $meta[$p['url']] ?? null);
        $images = array_filter($p['missing'], fn ($f) => preg_match('/\.(png|jpe?g|webp|gif|svg)$/i', $f));
        $fonts = array_filter($p['missing'], fn ($f) => preg_match('/\.(otf|ttf|woff2?)$/i', $f));
        $what = count($p['missing']) > 1 ? count($p['missing']) . ' files'
            : ($images ? 'an image' : ($fonts ? 'a font' : 'a file'));
        $attention[] = [
            'kind' => 'missing',
            'title' => ($p['id'] ?: $name) . ' is missing ' . $what,
            'detail' => $name . ' can\'t find ' . implode(', ', $p['missing']) . ', so ' . (count($p['missing']) > 1 ? 'those spots show' : 'that spot shows') . ' blank.',
            'pill' => 'Fix needed',
            'href' => $p['url'],
            'button' => 'View page',
        ];
    }
}
$quiet = $db->query('SELECT c.id, c.name,
        (SELECT COUNT(*) FROM hits h JOIN visitors v ON v.id = h.visitor_id WHERE v.campaign_id = c.id AND h.at > ' . ($now - 7 * 86400) . ') AS recent,
        (SELECT COUNT(*) FROM hits h JOIN visitors v ON v.id = h.visitor_id WHERE v.campaign_id = c.id) AS total
    FROM campaigns c
    WHERE c.archived = 0 AND NOT (c.mode = \'fixed\' AND c.fixed_deadline IS NOT NULL AND c.fixed_deadline <= ' . $now . ')')->fetchAll();
foreach ($quiet as $c) {
    if ((int) $c['recent'] > 0) {
        continue;
    }
    $attention[] = [
        'kind' => 'timer',
        'title' => (int) $c['total'] ? 'Your timer campaign had no views this week' : 'Your timer campaign hasn\'t had a view yet',
        'detail' => $c['name'] . ' is live, with no views in the last 7 days.',
        'pill' => 'Heads up',
        'href' => '/apps/deadlines/?p=campaign&id=' . (int) $c['id'],
        'button' => 'View campaign',
    ];
}

$trash = $db->query('SELECT * FROM lab_trash ORDER BY deleted_at DESC')->fetchAll();

// Rows, with everything the client-side filters and menus need.
function page_row(array $p, array $meta, array $folderNames, bool $landing): void
{
    $m = $meta[$p['url']] ?? null;
    $name = lab_page_name($p, $m);
    $type = $m['type'] ?? '';
    $folder = (int) ($m['folder_id'] ?? 0);
    $published = $p['published'] ?: ($m['first_seen'] ?? null);
    $abs = 'https://lab.emilydiazslp.com' . $p['url'];
    $label = $p['id'] ?: $name;
    $bits = array_filter([$p['id'], basename($p['file']), $p['deadline_app'] ? 'Deadline timer' : ($p['countdown'] ? 'Countdown' : '')]);
    ?>
    <tr id="row-<?= md5($p['url']) ?>"
        data-url="<?= dl_h($p['url']) ?>" data-id="<?= dl_h($p['id']) ?>" data-name="<?= dl_h($name) ?>" data-title="<?= dl_h($p['title']) ?>"
        data-file="lab<?= dl_h($p['file']) ?>" data-type="<?= dl_h($type) ?>" data-folder="<?= $folder ?>"
        data-find="<?= dl_h(strtolower($p['id'] . ' ' . $name . ' ' . $p['title'] . ' ' . $p['url'])) ?>">
      <td class="col-check"><input type="checkbox" class="check" aria-label="Select <?= dl_h($label) ?>"></td>
      <td class="col-page">
        <a href="<?= dl_h($p['url']) ?>" target="_blank" rel="noopener" class="page-name"><?= dl_h($name) ?></a>
        <span class="cap muted"><?= dl_h(implode(' · ', $bits)) ?></span>
      </td>
      <td>
        <span class="pills">
          <?php if ($p['noindex']): ?><span class="pill pill-hidden" title="Hidden from search engines"><span class="dot"></span>Hidden</span>
          <?php else: ?><span class="pill pill-live" title="Search engines can find it"><span class="dot"></span>Public</span><?php endif; ?>
          <?php if ($p['missing']): ?><span class="pill pill-alert" title="<?= dl_h(implode("\n", $p['missing'])) ?>"><?= count($p['missing']) ?> missing file<?= count($p['missing']) === 1 ? '' : 's' ?></span><?php endif; ?>
        </span>
      </td>
      <?php if ($landing): ?>
        <td class="hide-sm"><?php if ($folder && isset($folderNames[$folder])): ?><span class="folder-cell"><?= icon('folder', 16) ?><?= dl_h($folderNames[$folder]) ?></span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
      <?php endif; ?>
      <td class="hide-sm muted nowrap"><?= $published ? dl_h(date('M j, Y', (int) $published)) : '—' ?></td>
      <td class="hide-sm muted nowrap" title="<?= dl_h(date('M j, Y · g:i a', $p['updated'])) ?>"><?= dl_h(ago($p['updated'])) ?></td>
      <td class="col-actions">
        <div class="actions">
          <button type="button" class="btn btn-secondary btn-sm" data-copy-url="<?= dl_h($abs) ?>" title="<?= dl_h($abs) ?>" aria-label="Copy full URL for <?= dl_h($label) ?>: <?= dl_h($abs) ?>"><?= icon('copy', 14) ?><span class="btn-text">Copy URL</span></button>
          <div class="row-actions">
            <a href="<?= dl_h($p['url']) ?>" target="_blank" rel="noopener" class="btn btn-ghost icon-btn hide-sm" aria-label="Open <?= dl_h($label) ?>" title="Open"><?= icon('external', 18) ?></a>
            <button type="button" class="btn btn-ghost icon-btn" data-more aria-haspopup="menu" aria-expanded="false" aria-label="More actions for <?= dl_h($label) ?>" title="More"><?= icon('dots', 18) ?></button>
          </div>
        </div>
      </td>
    </tr>
    <?php
}

function table_section(string $dir, string $title, array $pages, array $meta, array $folderNames, array $folders): void
{
    $landing = $dir === 'landing-pages';
    $count = count($pages);
    $types = ['sales' => 0, 'optin' => 0, 'other' => 0, '' => 0];
    $inFolder = [];
    foreach ($pages as $p) {
        $types[$meta[$p['url']]['type'] ?? '']++;
        $f = (int) ($meta[$p['url']]['folder_id'] ?? 0);
        $inFolder[$f] = ($inFolder[$f] ?? 0) + 1;
    }
    if ($landing && count($inFolder) === 1 && !isset($inFolder[0])) {
        $sub = $count . ' in ' . ($folderNames[array_key_first($inFolder)] ?? 'a folder');
    } else {
        $sub = $count . ' page' . ($count === 1 ? '' : 's');
    }
    ?>
<section id="<?= $dir ?>" class="card dt" aria-labelledby="<?= $dir ?>-h" data-table>
  <div class="dt-head">
    <div class="dt-title"><h2 id="<?= $dir ?>-h" class="h-card"><?= dl_h($title) ?></h2><span class="muted small"><?= dl_h($sub) ?></span></div>
    <div class="dt-search">
      <label class="input input-sm"><?= icon('search', 16) ?><span class="lbl">Filter <?= dl_h(strtolower($title)) ?></span><input type="search" placeholder="Filter by name or ID" data-filter-search></label>
    </div>
    <?php if ($landing): ?>
      <div class="dt-tools">
        <button type="button" class="btn btn-ghost" data-open="dlg-new-folder"><?= icon('folder-plus', 16) ?>New folder</button>
        <button type="button" class="btn btn-secondary" data-open="dlg-folders"><?= icon('folder', 16) ?>View folders</button>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($landing): ?>
    <div class="filters">
      <div role="group" aria-label="Page type" class="chips">
        <button type="button" class="chip" aria-pressed="true" data-type-filter="all">All <span class="n"><?= $count ?></span></button>
        <button type="button" class="chip" aria-pressed="false" data-type-filter="sales">Sales pages <span class="n"><?= $types['sales'] ?></span></button>
        <button type="button" class="chip" aria-pressed="false" data-type-filter="optin">Opt-ins <span class="n"><?= $types['optin'] ?></span></button>
        <?php if ($types['other']): ?><button type="button" class="chip" aria-pressed="false" data-type-filter="other">Other <span class="n"><?= $types['other'] ?></span></button><?php endif; ?>
        <?php if ($types[''] && $types[''] < $count): ?><button type="button" class="chip" aria-pressed="false" data-type-filter="">Unsorted <span class="n"><?= $types[''] ?></span></button><?php endif; ?>
      </div>
      <span class="divider" aria-hidden="true"></span>
      <label class="chip chip-select">
        <?= icon('folder', 14) ?>
        <span class="lbl">Filter by folder</span>
        <select data-folder-filter>
          <option value="all">All folders</option>
          <?php foreach ($folders as $f): ?><option value="<?= (int) $f['id'] ?>"><?= dl_h($f['name']) ?> (<?= (int) ($inFolder[(int) $f['id']] ?? 0) ?>)</option><?php endforeach; ?>
          <option value="0">Not in a folder (<?= (int) ($inFolder[0] ?? 0) ?>)</option>
        </select>
        <span class="chip-caret"><?= icon('chevron-down', 14) ?></span>
      </label>
    </div>
  <?php endif; ?>

  <div class="bulk" role="region" aria-label="Bulk actions" hidden>
    <span data-bulk-count>0 selected</span>
    <button type="button" class="btn btn-ghost btn-xs" data-bulk-clear>Clear</button>
    <span class="spacer"></span>
    <?php if ($landing): ?><button type="button" class="btn btn-ghost btn-xs" data-bulk="move"><?= icon('folder', 16) ?>Move to folder</button><?php endif; ?>
    <button type="button" class="btn btn-ghost btn-xs" data-bulk="hide"><?= icon('eye-off', 16) ?>Hide from search</button>
    <button type="button" class="btn btn-ghost btn-xs" data-bulk="delete"><?= icon('trash', 16) ?>Delete…</button>
  </div>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr>
        <th class="col-check"><input type="checkbox" class="check" data-check-all aria-label="Select all <?= dl_h(strtolower($title)) ?>"></th>
        <th>Page</th><th>Status</th><?php if ($landing): ?><th class="hide-sm">Folder</th><?php endif; ?><th class="hide-sm">Published</th><th class="hide-sm">Updated</th><th class="col-actions"><span class="lbl">Actions</span></th>
      </tr></thead>
      <tbody>
        <?php foreach ($pages as $p) page_row($p, $meta, $folderNames, $landing); ?>
      </tbody>
    </table>
    <p class="dt-empty muted" hidden>No pages match. IDs look like LP-001, DOC-001 or ART-001.</p>
  </div>
  <div class="dt-foot">
    <span class="muted small" data-showing></span>
    <div class="pager">
      <button type="button" class="btn btn-secondary icon-btn icon-btn-sm" data-prev aria-label="Previous page"><?= icon('chevron-left', 16) ?></button>
      <button type="button" class="btn btn-secondary icon-btn icon-btn-sm" data-next aria-label="Next page"><?= icon('chevron-right', 16) ?></button>
    </div>
  </div>
</section>
<?php
}

function empty_section(string $dir, string $title): void
{
    $folder = 'lab/' . $dir;
    ?>
<section id="<?= $dir ?>" class="card empty-card" aria-labelledby="<?= $dir ?>-h">
  <h2 id="<?= $dir ?>-h" class="h-card"><?= dl_h($title) ?></h2>
  <p class="muted">No <?= dl_h(strtolower($title)) ?> yet. Add a page to <?= dl_h($folder) ?>/ and it shows up here.</p>
  <a href="<?= LAB_REPO ?>/tree/main/<?= dl_h($folder) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-flush">Open <?= dl_h($folder) ?></a>
</section>
<?php
}

// Data for the command palette and search: every page plus places to go.
$palette = [];
foreach ($sections as $dir => $list) {
    foreach ($list as $p) {
        $palette[] = ['kind' => 'page', 'id' => $p['id'], 'name' => lab_page_name($p, $meta[$p['url']] ?? null), 'url' => $p['url'], 'row' => 'row-' . md5($p['url'])];
    }
}
foreach ([['Landing pages', '#landing-pages'], ['Docs', '#docs'], ['Artifacts', '#artifacts'], ['Needs your attention', '#attention'],
          ['Deadline timers', '/apps/deadlines/'], ['Brand & tools', '/brand/thumbnail.html'], ['Lab home', '/']] as [$label, $href]) {
    $palette[] = ['kind' => 'place', 'name' => $label, 'url' => $href];
}

page_head('Lab admin');
?>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="app">

<aside class="side" aria-label="Main navigation">
  <a href="/admin/" class="brand" title="Lab admin"><span class="monogram" aria-hidden="true">e</span><span class="lbl">Lab admin</span></a>
  <nav class="nav">
    <a href="/admin/" aria-current="page" title="Dashboard"><?= icon('dashboard') ?><span class="lbl">Dashboard</span></a>
    <a href="#landing-pages" title="Landing pages"><?= icon('pages') ?><span class="lbl">Landing pages</span></a>
    <a href="#docs" title="Docs"><?= icon('docs') ?><span class="lbl">Docs</span></a>
    <a href="#artifacts" title="Artifacts"><?= icon('artifacts') ?><span class="lbl">Artifacts</span></a>
    <a href="/apps/deadlines/" title="Deadline timers"><?= icon('timer') ?><span class="lbl">Deadline timers</span></a>
    <a href="/brand/thumbnail.html" title="Brand &amp; tools"><?= icon('palette') ?><span class="lbl">Brand &amp; tools</span></a>
  </nav>
  <div class="nav nav-bottom">
    <a href="/" target="_blank" rel="noopener" title="Lab home"><?= icon('external') ?><span class="lbl">Lab home</span></a>
    <form method="post"><?= csrf_field() ?><button name="action" value="logout" title="Log out"><?= icon('logout') ?><span class="lbl">Log out</span></button></form>
  </div>
</aside>

<div class="main-col">
  <header class="topbar">
    <label class="input topsearch">
      <?= icon('search', 18) ?>
      <span class="lbl">Search pages, docs and tools</span>
      <input type="search" id="global-search" placeholder="Search pages, e.g. LP-002" autocomplete="off">
      <kbd class="hide-sm">/</kbd><kbd class="hide-sm" data-mod-key>⌘K</kbd>
    </label>
    <div class="top-right">
      <a href="#attention" class="btn btn-ghost icon-btn bell" aria-label="Notifications, <?= count($attention) ?> unread"><?= icon('bell') ?><?php if ($attention): ?><span class="bell-dot"></span><?php endif; ?></a>
      <div class="account">
        <button type="button" class="avatar" aria-label="Account: Emily Diaz" aria-haspopup="menu" aria-expanded="false" data-account>e</button>
        <div class="menu account-menu" role="menu" hidden>
          <a role="menuitem" href="/apps/deadlines/?p=settings"><?= icon('pencil', 16) ?>Change password</a>
          <form method="post"><?= csrf_field() ?><button role="menuitem" name="action" value="logout"><?= icon('logout', 16) ?>Log out</button></form>
        </div>
      </div>
    </div>
  </header>

  <main class="content" id="main">
    <div class="page-head">
      <h1 class="h-page">Dashboard</h1>
      <p class="muted">What needs you today, then everything else on lab.emilydiazslp.com.</p>
    </div>

    <section class="card attn" id="attention" aria-labelledby="attn-h" tabindex="-1">
      <div class="attn-head">
        <h2 id="attn-h" class="h-card">Needs your attention today</h2>
        <?php if ($attention): ?><span class="pill pill-alert"><?= count($attention) ?> item<?= count($attention) === 1 ? '' : 's' ?></span><?php endif; ?>
      </div>
      <?php if (!$attention): ?>
        <div class="attn-row">
          <span class="attn-icon attn-ok"><?= icon('check') ?></span>
          <div class="attn-text"><span class="strong">You're all caught up.</span><span class="muted small">Nothing is missing and every live timer has had views this week.</span></div>
        </div>
      <?php endif; ?>
      <?php foreach ($attention as $a): ?>
        <div class="attn-row">
          <span class="attn-icon <?= $a['kind'] === 'missing' ? 'attn-alert' : '' ?>"><?= icon($a['kind'] === 'missing' ? 'image-off' : 'timer') ?></span>
          <div class="attn-text"><span class="strong"><?= dl_h($a['title']) ?></span><span class="muted small"><?= dl_h($a['detail']) ?></span></div>
          <span class="pill <?= $a['kind'] === 'missing' ? 'pill-alert' : 'pill-hidden' ?>"><span class="dot"></span><?= dl_h($a['pill']) ?></span>
          <a href="<?= dl_h($a['href']) ?>" class="btn btn-secondary"<?= $a['kind'] === 'missing' ? ' target="_blank" rel="noopener"' : '' ?>><?= dl_h($a['button']) ?></a>
        </div>
      <?php endforeach; ?>
    </section>

    <?php
    if ($sections['landing-pages']) {
        table_section('landing-pages', 'Landing pages', $sections['landing-pages'], $meta, $folderNames, $folders);
    } else {
        empty_section('landing-pages', 'Landing pages');
    }
    $restEmpty = !$sections['docs'] && !$sections['artifacts'];
    if ($restEmpty): ?>
      <div class="pair"><?php empty_section('docs', 'Docs'); empty_section('artifacts', 'Artifacts'); ?></div>
    <?php else:
        foreach (['docs' => 'Docs', 'artifacts' => 'Artifacts'] as $dir => $title) {
            $sections[$dir] ? table_section($dir, $title, $sections[$dir], $meta, $folderNames, $folders) : empty_section($dir, $title);
        }
    endif; ?>

    <?php if ($trash): ?>
      <section class="card dt" id="trash" aria-labelledby="trash-h">
        <div class="dt-head"><div class="dt-title"><h2 id="trash-h" class="h-card">Recently deleted</h2><span class="muted small">Offline. Restore puts a page back at the same address.</span></div></div>
        <div class="tbl-wrap">
          <table class="tbl">
            <thead><tr><th class="col-pad">Page</th><th class="hide-sm">Address</th><th class="hide-sm">Deleted</th><th class="col-actions"><span class="lbl">Actions</span></th></tr></thead>
            <tbody>
              <?php foreach ($trash as $t): ?>
                <tr>
                  <td class="col-pad"><span class="page-name"><?= dl_h($t['title']) ?></span></td>
                  <td class="hide-sm muted"><?= dl_h($t['url']) ?></td>
                  <td class="hide-sm muted nowrap" title="<?= dl_h(date('M j, Y · g:i a', (int) $t['deleted_at'])) ?>"><?= dl_h(ago((int) $t['deleted_at'])) ?></td>
                  <td class="col-actions">
                    <div class="actions">
                      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-secondary btn-sm" name="action" value="restore_pages"><?= icon('undo', 14) ?>Restore</button></form>
                      <button type="button" class="btn btn-ghost btn-sm" data-purge="<?= (int) $t['id'] ?>" data-name="<?= dl_h($t['title']) ?>">Delete for good</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="muted small dt-note">Deleted pages are still in GitHub. When you delete one for good, ask Claude to remove it from the repo too.</p>
      </section>
    <?php endif; ?>
  </main>
</div>
</div>

<!-- Row "More" menu, shared by every row -->
<div class="menu row-menu" id="row-menu" role="menu" hidden>
  <button role="menuitem" data-act="claude"><?= icon('sparkle', 16) ?>Edit with Claude…</button>
  <button role="menuitem" data-act="copy-id"><?= icon('copy', 16) ?>Copy ID</button>
  <button role="menuitem" data-act="rename"><?= icon('pencil', 16) ?>Rename…</button>
  <div class="menu-group" data-landing-only>
    <span class="menu-label"><?= icon('tag', 14) ?>Change type</span>
    <?php foreach (['' => 'Unsorted'] + LAB_TYPES as $k => $label): ?>
      <button role="menuitemradio" class="menu-sub" data-act="type" data-type="<?= $k ?>" aria-checked="false"><span class="menu-check"><?= icon('check', 14) ?></span><?= dl_h($label) ?></button>
    <?php endforeach; ?>
  </div>
  <button role="menuitem" data-act="move" data-landing-only><?= icon('folder', 16) ?>Move to folder…</button>
  <a role="menuitem" data-act="source" target="_blank" rel="noopener"><?= icon('code', 16) ?>View source</a>
  <span class="menu-sep" aria-hidden="true"></span>
  <button role="menuitem" class="menu-danger" data-act="delete"><?= icon('trash', 16) ?>Delete…</button>
</div>

<!-- Hidden forms the menus and dialogs submit -->
<form method="post" id="form-type" hidden><?= csrf_field() ?><input type="hidden" name="action" value="set_type"><input type="hidden" name="url"><input type="hidden" name="type"></form>

<dialog class="modal" id="dlg-claude" aria-labelledby="dlg-claude-h">
  <form method="dialog" class="modal-body">
    <h2 class="h-card" id="dlg-claude-h">Edit with Claude</h2>
    <p class="muted small" data-claude-sub></p>
    <label class="field"><span class="field-label">What would you like to change?</span>
      <textarea class="textarea" rows="4" data-claude-ask placeholder="e.g. change the headline to “Your 30-minute bonus”"></textarea>
    </label>
    <p class="muted small">Copies the page's ID and your request. Paste it into a new Claude Code session in the desktop app.</p>
    <div class="modal-actions">
      <button value="cancel" class="btn btn-ghost">Cancel</button>
      <button type="button" class="btn btn-primary" data-claude-copy><?= icon('copy', 16) ?>Copy for Claude</button>
    </div>
  </form>
</dialog>

<dialog class="modal" id="dlg-rename" aria-labelledby="dlg-rename-h">
  <form method="post" class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="action" value="rename_page"><input type="hidden" name="url">
    <h2 class="h-card" id="dlg-rename-h">Rename</h2>
    <label class="field"><span class="field-label">Name on the dashboard</span><span class="input"><input name="name" maxlength="120"></span></label>
    <p class="muted small">Only changes the name here. The page's address and browser-tab title stay the same. Leave it blank to use the page's own title<span data-rename-title></span>.</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button class="btn btn-primary">Save name</button>
    </div>
  </form>
</dialog>

<dialog class="modal" id="dlg-move" aria-labelledby="dlg-move-h">
  <form method="post" class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="action" value="move_pages"><div data-urls></div>
    <h2 class="h-card" id="dlg-move-h">Move to folder</h2>
    <p class="muted small" data-move-sub></p>
    <label class="field"><span class="field-label">Folder</span>
      <span class="input"><select name="folder" data-move-folder>
        <option value="0">Not in a folder</option>
        <?php foreach ($folders as $f): ?><option value="<?= (int) $f['id'] ?>"><?= dl_h($f['name']) ?></option><?php endforeach; ?>
        <option value="new">+ New folder…</option>
      </select></span>
    </label>
    <label class="field" data-move-new hidden><span class="field-label">New folder name</span><span class="input"><input name="new_folder" maxlength="80" placeholder="e.g. AAC Hub fall launch"></span></label>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button class="btn btn-primary">Move</button>
    </div>
  </form>
</dialog>

<dialog class="modal" id="dlg-delete" aria-labelledby="dlg-delete-h">
  <form method="post" class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="action" value="delete_pages"><div data-urls></div>
    <h2 class="h-card" id="dlg-delete-h" data-delete-title>Delete?</h2>
    <ul class="delete-list small" data-delete-list></ul>
    <p class="muted small">It goes offline right away. You can undo, or restore it later from Recently deleted.</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button class="btn btn-danger">Delete</button>
    </div>
  </form>
</dialog>

<dialog class="modal" id="dlg-purge" aria-labelledby="dlg-purge-h">
  <form method="post" class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="action" value="purge_page"><input type="hidden" name="id">
    <h2 class="h-card" id="dlg-purge-h" data-purge-title>Delete for good?</h2>
    <p class="muted small">This can't be undone.</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button class="btn btn-danger">Delete for good</button>
    </div>
  </form>
</dialog>

<dialog class="modal" id="dlg-new-folder" aria-labelledby="dlg-new-folder-h">
  <form method="post" class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="action" value="create_folder">
    <h2 class="h-card" id="dlg-new-folder-h">New folder</h2>
    <label class="field"><span class="field-label">Folder name</span><span class="input"><input name="name" required maxlength="80" placeholder="e.g. AAC Hub fall launch"></span></label>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button class="btn btn-primary">Create folder</button>
    </div>
  </form>
</dialog>

<dialog class="modal" id="dlg-folders" aria-labelledby="dlg-folders-h">
  <div class="modal-body">
    <h2 class="h-card" id="dlg-folders-h">Folders</h2>
    <?php if (!$folders): ?>
      <p class="muted">No folders yet.</p>
    <?php else: ?>
      <ul class="folder-list">
        <?php foreach ($folders as $f):
            $n = count(array_filter($meta, fn ($m) => (int) ($m['folder_id'] ?? 0) === (int) $f['id'] && isset(array_column($sections['landing-pages'], null, 'url')[$m['url']]))); ?>
          <li>
            <form method="post" class="folder-rename">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
              <span class="folder-ico"><?= icon('folder', 16) ?></span>
              <span class="input input-sm"><span class="lbl">Folder name</span><input name="name" value="<?= dl_h($f['name']) ?>" required maxlength="80"></span>
              <span class="muted small nowrap"><?= $n ?> page<?= $n === 1 ? '' : 's' ?></span>
              <button class="btn btn-secondary btn-sm" name="action" value="rename_folder">Rename</button>
              <button class="btn btn-ghost btn-sm" name="action" value="delete_folder" data-confirm-folder="<?= dl_h($f['name']) ?>">Delete</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="muted small">Deleting a folder keeps its pages. They move to "Not in a folder".</p>
    <?php endif; ?>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-close>Close</button>
      <button type="button" class="btn btn-primary" data-open="dlg-new-folder"><?= icon('folder-plus', 16) ?>New folder</button>
    </div>
  </div>
</dialog>

<dialog class="modal palette" id="dlg-palette" aria-label="Jump to a page or section">
  <div class="modal-body">
    <label class="input"><?= icon('search', 18) ?><span class="lbl">Jump to</span><input type="search" data-palette-input placeholder="Type a page ID, name or section" autocomplete="off"></label>
    <ul class="palette-list" role="listbox" data-palette-list></ul>
  </div>
</dialog>

<?php toast_markup(); ?>

<script>
  window.LAB = { palette: <?= json_encode($palette, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?> };
</script>
<script src="admin.js"></script>
</body>
</html>
