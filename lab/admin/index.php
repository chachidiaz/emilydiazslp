<?php
// Lab dashboard: every page, doc and artifact on lab.emilydiazslp.com, plus deadline timer stats.
// Uses the deadline timers app's login, so one password covers both.

declare(strict_types=1);

require_once __DIR__ . '/../apps/deadlines/lib/auth.php';

dl_session_start();
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: SAMEORIGIN');

const LAB_SECTIONS = [
    'landing-pages' => ['Landing pages', 'Sales pages, offers and opt-ins.'],
    'docs' => ['Docs', 'Guides and documents.'],
    'artifacts' => ['Artifacts', 'Pages made with Claude.'],
];
const LAB_REPO = 'https://github.com/chachidiaz/emilydiazslp';
const LAB_TYPES = ['sales' => 'sales page', 'optin' => 'freebie opt-in', 'other' => 'other'];

$root = dirname(__DIR__);
$self = strtok($_SERVER['REQUEST_URI'] ?? '/admin/', '?');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    dl_check_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'login') {
        $result = dl_login((string) ($_POST['password'] ?? ''));
        $_SESSION['flash'] = $result === true ? null : (is_string($result) ? $result : 'That password didn\'t work.');
    } elseif ($action === 'logout' && dl_is_admin()) {
        dl_logout();
    } elseif (dl_is_admin()) {
        header('Location: ' . $self . lab_organize($root, $action));
        exit;
    }
    header('Location: ' . $self);
    exit;
}

// ---------- page types and folders (landing pages only) ----------

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
    $db->prepare('INSERT INTO lab_pages (url) VALUES (?) ON CONFLICT(url) DO NOTHING')->execute([$url]);
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

