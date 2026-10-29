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
    }
    header('Location: ' . $self);
    exit;
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

    return [
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

$db = dl_db();
$now = time();
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
      <a class="card stat" href="#landing-pages"><strong><?= count($sections['landing-pages']['pages']) ?></strong><span>landing pages</span></a>
      <a class="card stat" href="#docs"><strong><?= count($sections['docs']['pages']) ?></strong><span>docs</span></a>
      <a class="card stat" href="#artifacts"><strong><?= count($sections['artifacts']['pages']) ?></strong><span>artifacts</span></a>
      <a class="card stat" href="#timers"><strong><?= count($campaigns) ?></strong><span>active timer campaigns</span></a>
      <a class="card stat" href="#timers"><strong><?= $people ?></strong><span>people tracked</span></a>
      <a class="card stat" href="#timers"><strong><?= $visits7 ?></strong><span>timer views, last 7 days</span></a>
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
        <?php else: ?>
          <div class="page-grid">
            <?php foreach ($s['pages'] as $p): ?>
              <article class="card page-card">
                <a class="thumb" href="<?= dl_h($p['url']) ?>" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
                  <iframe data-src="<?= dl_h($p['url']) ?>" title="" sandbox="allow-scripts" loading="lazy" scrolling="no"></iframe>
                </a>
                <div class="page-body">
                  <div class="row tags">
                    <?php if ($p['noindex']): ?><span class="badge badge-muted" title="Hidden from search engines">private</span><?php else: ?><span class="badge badge-live" title="Search engines can find it">public</span><?php endif; ?>
                    <?php if ($p['deadline_app']): ?><span class="badge">deadline timer</span><?php elseif ($p['countdown']): ?><span class="badge">built-in countdown</span><?php endif; ?>
                    <?php if ($p['missing']): ?><span class="badge badge-error" title="<?= dl_h(implode("\n", $p['missing'])) ?>"><?= count($p['missing']) ?> missing files</span><?php endif; ?>
                  </div>
                  <h3><a href="<?= dl_h($p['url']) ?>" target="_blank" rel="noopener"><?= dl_h($p['title']) ?></a></h3>
                  <p class="path"><?= dl_h($p['url']) ?></p>
                  <p class="muted small">Updated <?= dl_h(ago($p['updated'])) ?> · <?= number_format($p['size'] / 1024) ?> KB</p>
                  <?php if ($p['missing']): ?>
                    <details class="missing"><summary>see missing files</summary><ul><?php foreach ($p['missing'] as $f): ?><li><code><?= dl_h($f) ?></code></li><?php endforeach; ?></ul></details>
                  <?php endif; ?>
                  <div class="row">
                    <a class="btn btn-sm" href="<?= dl_h($p['url']) ?>" target="_blank" rel="noopener">open</a>
                    <button type="button" class="linkish" data-copy-url="<?= dl_h($p['url']) ?>">copy link</button>
                    <a class="linkish" href="<?= LAB_REPO ?>/blob/main/lab<?= dl_h($p['file']) ?>" target="_blank" rel="noopener">source</a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

    <section class="lab-section" id="timers">
      <div class="section-head row-between">
        <div>
          <h2>Deadline timers <span class="count"><?= count($campaigns) ?></span></h2>
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
        <a class="card link-card" href="/brand/thumbnail.html" target="_blank" rel="noopener"><strong>Design system</strong><span class="muted small">colors, fonts, logo</span></a>
        <a class="card link-card" href="/brand/ui_kits/website/" target="_blank" rel="noopener"><strong>Website UI kit</strong><span class="muted small">click-through site recreation</span></a>
        <a class="card link-card" href="/brand/templates/service-page/ServicePage.dc.html" target="_blank" rel="noopener"><strong>Service page template</strong><span class="muted small">copy-and-fill landing page</span></a>
        <a class="card link-card" href="/apps/deadlines/demo/" target="_blank" rel="noopener"><strong>Deadline demo funnel</strong><span class="muted small">practice opt-in and sales pages</span></a>
        <a class="card link-card" href="/" target="_blank" rel="noopener"><strong>Public lab home</strong><span class="muted small">lab.emilydiazslp.com</span></a>
        <a class="card link-card" href="<?= LAB_REPO ?>" target="_blank" rel="noopener"><strong>GitHub repo</strong><span class="muted small">every file and change</span></a>
      </div>
    </section>
<?php
page_end();