// Handles the organize actions and returns the anchor to land back on.
function lab_organize(string $root, string $action): string
{
    $db = lab_db();
    $name = trim((string) ($_POST['name'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);
    switch ($action) {
        case 'organize_page':
            $url = (string) ($_POST['url'] ?? '');
            if (!in_array($url, array_column(lab_pages($root, 'landing-pages'), 'url'), true)) {
                return '#landing-pages';
            }
            $type = isset(LAB_TYPES[$_POST['type'] ?? '']) ? $_POST['type'] : null;
            $folder = (int) ($_POST['folder'] ?? 0);
            $newFolder = trim((string) ($_POST['new_folder'] ?? ''));
            if ($newFolder !== '') {
                $folder = lab_new_folder($newFolder);
            }
            $folder = $folder && lab_folder_exists($folder) ? $folder : null;
            lab_set_meta($url, ['type' => $type, 'folder_id' => $folder]);
            return $folder ? '#folder-' . $folder : '#landing-pages';
        case 'rename_page':
            $pages = lab_all_pages($root);
            $url = (string) ($_POST['url'] ?? '');
            if (isset($pages[$url])) {
                lab_set_meta($url, ['name' => $name !== '' && $name !== $pages[$url]['title'] ? substr($name, 0, 120) : null]);
                $_SESSION['flash'] = $name !== '' ? 'Renamed.' : 'Back to the page\'s own title.';
            }
            return '#page-' . md5($url);
        case 'delete_page':
            $pages = lab_all_pages($root);
            $url = (string) ($_POST['url'] ?? '');
            if (!isset($pages[$url])) {
                return '';
            }
            $p = $pages[$url];
            $trash = lab_trash_dir();
            $trashName = date('Ymd-His') . '-' . trim(preg_replace('/[^a-z0-9.]+/i', '-', $p['file']), '-');
            if ((!is_dir($trash) && !mkdir($trash, 0755, true)) || !rename($root . $p['file'], $trash . '/' . $trashName)) {
                $_SESSION['flash'] = 'That page couldn\'t be deleted: the server didn\'t allow it. Nothing changed.';
                return '#page-' . md5($url);
            }
            // Remove the page's folder too if nothing else is left in it.
            $dir = dirname($root . $p['file']);
            if (!in_array(basename($dir), array_keys(LAB_SECTIONS), true) && count(scandir($dir)) === 2) {
                rmdir($dir);
            }
            $st = $db->prepare('SELECT * FROM lab_pages WHERE url = ?');
            $st->execute([$url]);
            $title = lab_page_name($p, $st->fetch() ?: null);
            $db->prepare('INSERT INTO lab_trash (file, url, title, trash_name, deleted_at) VALUES (?, ?, ?, ?, ?)')
                ->execute([$p['file'], $url, $title, $trashName, time()]);
            $_SESSION['flash'] = '"' . $title . '" is offline now and in Recently deleted.';
            return '#trash';
        case 'restore_page':
        case 'purge_page':
            $st = $db->prepare('SELECT * FROM lab_trash WHERE id = ?');
            $st->execute([$id]);
            $t = $st->fetch();
            if (!$t) {
                return '#trash';
            }
            $from = lab_trash_dir() . '/' . $t['trash_name'];
            if ($action === 'purge_page') {
                if (is_file($from)) unlink($from);
                $db->prepare('DELETE FROM lab_trash WHERE id = ?')->execute([$id]);
                $db->prepare('DELETE FROM lab_pages WHERE url = ?')->execute([$t['url']]);
                $_SESSION['flash'] = '"' . $t['title'] . '" was deleted for good.';
                return '#trash';
            }
            $to = $root . $t['file'];
            if (file_exists($to)) {
                $_SESSION['flash'] = 'A page already exists at ' . $t['url'] . ', so this one wasn\'t restored.';
                return '#trash';
            }
            if (!is_dir(dirname($to))) mkdir(dirname($to), 0755, true);
            if (!is_file($from) || !rename($from, $to)) {
                $_SESSION['flash'] = 'That page couldn\'t be restored.';
                return '#trash';
            }
            $db->prepare('DELETE FROM lab_trash WHERE id = ?')->execute([$id]);
            $_SESSION['flash'] = '"' . $t['title'] . '" is back online.';
            return '#page-' . md5($t['url']);
        case 'create_folder':
            return $name !== '' ? '#folder-' . lab_new_folder($name) : '#landing-pages';
        case 'rename_folder':
            if ($name !== '') {
                $db->prepare('UPDATE lab_folders SET name = ? WHERE id = ?')->execute([substr($name, 0, 80), $id]);
            }
            return '#folder-' . $id;
        case 'delete_folder':
            $db->prepare('UPDATE lab_pages SET folder_id = NULL WHERE folder_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM lab_folders WHERE id = ?')->execute([$id]);
            $_SESSION['flash'] = 'Folder deleted. Its pages are back in "not in a folder".';
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
        $pages[] = lab_page_info($root, $rel);
    }
    usort($pages, fn ($a, $b) => strcmp($a['url'], $b['url']));
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

    return [
        'id' => strtoupper($idm[1] ?? ''),
        'title' => $title,
        'url' => $url,
        'file' => $rel,
        'updated' => filemtime($path),
        'size' => filesize($path),
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

function lab_card(array $p, array $org, bool $organize = false): void
{
    global $csrf;
    $meta = $org['meta'][$p['url']] ?? null;
    $name = lab_page_name($p, $meta);
    $type = $org['meta'][$p['url']]['type'] ?? null;
    $folder = (int) ($org['meta'][$p['url']]['folder_id'] ?? 0);
    ?>
    <article class="card page-card" id="page-<?= md5($p['url']) ?>" data-type="<?= $type ?? 'unsorted' ?>" data-find="<?= dl_h(strtolower($p['id'] . ' ' . $name . ' ' . $p['title'] . ' ' . $p['url'])) ?>">
      <a class="thumb" href="<?= dl_h($p['url']) ?>" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
        <iframe data-src="<?= dl_h($p['url']) ?>" title="" sandbox="allow-scripts" loading="lazy" scrolling="no"></iframe>
      </a>
      <div class="page-body">
        <div class="row tags">
          <?php if ($organize): ?><span class="badge type-<?= $type ?? 'unsorted' ?>"><?= $type ? LAB_TYPES[$type] : 'unsorted' ?></span><?php endif; ?>
          <?php if ($p['noindex']): ?><span class="badge badge-muted" title="Hidden from search engines">private</span><?php else: ?><span class="badge badge-live" title="Search engines can find it">public</span><?php endif; ?>
          <?php if ($p['deadline_app']): ?><span class="badge">deadline timer</span><?php elseif ($p['countdown']): ?><span class="badge">built-in countdown</span><?php endif; ?>
          <?php if ($p['missing']): ?><span class="badge badge-error" title="<?= dl_h(implode("\n", $p['missing'])) ?>"><?= count($p['missing']) ?> missing files</span><?php endif; ?>
        </div>
        <?php if ($p['id']): ?>
          <button type="button" class="lab-id" data-copy-text="<?= dl_h($p['id']) ?>" title="Copy this ID to tell Claude which page to edit"><?= dl_h($p['id']) ?></button>
        <?php else: ?>
          <span class="lab-id missing-id" title="Ask Claude to give this page an ID">no ID yet</span>
        <?php endif; ?>
        <h3><a href="<?= dl_h($p['url']) ?>" target="_blank" rel="noopener"><?= dl_h($name) ?></a></h3>
        <?php if ($name !== $p['title']): ?><p class="muted small">Page title: <?= dl_h($p['title']) ?></p><?php endif; ?>
        <p class="path"><?= dl_h($p['url']) ?></p>
        <p class="muted small">Updated <?= dl_h(ago($p['updated'])) ?> · <?= number_format($p['size'] / 1024) ?> KB</p>
        <?php if ($p['missing']): ?>
          <details class="missing"><summary>see missing files</summary><ul><?php foreach ($p['missing'] as $f): ?><li><code><?= dl_h($f) ?></code></li><?php endforeach; ?></ul></details>
        <?php endif; ?>
        <?php if ($organize): ?>
          <form method="post" class="organize" action="#landing-pages">
            <input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>">
            <input type="hidden" name="action" value="organize_page">
            <input type="hidden" name="url" value="<?= dl_h($p['url']) ?>">
            <input type="hidden" name="new_folder" value="">
            <label>Type
              <select name="type">
                <option value="">unsorted</option>
                <?php foreach (LAB_TYPES as $k => $label): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
              </select>
            </label>
            <label>Folder
              <select name="folder">
                <option value="0">not in a folder</option>
                <?php foreach ($org['folders'] as $f): ?><option value="<?= (int) $f['id'] ?>" <?= $folder === (int) $f['id'] ? 'selected' : '' ?>><?= dl_h($f['name']) ?></option><?php endforeach; ?>
                <option value="new">+ new folder…</option>
              </select>
            </label>
            <noscript><button class="btn btn-sm btn-outline">save</button></noscript>
          </form>
        <?php endif; ?>
        <div class="row">
          <a class="btn btn-sm" href="<?= dl_h($p['url']) ?>" target="_blank" rel="noopener">open</a>
          <button type="button" class="linkish" data-copy-url="<?= dl_h($p['url']) ?>">copy link</button>
          <a class="linkish" href="<?= LAB_REPO ?>/blob/main/lab<?= dl_h($p['file']) ?>" target="_blank" rel="noopener">source</a>
        </div>
        <details class="claude-box" data-id="<?= dl_h($p['id']) ?>" data-file="lab<?= dl_h($p['file']) ?>" data-url="<?= dl_h($p['url']) ?>" data-name="<?= dl_h($name) ?>">
          <summary class="btn btn-sm btn-outline">edit with claude</summary>
          <label>What would you like to change?
            <textarea rows="3" placeholder="e.g. change the headline to “Your 30-minute bonus”"></textarea>
          </label>
          <div class="row">
            <button type="button" class="btn btn-sm" data-copy-claude>copy for claude</button>
            <span class="hint" role="status"></span>
          </div>
          <p class="hint">Copies this page's ID and your request. Paste it into a new Claude Code session in the desktop app.</p>
        </details>
        <details class="card-menu">
          <summary class="linkish">rename or delete</summary>
          <form method="post" class="row">
            <input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>"><input type="hidden" name="url" value="<?= dl_h($p['url']) ?>">
            <input name="name" value="<?= dl_h($name) ?>" aria-label="Name on the dashboard" maxlength="120">
            <button class="btn btn-sm" name="action" value="rename_page">rename</button>
          </form>
          <p class="hint">Changes the name here only. Leave it blank to use the page's own title.</p>
          <form method="post" data-confirm="Delete &quot;<?= dl_h($name) ?>&quot;? It goes offline right away. You can restore it from Recently deleted.">
            <input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>"><input type="hidden" name="url" value="<?= dl_h($p['url']) ?>">
            <button class="linkish danger" name="action" value="delete_page">delete page</button>
          </form>
        </details>
      </div>
    </article>
    <?php
}

function ago(int $ts): string
{
    $d = time() - $ts;
    if ($d < 3600) return max(1, intdiv($d, 60)) . ' min ago';
    if ($d < 86400) return intdiv($d, 3600) . ' hr ago';
    if ($d < 86400 * 30) return intdiv($d, 86400) . ' day' . (intdiv($d, 86400) === 1 ? '' : 's') . ' ago';
    return date('M j, Y', $ts);
}

// ---------- views ----------

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$csrf = dl_csrf();

function page_start(string $title): void
{
    global $flash, $csrf;
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= dl_h($title) ?></title>
  <link rel="stylesheet" href="/brand/styles.css">
  <link rel="stylesheet" href="/apps/deadlines/admin.css">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <header class="top">
    <div class="wrap top-in">
      <a class="brand" href="/admin/"><img src="/brand/assets/logo-maroon.png" alt="Emily Diaz, SLP"><span>lab admin</span></a>
      <?php if (dl_is_admin()): ?>
      <nav>
        <a href="/admin/">dashboard</a>
        <a href="/apps/deadlines/">deadline timers</a>
        <a href="/" target="_blank" rel="noopener">lab home</a>
        <form method="post"><input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>"><button name="action" value="logout" class="linkish">log out</button></form>
      </nav>
      <?php endif; ?>
    </div>
  </header>
  <main class="wrap">
    <?php if ($flash): ?><div class="flash" role="status"><?= dl_h($flash) ?></div><?php endif; ?>
<?php
}

function page_end(): void
{
    ?>
  </main>
  <script>
    document.querySelectorAll('[data-copy-url]').forEach(function (b) {
      b.addEventListener('click', function () {
        navigator.clipboard.writeText(location.origin + b.getAttribute('data-copy-url')).then(function () {
          var t = b.textContent; b.textContent = 'copied'; setTimeout(function () { b.textContent = t; }, 1400);
        });
      });
    });
    document.querySelectorAll('[data-copy-text]').forEach(function (b) {
      b.addEventListener('click', function () {
        navigator.clipboard.writeText(b.getAttribute('data-copy-text')).then(function () {
          var t = b.textContent; b.textContent = 'copied'; setTimeout(function () { b.textContent = t; }, 1200);
        });
      });
    });
    // "Edit with claude": copies the page's ID and the request, to paste into a new Claude session.
    document.querySelectorAll('.claude-box').forEach(function (box) {
      var area = box.querySelector('textarea'), note = box.querySelector('[role=status]');
      box.addEventListener('toggle', function () { if (box.open) area.focus(); });
      box.querySelector('[data-copy-claude]').addEventListener('click', function () {
        var id = box.getAttribute('data-id'), ask = area.value.trim();
        var text = 'Edit lab page ' + (id || '(no ID yet)') + ' ("' + box.getAttribute('data-name') + '").\n' +
          'File: ' + box.getAttribute('data-file') + '\n' +
          'Live: ' + location.origin + box.getAttribute('data-url') + '\n\n' +
          (ask ? 'What to change: ' + ask : "I'll tell you what to change.") + '\n\n' +
          'Follow CLAUDE.md. Pull the latest main first. Show me the change before publishing.';
        navigator.clipboard.writeText(text).then(function () {
          note.textContent = 'Copied. Paste it into Claude.';
        }, function () {
          // Clipboard blocked: show the text selected so Cmd+C copies it.
          area.value = text;
          area.select();
          note.textContent = 'Press Cmd+C (Ctrl+C on Windows) to copy.';
        });
      });
    });
    // Find: narrow every section to cards whose ID, name or address matches.
    (function () {
      var box = document.getElementById('find');
      if (!box) return;
      box.addEventListener('input', function () {
        var q = box.value.trim().toLowerCase(), hits = 0;
        document.querySelectorAll('.page-card').forEach(function (c) {
          var on = !q || c.getAttribute('data-find').indexOf(q) >= 0;
          c.classList.toggle('find-hide', !on);
          if (on) hits++;
        });
        document.querySelectorAll('.folder').forEach(function (f) {
          f.classList.toggle('find-hide', !!q && !f.querySelector('.page-card:not(.find-hide)'));
        });
        document.getElementById('find-none').hidden = !q || hits > 0;
        var first = q && document.querySelector('.page-card:not(.find-hide)');
        if (first && /^[a-z]+-\d+$/.test(q)) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    })();
    // Type filters: show only matching cards, and hide folders with none left.
    document.querySelectorAll('[data-filter]').forEach(function (chip) {
      chip.addEventListener('click', function () {
        var want = chip.getAttribute('data-filter'), any = false;
        document.querySelectorAll('[data-filter]').forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
        document.querySelectorAll('#landing-pages .page-card').forEach(function (card) {
          card.hidden = want !== 'all' && card.getAttribute('data-type') !== want;
        });
        document.querySelectorAll('#landing-pages .folder').forEach(function (f) {
          var visible = f.querySelectorAll('.page-card:not([hidden])').length;
          f.hidden = want !== 'all' && !visible;
          any = any || visible > 0;
        });
        document.querySelector('#landing-pages .filter-empty').hidden = any || want === 'all';
      });
    });
    // Save a page's type or folder as soon as it changes.
    document.querySelectorAll('form.organize').forEach(function (form) {
      var folder = form.elements.folder, last = folder.value;
      form.addEventListener('change', function (e) {
        if (e.target === folder && folder.value === 'new') {
          var name = prompt('Name the new folder');
          if (!name || !name.trim()) { folder.value = last; return; }
          form.elements.new_folder.value = name.trim();
        }
        form.submit();
      });
    });
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
      f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
    });
    // Scale each 1280px-wide page preview to fit its card.
    function fitThumbs() {
      document.querySelectorAll('.thumb').forEach(function (t) { t.style.setProperty('--thumb-scale', t.clientWidth / 1280); });
    }
    fitThumbs();
    window.addEventListener('resize', fitThumbs);
    // Load thumbnails one after another so the dashboard stays quick.
    (function () {
      var frames = Array.prototype.slice.call(document.querySelectorAll('iframe[data-src]'));
      function next() {
        var f = frames.shift(), done = false;
        if (!f) return;
        function go() { if (!done) { done = true; next(); } }
        f.addEventListener('load', go, { once: true });
        setTimeout(go, 4000);
        f.src = f.getAttribute('data-src');
        f.removeAttribute('data-src');
      }
      next(); next();
    })();
  </script>
</body>
</html>
<?php
}

if (!dl_has_password()) {
    header('Location: /apps/deadlines/');
    exit;
}

if (!dl_is_admin()) {
    page_start('Lab admin');
    ?>
    <section class="card narrow">
      <p class="eyebrow">Lab admin</p>
      <h1>Welcome back</h1>
      <p class="muted">Same password as your deadline timers.</p>
      <form method="post" class="stack">
        <input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>">
        <label>Password <input type="password" name="password" required autofocus autocomplete="current-password"></label>
        <button class="btn" name="action" value="login">log in</button>
      </form>
    </section>
    <?php
    page_end();
    exit;
}

$sections = [];
foreach (LAB_SECTIONS as $dir => [$title, $blurb]) {
    $sections[$dir] = ['title' => $title, 'blurb' => $blurb, 'pages' => lab_pages($root, $dir)];
}

$db = lab_db();
$now = time();
$org = [
    'folders' => $db->query('SELECT * FROM lab_folders ORDER BY name COLLATE NOCASE')->fetchAll(),
    'meta' => array_column($db->query('SELECT * FROM lab_pages')->fetchAll(), null, 'url'),
];
$campaigns = $db->query('SELECT c.*,
        (SELECT COUNT(*) FROM visitors v WHERE v.campaign_id = c.id) AS people,
        (SELECT COUNT(*) FROM visitors v WHERE v.campaign_id = c.id AND v.email IS NOT NULL) AS emails
    FROM campaigns c WHERE c.archived = 0 ORDER BY c.created_at DESC')->fetchAll();
$people = (int) $db->query('SELECT COUNT(*) FROM visitors')->fetchColumn();
$visits7 = (int) $db->query('SELECT COUNT(*) FROM hits WHERE at > ' . ($now - 7 * 86400))->fetchColumn();
$recent = $db->query('SELECT h.at, h.url, h.via, v.email, v.id AS vid, c.name AS campaign
    FROM hits h JOIN visitors v ON v.id = h.visitor_id JOIN campaigns c ON c.id = v.campaign_id
    ORDER BY h.at DESC LIMIT 8')->fetchAll();
$totalPages = array_sum(array_map(fn ($s) => count($s['pages']), $sections));
$missingCount = 0;
foreach ($sections as $s) {
    foreach ($s['pages'] as $p) {
        $missingCount += $p['missing'] ? 1 : 0;
    }
}

page_start('Lab admin');
?>
    <div class="page-head">
      <div>
        <p class="eyebrow">lab.emilydiazslp.com</p>
        <h1>Everything at a glance</h1>
      </div>
      <div class="row">
        <a class="btn btn-outline btn-sm" href="<?= LAB_REPO ?>/actions" target="_blank" rel="noopener">deploy history</a>
        <a class="btn btn-sm" href="/apps/deadlines/">open deadline timers</a>
      </div>
    </div>

    <div class="stat-row">
      <a class="card stat" href="#landing-pages"><strong><?= count($sections['landing-pages']['pages']) ?></strong><span>landing pages<?php
        $types = array_count_values(array_filter(array_map(fn ($p) => $org['meta'][$p['url']]['type'] ?? null, $sections['landing-pages']['pages'])));
        if ($types) echo '<br>' . (int) ($types['sales'] ?? 0) . ' sales · ' . (int) ($types['optin'] ?? 0) . ' opt-in';
      ?></span></a>
      <a class="card stat" href="#docs"><strong><?= count($sections['docs']['pages']) ?></strong><span>docs</span></a>
      <a class="card stat" href="#artifacts"><strong><?= count($sections['artifacts']['pages']) ?></strong><span>artifacts</span></a>
      <a class="card stat" href="#timers"><strong><?= count($campaigns) ?></strong><span>active timer campaigns</span></a>
      <a class="card stat" href="#timers"><strong><?= $people ?></strong><span>people tracked</span></a>
      <a class="card stat" href="#timers"><strong><?= $visits7 ?></strong><span>timer views, last 7 days</span></a>
    </div>

    <div class="find">
      <input type="search" id="find" placeholder="Find a page by ID or name, e.g. LP-002" aria-label="Find a page by ID or name" autocomplete="off">
      <p class="hint" id="find-none" hidden>Nothing matches. IDs look like LP-001 (landing pages), DOC-001 (docs) or ART-001 (artifacts).</p>
    </div>


    <?php if ($missingCount): ?>
      <div class="flash warn" role="status"><?= $missingCount ?> page<?= $missingCount === 1 ? ' is' : 's are' ?> missing images or fonts. Look for the red label below.</div>
    <?php endif; ?>

    <?php foreach ($sections as $dir => $s): ?>
      <section class="lab-section" id="<?= $dir ?>">
        <div class="section-head">
          <h2><?= dl_h($s['title']) ?> <span class="count"><?= count($s['pages']) ?></span></h2>
          <p class="muted"><?= dl_h($s['blurb']) ?></p>
        </div>
        <?php if (!$s['pages']): ?>
          <p class="muted empty-section">Nothing here yet. Pages added to <code>lab/<?= $dir ?>/</code> show up here on their own.</p>
        <?php elseif ($dir === 'landing-pages'):
            $byFolder = [];
            $typeCounts = ['all' => count($s['pages']), 'sales' => 0, 'optin' => 0, 'other' => 0, 'unsorted' => 0];
            foreach ($s['pages'] as $p) {
                $m = $org['meta'][$p['url']] ?? null;
                $byFolder[(int) ($m['folder_id'] ?? 0)][] = $p;
                $typeCounts[$m['type'] ?? 'unsorted']++;
            }
            $filterLabels = ['all' => 'all', 'sales' => 'sales pages', 'optin' => 'freebie opt-ins', 'other' => 'other', 'unsorted' => 'unsorted'];
        ?>
          <div class="organize-bar">
            <div class="filters" role="group" aria-label="Show pages by type">
              <?php foreach ($filterLabels as $k => $label): if ($k !== 'all' && !$typeCounts[$k]) continue; ?>
                <button type="button" class="chip" data-filter="<?= $k ?>" aria-pressed="<?= $k === 'all' ? 'true' : 'false' ?>"><?= $label ?> <span class="count"><?= $typeCounts[$k] ?></span></button>
              <?php endforeach; ?>
            </div>
            <form method="post" class="row new-folder">
              <input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>">
              <input name="name" placeholder="New folder, e.g. AAC Hub fall launch" aria-label="New folder name" required maxlength="80">
              <button class="btn btn-outline btn-sm" name="action" value="create_folder">+ folder</button>
            </form>
          </div>

          <?php foreach ($org['folders'] as $f): $list = $byFolder[(int) $f['id']] ?? []; ?>
            <div class="folder" id="folder-<?= (int) $f['id'] ?>">
              <div class="folder-head">
                <h3><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h3.6c.66 0 1.3.26 1.77.73L12.3 6.2H18.5A2.5 2.5 0 0 1 21 8.7v8.8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/></svg><?= dl_h($f['name']) ?> <span class="count"><?= count($list) ?></span></h3>
                <details class="folder-menu">
                  <summary class="linkish">rename or delete</summary>
                  <div class="folder-menu-body">
                    <form method="post" class="row">
                      <input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                      <input name="name" value="<?= dl_h($f['name']) ?>" aria-label="Folder name" required maxlength="80">
                      <button class="btn btn-sm" name="action" value="rename_folder">rename</button>
                    </form>
                    <form method="post" data-confirm="Delete the folder &quot;<?= dl_h($f['name']) ?>&quot;? Its pages stay, they just won't be in a folder.">
                      <input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                      <button class="linkish danger" name="action" value="delete_folder">delete folder</button>
                    </form>
                  </div>
                </details>
              </div>
              <?php if (!$list): ?>
                <p class="muted empty-section">Empty. Move pages here with the <strong>Folder</strong> menu on any page.</p>
              <?php else: ?>
                <div class="page-grid"><?php foreach ($list as $p) lab_card($p, $org, true); ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <?php if (!empty($byFolder[0])): ?>
            <div class="folder unfiled">
              <?php if ($org['folders']): ?><div class="folder-head"><h3>Not in a folder <span class="count"><?= count($byFolder[0]) ?></span></h3></div><?php endif; ?>
              <div class="page-grid"><?php foreach ($byFolder[0] as $p) lab_card($p, $org, true); ?></div>
            </div>
          <?php endif; ?>
          <p class="muted empty-section filter-empty" hidden>No pages of this type yet.</p>
        <?php else: ?>
          <div class="page-grid"><?php foreach ($s['pages'] as $p) lab_card($p, $org); ?></div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <?php $trash = $db->query('SELECT * FROM lab_trash ORDER BY deleted_at DESC')->fetchAll(); if ($trash): ?>
      <section class="lab-section" id="trash">
        <div class="section-head">
          <h2>Recently deleted <span class="count"><?= count($trash) ?></span></h2>
          <p class="muted">These pages are offline. Restore one to put it back at the same address.</p>
        </div>
        <div class="card">
          <ul class="plain-list">
            <?php foreach ($trash as $t): ?>
              <li class="row-between">
                <div>
                  <strong><?= dl_h($t['title']) ?></strong>
                  <span class="muted small path"><?= dl_h($t['url']) ?> · deleted <?= dl_h(ago((int) $t['deleted_at'])) ?></span>
                </div>
                <div class="row">
                  <form method="post"><input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-sm btn-outline" name="action" value="restore_page">restore</button></form>
                  <form method="post" data-confirm="Delete &quot;<?= dl_h($t['title']) ?>&quot; for good? This can't be undone."><input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="linkish danger" name="action" value="purge_page">delete for good</button></form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
          <p class="hint">Pages are also kept in GitHub. Next time you're working with Claude, ask to remove deleted pages from the repo so they stay gone.</p>
        </div>
      </section>
    <?php endif; ?>

    <section class="lab-section" id="timers">
      <div class="section-head row-between">
        <div>
          <h2><span class="lab-id small-id">APP-001</span> Deadline timers <span class="count"><?= count($campaigns) ?></span></h2>
          <p class="muted">Active campaigns and the latest timer views.</p>
        </div>
        <a class="btn btn-sm" href="/apps/deadlines/">open deadline timers</a>
      </div>
      <div class="two-col">
        <div class="card">
          <h3>Campaigns</h3>
          <?php if (!$campaigns): ?>
            <p class="muted">No active campaigns. <a href="/apps/deadlines/">Create one</a>.</p>
          <?php else: ?>
            <ul class="plain-list">
              <?php foreach ($campaigns as $c):
                  $ended = $c['mode'] === 'fixed' && $c['fixed_deadline'] && $c['fixed_deadline'] <= $now; ?>
                <li>
                  <a href="/apps/deadlines/?p=campaign&amp;id=<?= (int) $c['id'] ?>"><strong><?= dl_h($c['name']) ?></strong></a>
                  <span class="muted small"><?= $c['mode'] === 'fixed' ? ($ended ? 'fixed date · ended' : 'fixed date · ends ' . dl_h(date('M j', (int) $c['fixed_deadline']))) : 'evergreen · ' . dl_h((string) round($c['duration_minutes'] / 1440, 1)) . ' days' ?></span>
                  <span class="small"><strong><?= (int) $c['people'] ?></strong> people · <strong><?= (int) $c['emails'] ?></strong> with email</span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <div class="card">
          <h3>Latest timer views</h3>
          <?php if (!$recent): ?>
            <p class="muted">No views yet.</p>
          <?php else: ?>
            <ul class="plain-list">
              <?php foreach ($recent as $h): ?>
                <li>
                  <a href="/apps/deadlines/?p=person&amp;id=<?= (int) $h['vid'] ?>"><strong><?= $h['email'] ? dl_h($h['email']) : 'no email yet' ?></strong></a>
                  <span class="muted small"><?= dl_h($h['campaign']) ?> · <?= dl_h(preg_replace('#^https?://[^/]+#', '', $h['url']) ?: '/') ?></span>
                  <span class="muted small"><?= dl_h(ago((int) $h['at'])) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="lab-section" id="brand">
      <div class="section-head">
        <h2>Brand &amp; tools</h2>
      </div>
      <div class="link-grid">
        <a class="card link-card" href="/brand/thumbnail.html" target="_blank" rel="noopener"><span class="lab-id small-id">BR-001</span><strong>Design system</strong><span class="muted small">colors, fonts, logo</span></a>
        <a class="card link-card" href="/brand/ui_kits/website/" target="_blank" rel="noopener"><span class="lab-id small-id">BR-002</span><strong>Website UI kit</strong><span class="muted small">click-through site recreation</span></a>
        <a class="card link-card" href="/brand/templates/service-page/ServicePage.dc.html" target="_blank" rel="noopener"><span class="lab-id small-id">BR-003</span><strong>Service page template</strong><span class="muted small">copy-and-fill landing page</span></a>
        <a class="card link-card" href="/apps/deadlines/demo/" target="_blank" rel="noopener"><span class="lab-id small-id">APP-002</span><strong>Deadline demo funnel</strong><span class="muted small">practice opt-in and sales pages</span></a>
        <a class="card link-card" href="/" target="_blank" rel="noopener"><strong>Public lab home</strong><span class="muted small">lab.emilydiazslp.com</span></a>
        <a class="card link-card" href="<?= LAB_REPO ?>" target="_blank" rel="noopener"><strong>GitHub repo</strong><span class="muted small">every file and change</span></a>
      </div>
    </section>
<?php
page_end();
