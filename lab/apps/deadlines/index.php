<?php
// Dashboard for deadline timers: campaigns, timers, tracked people, settings.

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

dl_session_start();
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: SAMEORIGIN');

const DL_DEFAULT_MERGE_TAG = '{{ subscriber.email }}';
const DL_BRAND_COLORS = ['#743E5A', '#4A2739', '#C45B8F', '#E192B9', '#F6D9E7', '#FFFBFD', '#FFFFFF', '#5A4A53'];

$self = strtok($_SERVER['REQUEST_URI'] ?? '/apps/deadlines/', '?');
$appUrl = rtrim(dirname($self . 'x'), '/') . '/';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function go(string $query = '', ?string $flash = null): never
{
    global $self;
    if ($flash) {
        $_SESSION['flash'] = $flash;
    }
    header('Location: ' . $self . ($query ? '?' . $query : ''));
    exit;
}

function post(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function fmt_time(?int $ts, string $tz): string
{
    if (!$ts) {
        return '—';
    }
    $d = (new DateTime('@' . $ts))->setTimezone(new DateTimeZone($tz));
    return $d->format('M j, Y · g:i a');
}

function fmt_duration(int $minutes): string
{
    $d = intdiv($minutes, 1440);
    $h = intdiv($minutes % 1440, 60);
    $m = $minutes % 60;
    $out = [];
    if ($d) $out[] = $d . ' day' . ($d === 1 ? '' : 's');
    if ($h) $out[] = $h . ' hour' . ($h === 1 ? '' : 's');
    if ($m) $out[] = $m . ' minute' . ($m === 1 ? '' : 's');
    return $out ? implode(', ', $out) : '0 minutes';
}

function fmt_left(int $seconds): string
{
    if ($seconds <= 0) return 'expired';
    $d = intdiv($seconds, 86400);
    $h = intdiv($seconds % 86400, 3600);
    $m = intdiv($seconds % 3600, 60);
    if ($d) return "{$d}d {$h}h left";
    if ($h) return "{$h}h {$m}m left";
    return max(1, $m) . 'm left';
}

function local_input(?int $ts, string $tz): string
{
    if (!$ts) return '';
    return (new DateTime('@' . $ts))->setTimezone(new DateTimeZone($tz))->format('Y-m-d\TH:i');
}

function valid_tz(string $tz): string
{
    return in_array($tz, DateTimeZone::listIdentifiers(), true) ? $tz : 'America/Phoenix';
}

function campaign_summary(array $c): string
{
    if ($c['mode'] === 'fixed') {
        return 'Fixed date · ends ' . fmt_time((int) $c['fixed_deadline'], $c['timezone']);
    }
    return 'Evergreen · ' . fmt_duration((int) $c['duration_minutes']) . ' from first visit';
}

function merge_tag(): string
{
    return dl_setting('merge_tag', DL_DEFAULT_MERGE_TAG);
}

function create_demo(): int
{
    $db = dl_db();
    if ($c = dl_campaign_by_slug('demo')) {
        $db->prepare('UPDATE campaigns SET archived = 0 WHERE id = ?')->execute([$c['id']]);
        return (int) $c['id'];
    }
    $db->prepare('INSERT INTO campaigns (slug, name, mode, duration_minutes, timezone, reset_days, expired_action, expired_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute(['demo', 'Demo: freebie fast-action discount', 'evergreen', 7 * 1440, 'America/Phoenix', 30, 'message', 'The fast-action discount has ended.', time()]);
    $id = (int) $db->lastInsertId();
    dl_create_timer($id, 'bar', 'Announcement bar', [
        'headline' => 'Your fast-action discount ends in',
        'button_text' => 'claim my discount',
        'button_url' => '/apps/deadlines/demo/sales.html',
    ], 'demo-bar');
    dl_create_timer($id, 'inline', 'Sales page timer', [
        'headline' => 'Your 40% fast-action discount ends in',
    ], 'demo-inline');
    return $id;
}

// ---------- actions ----------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');

    if ($action === 'setup' && !dl_has_password()) {
        dl_check_csrf();
        $pw = (string) ($_POST['password'] ?? '');
        if (strlen($pw) < 10) go('', 'Use at least 10 characters.');
        if ($pw !== (string) ($_POST['password2'] ?? '')) go('', 'The two passwords don\'t match.');
        dl_set_password($pw);
        dl_login($pw);
        go('', 'You\'re all set. Start by creating a campaign.');
    }

    if ($action === 'login') {
        dl_check_csrf();
        $result = dl_login((string) ($_POST['password'] ?? ''));
        if ($result === true) go();
        go('', is_string($result) ? $result : 'That password didn\'t work.');
    }

    if (!dl_is_admin()) go();
    dl_check_csrf();
    $db = dl_db();
    $id = (int) ($_POST['id'] ?? 0);

    switch ($action) {
        case 'logout':
            dl_logout();
            go();

        case 'create_campaign':
            $name = post('name') ?: 'New campaign';
            $db->prepare('INSERT INTO campaigns (slug, name, created_at) VALUES (?, ?, ?)')
                ->execute([dl_slugify($name), $name, time()]);
            $cid = (int) $db->lastInsertId();
            dl_create_timer($cid, 'bar', 'Announcement bar');
            dl_create_timer($cid, 'inline', 'Inline timer');
            go('p=campaign&id=' . $cid, 'Campaign created with an announcement bar and an inline timer.');

        case 'create_demo':
            go('p=campaign&id=' . create_demo(), 'Demo campaign ready. Open the demo funnel to click through it.');

        case 'save_campaign':
            $c = dl_campaign($id) or go();
            $tz = valid_tz(post('timezone'));
            $mode = post('mode') === 'fixed' ? 'fixed' : 'evergreen';
            $minutes = max(1, (int) post('days') * 1440 + (int) post('hours') * 60 + (int) post('minutes'));
            $fixed = null;
            if (post('fixed_deadline') !== '') {
                try {
                    $fixed = (new DateTime(post('fixed_deadline'), new DateTimeZone($tz)))->getTimestamp();
                } catch (Exception) {
                    go('p=campaign&id=' . $id, 'That end date didn\'t look right.');
                }
            }
            if ($mode === 'fixed' && !$fixed) go('p=campaign&id=' . $id, 'Pick an end date for a fixed-date campaign.');
            $slug = strtolower(preg_replace('/[^a-z0-9-]+/i', '-', post('slug'))) ?: $c['slug'];
            $other = dl_campaign_by_slug($slug);
            if ($other && (int) $other['id'] !== $id) go('p=campaign&id=' . $id, 'Another campaign already uses that short name.');
            $action_ = in_array(post('expired_action'), ['hide', 'message', 'redirect'], true) ? post('expired_action') : 'message';
            $db->prepare('UPDATE campaigns SET name = ?, slug = ?, mode = ?, duration_minutes = ?, fixed_deadline = ?, timezone = ?, reset_days = ?, expired_action = ?, expired_message = ?, redirect_url = ? WHERE id = ?')
                ->execute([post('name') ?: $c['name'], $slug, $mode, $minutes, $fixed, $tz, max(0, (int) post('reset_days')), $action_, post('expired_message'), post('redirect_url'), $id]);
            go('p=campaign&id=' . $id, 'Saved.');

        case 'duplicate_campaign':
            $c = dl_campaign($id) or go();
            $name = $c['name'] . ' (copy)';
            $db->prepare('INSERT INTO campaigns (slug, name, mode, duration_minutes, fixed_deadline, timezone, reset_days, expired_action, expired_message, redirect_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([dl_slugify($name), $name, $c['mode'], $c['duration_minutes'], $c['fixed_deadline'], $c['timezone'], $c['reset_days'], $c['expired_action'], $c['expired_message'], $c['redirect_url'], time()]);
            $cid = (int) $db->lastInsertId();
            foreach (dl_campaign_timers($id) as $t) {
                dl_create_timer($cid, $t['type'], $t['name'], json_decode($t['settings'], true) ?: []);
            }
            go('p=campaign&id=' . $cid, 'Copied. The copy has new embed codes and no people yet.');

        case 'archive_campaign':
            $db->prepare('UPDATE campaigns SET archived = 1 - archived WHERE id = ?')->execute([$id]);
            go('p=campaign&id=' . $id);

        case 'delete_campaign':
            $db->prepare('DELETE FROM campaigns WHERE id = ?')->execute([$id]);
            go('', 'Campaign deleted.');

        case 'create_timer':
            $c = dl_campaign($id) or go();
            $type = post('type') === 'bar' ? 'bar' : 'inline';
            $tid = dl_create_timer($id, $type, $type === 'bar' ? 'Announcement bar' : 'Inline timer');
            go('p=timer&id=' . $tid);

        case 'save_timer':
            $st = $db->prepare('SELECT * FROM timers WHERE id = ?');
            $st->execute([$id]);
            $t = $st->fetch() or go();
            $settings = [];
            foreach (dl_timer_defaults($t['type']) as $k => $default) {
                if (is_bool($default)) {
                    $settings[$k] = !empty($_POST[$k]);
                } elseif (isset(DL_SIZE_RANGES[$k])) {
                    [$min, $max] = DL_SIZE_RANGES[$k];
                    $settings[$k] = max($min, min($max, (int) post($k, (string) $default)));
                } elseif ($k === 'font' || $k === 'digit_font') {
                    $settings[$k] = dl_clean_font(post($k));
                } else {
                    $v = post($k, (string) $default);
                    if (str_ends_with($k, 'bg') || str_ends_with($k, 'fg')) {
                        $v = preg_match('/^#[0-9a-f]{6}$/i', $v) ? $v : $default;
                    }
                    $settings[$k] = $v;
                }
            }
            $db->prepare('UPDATE timers SET name = ?, settings = ? WHERE id = ?')
                ->execute([post('name') ?: $t['name'], json_encode($settings), $id]);
            go('p=timer&id=' . $id, 'Timer saved. It updates on your pages right away.');

        case 'delete_timer':
            $st = $db->prepare('SELECT campaign_id FROM timers WHERE id = ?');
            $st->execute([$id]);
            $cid = (int) $st->fetchColumn();
            $db->prepare('DELETE FROM timers WHERE id = ?')->execute([$id]);
            go('p=campaign&id=' . $cid . '&tab=timers', 'Timer deleted. Remove its embed code from your pages too.');

        case 'clear_person':
            $st = $db->prepare('SELECT campaign_id FROM visitors WHERE id = ?');
            $st->execute([$id]);
            $cid = (int) $st->fetchColumn();
            $db->prepare('DELETE FROM visitors WHERE id = ?')->execute([$id]);
            go('p=campaign&id=' . $cid . '&tab=people', 'Timer cleared. They get a fresh deadline on their next visit.');

        case 'clear_campaign':
            $db->prepare('DELETE FROM visitors WHERE campaign_id = ?')->execute([$id]);
            go('p=campaign&id=' . $id . '&tab=people', 'All timers in this campaign were cleared.');

        case 'clear_mine':
            $c = dl_campaign($id) or go();
            $token = $_COOKIE[dl_cookie_name($c)] ?? '';
            $db->prepare('DELETE FROM visitors WHERE campaign_id = ? AND token = ?')->execute([$id, $token]);
            go('p=campaign&id=' . $id . '&tab=people', 'Your browser\'s timer was cleared. Reload a page with this campaign to start fresh.');

        case 'save_settings':
            $tag = post('merge_tag') ?: DL_DEFAULT_MERGE_TAG;
            dl_set_setting('merge_tag', $tag);
            $pw = (string) ($_POST['new_password'] ?? '');
            if ($pw !== '') {
                if (strlen($pw) < 10) go('p=settings', 'Use at least 10 characters for the new password.');
                if (!password_verify((string) ($_POST['current_password'] ?? ''), (string) dl_setting('admin_hash'))) {
                    go('p=settings', 'Your current password didn\'t match.');
                }
                dl_set_password($pw);
            }
            go('p=settings', 'Settings saved.');
    }
    go();
}

// ---------- views ----------

$page = $_GET['p'] ?? 'campaigns';

function head(string $title): void
{
    global $flash, $appUrl;
    $csrf = dl_csrf();
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= dl_h($title) ?> · Deadline Timers</title>
  <link rel="stylesheet" href="/brand/styles.css">
  <link rel="stylesheet" href="<?= dl_h($appUrl) ?>admin.css">
</head>
<body>
  <header class="top">
    <div class="wrap top-in">
      <a class="brand" href="<?= dl_h($appUrl) ?>"><img src="/brand/assets/logo-maroon.png" alt="Emily Diaz, SLP"><span>deadline timers</span></a>
      <?php if (dl_is_admin()): ?>
      <nav>
        <a href="<?= dl_h($appUrl) ?>">campaigns</a>
        <a href="<?= dl_h($appUrl) ?>demo/">demo funnel</a>
        <a href="<?= dl_h($appUrl) ?>?p=settings">settings</a>
        <form method="post"><input type="hidden" name="csrf" value="<?= dl_h($csrf) ?>"><button name="action" value="logout" class="linkish">log out</button></form>
      </nav>
      <?php endif; ?>
    </div>
  </header>
  <div class="db-warning" id="db-warning" hidden>
    <div class="wrap">Your tracking data can be downloaded from the web. The server isn't applying <code>data/.htaccess</code>. Don't share links to this campaign until that's fixed.</div>
  </div>
  <main class="wrap">
    <?php if ($flash): ?><div class="flash" role="status"><?= dl_h($flash) ?></div><?php endif; ?>
<?php
}

function foot(): void
{
    global $appUrl;
    ?>
  </main>
  <?php if (dl_is_admin()): ?>
  <script>
    // Warn if the SQLite file is publicly reachable (for example, if .htaccess is ignored).
    fetch(<?= json_encode($appUrl . 'data/deadlines.sqlite') ?>, { method: 'HEAD', cache: 'no-store' })
      .then(function (r) { if (r.ok) document.getElementById('db-warning').hidden = false; });
    document.querySelectorAll('[data-copy]').forEach(function (b) {
      b.addEventListener('click', function () {
        var src = document.getElementById(b.getAttribute('data-copy'));
        navigator.clipboard.writeText(src.value || src.textContent).then(function () {
          var t = b.textContent; b.textContent = 'copied'; setTimeout(function () { b.textContent = t; }, 1400);
        });
      });
    });
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
      f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
    });
  </script>
  <?php endif; ?>
</body>
</html>
<?php
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . dl_h(dl_csrf()) . '">';
}

function action_button(string $action, int $id, string $label, string $class = 'btn btn-outline btn-sm', ?string $confirm = null): string
{
    return '<form method="post" class="inline-form"' . ($confirm ? ' data-confirm="' . dl_h($confirm) . '"' : '') . '>'
        . csrf_field() . '<input type="hidden" name="id" value="' . $id . '">'
        . '<button class="' . dl_h($class) . '" name="action" value="' . dl_h($action) . '">' . dl_h($label) . '</button></form>';
}

function embed_code(array $t): string
{
    global $appUrl;
    return '<script src="' . $appUrl . 'timer.js" defer></script>' . "\n" . '<div data-deadline-timer="' . $t['public_key'] . '"></div>';
}

// --- setup + login ---

if (!dl_has_password()) {
    head('Set up');
    ?>
    <section class="card narrow">
      <p class="eyebrow">First-time setup</p>
      <h1>Create your password</h1>
      <p>This password protects your campaigns and the list of people in them. You'll use it every time you log in here.</p>
      <form method="post" class="stack">
        <?= csrf_field() ?>
        <label>Password <input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
        <label>Type it again <input type="password" name="password2" minlength="10" required autocomplete="new-password"></label>
        <button class="btn" name="action" value="setup">create password</button>
      </form>
    </section>
    <?php
    foot();
    exit;
}

if (!dl_is_admin()) {
    head('Log in');
    ?>
    <section class="card narrow">
      <p class="eyebrow">Deadline timers</p>
      <h1>Welcome back</h1>
      <form method="post" class="stack">
        <?= csrf_field() ?>
        <label>Password <input type="password" name="password" required autofocus autocomplete="current-password"></label>
        <button class="btn" name="action" value="login">log in</button>
      </form>
    </section>
    <?php
    foot();
    exit;
}

$db = dl_db();
$now = time();

// --- campaigns list ---

if ($page === 'campaigns') {
    $rows = $db->query('SELECT c.*,
            (SELECT COUNT(*) FROM visitors v WHERE v.campaign_id = c.id) AS people,
            (SELECT COUNT(*) FROM visitors v WHERE v.campaign_id = c.id AND v.email IS NOT NULL) AS emails
        FROM campaigns c ORDER BY c.archived, c.created_at DESC')->fetchAll();
    head('Campaigns');
    ?>
    <div class="page-head">
      <div>
        <p class="eyebrow">Deadline timers</p>
        <h1>Campaigns</h1>
      </div>
      <form method="post" class="row">
        <?= csrf_field() ?>
        <input name="name" placeholder="New campaign name, e.g. AAC Handbook fast-action" aria-label="New campaign name" required>
        <button class="btn" name="action" value="create_campaign">create campaign</button>
      </form>
    </div>

    <?php if (!$rows): ?>
      <section class="card empty">
        <p class="script big">let's make your first deadline</p>
        <p>A campaign holds one offer's deadline and the timers that show it. Create one above, or start from a ready-made demo to see how a freebie funnel works.</p>
        <form method="post"><?= csrf_field() ?><button class="btn btn-outline" name="action" value="create_demo">create the demo campaign</button></form>
      </section>
    <?php endif; ?>

    <?php if ($rows):
        $sections = [
            'evergreen' => ['Evergreen timers', 'Each person gets their own countdown from their first visit.'],
            'fixed' => ['Fixed-date timers', 'Everyone counts down to the same date and time.'],
        ];
        foreach ($sections as $mode => [$title, $blurb]):
            $list = array_filter($rows, fn ($c) => $c['mode'] === $mode);
    ?>
      <section class="campaign-section">
        <div class="section-head">
          <h2><?= $title ?> <span class="count"><?= count($list) ?></span></h2>
          <p class="muted"><?= $blurb ?></p>
        </div>
        <?php if (!$list): ?>
          <p class="muted empty-section">No <?= $mode === 'fixed' ? 'fixed-date' : 'evergreen' ?> campaigns yet. Create one above, then pick <strong><?= $mode === 'fixed' ? 'Fixed date' : 'Evergreen' ?></strong> in its deadline settings.</p>
        <?php else: ?>
        <div class="grid">
          <?php foreach ($list as $c): ?>
            <a class="card campaign-card<?= $c['archived'] ? ' is-archived' : '' ?>" href="?p=campaign&amp;id=<?= (int) $c['id'] ?>">
              <?php if ($c['archived'] || $mode === 'fixed'): ?>
              <div class="row">
                <?php if ($c['archived']): ?><span class="badge badge-muted">paused</span><?php endif; ?>
                <?php if ($mode === 'fixed'): ?>
                  <span class="badge <?= $c['fixed_deadline'] && $c['fixed_deadline'] <= $now ? 'badge-muted' : 'badge-live' ?>"><?= $c['fixed_deadline'] && $c['fixed_deadline'] <= $now ? 'ended' : ($c['fixed_deadline'] ? fmt_left((int) $c['fixed_deadline'] - $now) : 'no date set') ?></span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <h3><?= dl_h($c['name']) ?></h3>
              <p class="muted"><?= dl_h(campaign_summary($c)) ?></p>
              <p class="stats"><strong><?= (int) $c['people'] ?></strong> people · <strong><?= (int) $c['emails'] ?></strong> with email</p>
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </section>
    <?php endforeach; endif; ?>

    <?php if ($rows && !dl_campaign_by_slug('demo')): ?>
      <section class="card row-between">
        <div>
          <h2>Practice funnel</h2>
          <p class="muted">The <a href="demo/">demo pages</a> need the demo campaign. Create it to click through a freebie funnel without touching your real campaigns.</p>
        </div>
        <form method="post"><?= csrf_field() ?><button class="btn btn-outline btn-sm" name="action" value="create_demo">create the demo campaign</button></form>
      </section>
    <?php endif; ?>
    <?php
    foot();
    exit;
}

// --- one campaign ---

if ($page === 'campaign') {
    $c = dl_campaign((int) ($_GET['id'] ?? 0)) or go();
    $cid = (int) $c['id'];
    $tz = $c['timezone'];
    $tabs = ['deadline' => 'deadline', 'timers' => 'timers', 'links' => 'email links', 'people' => 'people'];
    $tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'deadline';
    $timers = dl_campaign_timers($cid);
    $search = trim((string) ($_GET['q'] ?? ''));
    $sql = 'SELECT * FROM visitors WHERE campaign_id = ?';
    $args = [$cid];
    if ($search !== '') {
        $sql .= ' AND email LIKE ?';
        $args[] = '%' . $search . '%';
    }
    $st = $db->prepare($sql . ' ORDER BY last_seen DESC LIMIT 200');
    $st->execute($args);
    $people = $st->fetchAll();
    $counts = $db->prepare('SELECT COUNT(*) total, SUM(email IS NOT NULL) emails FROM visitors WHERE campaign_id = ?');
    $counts->execute([$cid]);
    $counts = $counts->fetch();
    $mins = (int) $c['duration_minutes'];
    $myToken = $_COOKIE[dl_cookie_name($c)] ?? '';
    $tabUrl = fn (string $t): string => '?p=campaign&amp;id=' . $cid . '&amp;tab=' . $t;

    head($c['name']);
    ?>
    <p class="crumbs"><a href="?">campaigns</a> /</p>
    <div class="page-head">
      <div>
        <p class="eyebrow"><?= $c['mode'] === 'fixed' ? 'Fixed-date campaign' : 'Evergreen campaign' ?><?= $c['archived'] ? ' · paused' : '' ?></p>
        <h1><?= dl_h($c['name']) ?></h1>
        <p class="muted"><?= dl_h(campaign_summary($c)) ?></p>
      </div>
      <div class="row">
        <?= action_button('duplicate_campaign', $cid, 'duplicate') ?>
        <?= action_button('archive_campaign', $cid, $c['archived'] ? 'resume' : 'pause', 'btn btn-outline btn-sm', $c['archived'] ? null : 'Pause this campaign? Its timers stop showing on your pages until you resume it.') ?>
      </div>
    </div>

    <div class="tabs" role="tablist" aria-label="Campaign sections">
      <?php foreach ($tabs as $key => $label): ?>
        <a role="tab" id="tab-<?= $key ?>" data-tab="<?= $key ?>" aria-controls="panel-<?= $key ?>" href="<?= $tabUrl($key) ?>" aria-selected="<?= $key === $tab ? 'true' : 'false' ?>" tabindex="<?= $key === $tab ? '0' : '-1' ?>"><?= $label ?><?php
          if ($key === 'timers') echo ' <span class="count">' . count($timers) . '</span>';
          if ($key === 'people') echo ' <span class="count">' . (int) $counts['total'] . '</span>';
        ?></a>
      <?php endforeach; ?>
    </div>

    <div class="tab-panel" role="tabpanel" id="panel-deadline" aria-labelledby="tab-deadline"<?= $tab !== 'deadline' ? ' hidden' : '' ?>>
      <section class="card">
        <form method="post" class="stack" id="campaign-form">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $cid ?>">
          <div class="cols">
            <label>Campaign name <input name="name" value="<?= dl_h($c['name']) ?>" required></label>
            <label>Short name <span class="hint">used in show/hide code</span><input name="slug" value="<?= dl_h($c['slug']) ?>" pattern="[a-z0-9-]+" required></label>
          </div>

          <div class="form-cols">
            <div class="stack">
              <h2>When it ends</h2>
              <fieldset class="choice">
                <legend>Type of deadline</legend>
                <label class="radio"><input type="radio" name="mode" value="evergreen" <?= $c['mode'] === 'evergreen' ? 'checked' : '' ?>>
                  <span><strong>Evergreen</strong> Each person gets their own countdown, starting the first time they see a timer.</span></label>
                <label class="radio"><input type="radio" name="mode" value="fixed" <?= $c['mode'] === 'fixed' ? 'checked' : '' ?>>
                  <span><strong>Fixed date</strong> Everyone counts down to the same date and time.</span></label>
              </fieldset>

              <div data-mode="evergreen">
                <p class="label">Countdown length</p>
                <div class="cols three">
                  <label>Days <input type="number" name="days" min="0" value="<?= intdiv($mins, 1440) ?>"></label>
                  <label>Hours <input type="number" name="hours" min="0" max="23" value="<?= intdiv($mins % 1440, 60) ?>"></label>
                  <label>Minutes <input type="number" name="minutes" min="0" max="59" value="<?= $mins % 60 ?>"></label>
                </div>
              </div>
              <div data-mode="fixed">
                <label>Ends on <input type="datetime-local" name="fixed_deadline" value="<?= dl_h(local_input($c['fixed_deadline'] ? (int) $c['fixed_deadline'] : null, $tz)) ?>"></label>
              </div>
              <label>Time zone
                <select name="timezone">
                  <?php foreach (DateTimeZone::listIdentifiers() as $zone): ?>
                    <option <?= $zone === $tz ? 'selected' : '' ?>><?= dl_h($zone) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>

            <div class="stack">
              <h2>After it ends</h2>
              <fieldset class="choice">
                <legend>When the deadline passes</legend>
                <label class="radio"><input type="radio" name="expired_action" value="message" <?= $c['expired_action'] === 'message' ? 'checked' : '' ?>><span><strong>Show a message</strong> in place of the countdown</span></label>
                <label class="radio"><input type="radio" name="expired_action" value="hide" <?= $c['expired_action'] === 'hide' ? 'checked' : '' ?>><span><strong>Hide the timers</strong></span></label>
                <label class="radio"><input type="radio" name="expired_action" value="redirect" <?= $c['expired_action'] === 'redirect' ? 'checked' : '' ?>><span><strong>Send them to another page</strong>, like your full-price page</span></label>
              </fieldset>
              <label data-expired="message">Message <input name="expired_message" value="<?= dl_h($c['expired_message']) ?>"></label>
              <label data-expired="redirect">Page to send them to <input name="redirect_url" value="<?= dl_h($c['redirect_url']) ?>" placeholder="/landing-pages/aac-handbook/"></label>

              <label>Reset a person's timer <span class="hint">days after their deadline passes · 0 = never</span>
                <input type="number" name="reset_days" min="0" value="<?= (int) $c['reset_days'] ?>">
              </label>
              <p class="hint">After this many days, someone who comes back gets a brand-new countdown, as if they were new. Their browser cookie lasts this long too. Leave it at 0 if the discount should never come back for them.</p>
            </div>
          </div>

          <button class="btn" name="action" value="save_campaign">save deadline</button>
        </form>
      </section>

      <section class="card danger-zone">
        <h2>Delete campaign</h2>
        <p>Deletes the campaign, its timers and everyone tracked in it. Pages with its embed codes will show nothing.</p>
        <?= action_button('delete_campaign', $cid, 'delete campaign', 'btn btn-outline btn-sm danger', 'Delete "' . $c['name'] . '" and everyone tracked in it? This can\'t be undone.') ?>
      </section>

      <script>
        (function () {
          var form = document.getElementById('campaign-form');
          function sync() {
            var mode = form.querySelector('[name=mode]:checked').value;
            var exp = form.querySelector('[name=expired_action]:checked').value;
            form.querySelectorAll('[data-mode]').forEach(function (n) { n.hidden = n.getAttribute('data-mode') !== mode; });
            form.querySelectorAll('[data-expired]').forEach(function (n) { n.hidden = n.getAttribute('data-expired') !== exp; });
          }
          form.addEventListener('change', sync);
          sync();
        })();
      </script>
    </div>

    <div class="tab-panel" role="tabpanel" id="panel-timers" aria-labelledby="tab-timers"<?= $tab !== 'timers' ? ' hidden' : '' ?>>
      <section class="card">
        <div class="row-between">
          <h2>Timers</h2>
          <div class="row">
            <?php foreach (['bar' => '+ announcement bar', 'inline' => '+ inline timer'] as $type => $label): ?>
              <form method="post" class="inline-form">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $cid ?>"><input type="hidden" name="type" value="<?= $type ?>">
                <button class="btn btn-outline btn-sm" name="action" value="create_timer"><?= $label ?></button>
              </form>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if (!$timers): ?><p class="muted">No timers yet. Add an announcement bar or an inline timer.</p><?php endif; ?>
        <ul class="timer-list">
          <?php foreach ($timers as $t): ?>
            <li>
              <div class="row-between">
                <div>
                  <span class="badge"><?= $t['type'] === 'bar' ? 'announcement bar' : 'inline' ?></span>
                  <strong><?= dl_h($t['name']) ?></strong>
                </div>
                <a class="btn btn-sm" href="?p=timer&amp;id=<?= (int) $t['id'] ?>">edit</a>
              </div>
              <textarea id="embed-<?= (int) $t['id'] ?>" class="code" rows="2" readonly><?= dl_h(embed_code($t)) ?></textarea>
              <button type="button" class="linkish" data-copy="embed-<?= (int) $t['id'] ?>">copy embed code</button>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="hint">Paste the embed code into any page on lab.emilydiazslp.com. With several timers on one page, the <code>&lt;script&gt;</code> line is needed only once.</p>
      </section>

      <section class="card">
        <h2>Show or hide content</h2>
        <p>Wrap any button or section so it appears only before, or only after, the deadline.</p>
        <textarea id="showhide" class="code" rows="3" readonly><?= dl_h('<a href="CHECKOUT-DISCOUNT-LINK" data-deadline-campaign="' . $c['slug'] . '" data-deadline-show="active">claim my discount</a>' . "\n" . '<a href="CHECKOUT-FULL-PRICE-LINK" data-deadline-campaign="' . $c['slug'] . '" data-deadline-show="expired">enroll now</a>') ?></textarea>
        <button type="button" class="linkish" data-copy="showhide">copy code</button>
      </section>
    </div>

    <div class="tab-panel" role="tabpanel" id="panel-links" aria-labelledby="tab-links"<?= $tab !== 'links' ? ' hidden' : '' ?>>
      <section class="card stack">
        <h2>Email links</h2>
        <p>Add your subscriber's email to every link in your Flodesk emails. That's what keeps their deadline the same on every device.</p>
        <label>Page link <input id="link-in" placeholder="https://lab.emilydiazslp.com/landing-pages/aac-handbook/"></label>
        <label>Use this in Flodesk <textarea id="link-out" class="code" rows="2" readonly></textarea></label>
        <button type="button" class="linkish" data-copy="link-out">copy link</button>
        <p class="hint">The email tag is <code><?= dl_h(merge_tag()) ?></code>. Send yourself a test email and check that the link shows your address. You can change the tag in <a href="?p=settings">settings</a>.</p>
      </section>
      <script>
        (function () {
          var tag = <?= json_encode(merge_tag()) ?>;
          var input = document.getElementById('link-in'), out = document.getElementById('link-out');
          input.addEventListener('input', function () {
            var u = input.value.trim(), hash = '', i = u.indexOf('#');
            if (!u) { out.value = ''; return; }
            if (i >= 0) { hash = u.slice(i); u = u.slice(0, i); }
            out.value = u + (u.indexOf('?') >= 0 ? '&' : '?') + 'email=' + tag + hash;
          });
        })();
      </script>
    </div>

    <div class="tab-panel" role="tabpanel" id="panel-people" aria-labelledby="tab-people"<?= $tab !== 'people' ? ' hidden' : '' ?>>
      <section class="card">
        <div class="row-between">
          <div>
            <h2>People</h2>
            <p class="muted"><?= (int) $counts['total'] ?> tracked · <?= (int) $counts['emails'] ?> with email</p>
          </div>
          <div class="row">
            <form class="row" method="get">
              <input type="hidden" name="p" value="campaign"><input type="hidden" name="id" value="<?= $cid ?>"><input type="hidden" name="tab" value="people">
              <input type="search" name="q" value="<?= dl_h($search) ?>" placeholder="search by email" aria-label="Search by email">
            </form>
            <?= action_button('clear_campaign', $cid, 'clear all timers', 'btn btn-outline btn-sm danger', 'Clear every timer in this campaign? Everyone gets a fresh deadline on their next visit. This can\'t be undone.') ?>
          </div>
        </div>
        <?php if (!$people): ?>
          <p class="muted"><?= $search !== '' ? 'Nobody matches that search.' : 'Nobody yet. People appear here the first time they load a page with this campaign\'s timer.' ?></p>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Person</th><th>Started</th><th>Deadline</th><th>Status</th><th>Visits</th><th>Last seen</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($people as $v):
                $deadline = dl_visitor_deadline($c, $v);
                $left = $deadline - $now;
                $status = $left > 0 ? fmt_left($left) : (dl_reset_due($c, $v, $now) ? 'resets on next visit' : 'expired');
            ?>
              <tr>
                <td><a href="?p=person&amp;id=<?= (int) $v['id'] ?>"><?= $v['email'] ? dl_h($v['email']) : '<span class="muted">no email yet</span>' ?></a><?= $v['token'] === $myToken ? ' <span class="badge">you</span>' : '' ?></td>
                <td><?= dl_h(fmt_time((int) $v['started_at'], $tz)) ?></td>
                <td><?= dl_h(fmt_time($deadline, $tz)) ?></td>
                <td><span class="badge <?= $left > 0 ? 'badge-live' : 'badge-muted' ?>"><?= dl_h($status) ?></span></td>
                <td><?= (int) $v['visits'] ?></td>
                <td><?= dl_h(fmt_time((int) $v['last_seen'], $tz)) ?></td>
                <td><?= action_button('clear_person', (int) $v['id'], 'clear', 'linkish danger', 'Clear this person\'s timer? They get a fresh deadline on their next visit.') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </section>

      <section class="card">
        <h2>Test it</h2>
        <p>Add <code>?dl_reset=1</code> to any page with this campaign's timers to restart your own countdown. It only works while you're logged in here.</p>
        <p class="muted">Your browser: <?php
          $mine = $myToken ? dl_find_visitor('token', $cid, $myToken) : null;
          echo $mine ? dl_h(fmt_left(dl_visitor_deadline($c, $mine) - $now)) . ($mine['email'] ? ' · ' . dl_h($mine['email']) : '') : 'no timer yet';
        ?></p>
        <?= action_button('clear_mine', $cid, 'clear my timer') ?>
      </section>
    </div>

    <script>
      // Switch tabs without reloading, so unsaved edits stay put. ?tab= keeps links and redirects working.
      (function () {
        var tabs = Array.prototype.slice.call(document.querySelectorAll('.tabs [role=tab]'));
        function select(tab, focus) {
          tabs.forEach(function (t) {
            var on = t === tab;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
          });
          if (focus) tab.focus();
          var url = new URL(location.href);
          url.searchParams.set('tab', tab.getAttribute('data-tab'));
          url.searchParams.delete('q');
          history.replaceState(null, '', url.href);
        }
        tabs.forEach(function (t, i) {
          t.addEventListener('click', function (e) { e.preventDefault(); select(t); });
          t.addEventListener('keydown', function (e) {
            var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
            if (d) { e.preventDefault(); select(tabs[(i + d + tabs.length) % tabs.length], true); }
          });
        });
      })();
    </script>
    <?php
    foot();
    exit;
}

// --- one timer ---

if ($page === 'timer') {
    $st = $db->prepare('SELECT * FROM timers WHERE id = ?');
    $st->execute([(int) ($_GET['id'] ?? 0)]);
    $t = $st->fetch() or go();
    $c = dl_campaign((int) $t['campaign_id']);
    $s = dl_timer_settings($t);
    $isBar = $t['type'] === 'bar';

    $color = function (string $key, string $label) use ($s): string {
        return '<label class="color">' . dl_h($label) . '<input type="color" name="' . $key . '" value="' . dl_h($s[$key]) . '" list="brand-colors"></label>';
    };

    $range = function (string $key, string $label, string $hint = '') use ($s): string {
        [$min, $max] = DL_SIZE_RANGES[$key];
        return '<label class="range">' . dl_h($label) . ($hint ? ' <span class="hint">' . dl_h($hint) . '</span>' : '')
            . '<span class="range-row"><input type="range" name="' . $key . '" min="' . $min . '" max="' . $max . '" value="' . (int) $s[$key] . '">'
            . '<output>' . (int) $s[$key] . 'px</output></span></label>';
    };

    head($t['name']);
    ?>
    <p class="crumbs"><a href="?">campaigns</a> / <a href="?p=campaign&amp;id=<?= (int) $c['id'] ?>&amp;tab=timers"><?= dl_h($c['name']) ?></a> /</p>
    <div class="page-head">
      <div>
        <p class="eyebrow"><?= $isBar ? 'Announcement bar' : 'Inline timer' ?></p>
        <h1><?= dl_h($t['name']) ?></h1>
      </div>
    </div>

    <datalist id="brand-colors"><?php foreach (DL_BRAND_COLORS as $hex): ?><option value="<?= $hex ?>"><?php endforeach; ?></datalist>

    <section class="card preview-card">
      <div class="row-between">
        <h2>Preview</h2>
        <label class="check"><input type="checkbox" id="show-expired"> show the expired state</label>
      </div>
      <div class="preview-stage <?= $isBar ? 'is-bar' : '' ?>"><div id="preview"></div></div>
    </section>

    <form method="post" class="stack-lg" id="timer-form">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
      <div class="two-col">
      <section class="card stack">
        <h2>Words</h2>
        <label>Timer name <span class="hint">only you see this</span><input name="name" value="<?= dl_h($t['name']) ?>"></label>
        <label>Headline <input name="headline" value="<?= dl_h($s['headline']) ?>"></label>
        <label>Expired message <span class="hint">blank uses the campaign's message</span><input name="expired_text" value="<?= dl_h($s['expired_text']) ?>" placeholder="<?= dl_h($c['expired_message']) ?>"></label>
        <div class="cols">
          <label>Button label <span class="hint">optional</span><input name="button_text" value="<?= dl_h($s['button_text']) ?>" placeholder="claim my discount"></label>
          <label>Button link <input name="button_url" value="<?= dl_h($s['button_url']) ?>" placeholder="/landing-pages/aac-handbook/#buy"></label>
        </div>
        <div class="row">
          <label class="check"><input type="checkbox" name="show_days" <?= $s['show_days'] ? 'checked' : '' ?>> show days</label>
          <label class="check"><input type="checkbox" name="show_seconds" <?= $s['show_seconds'] ? 'checked' : '' ?>> show seconds</label>
        </div>
        <?php if ($isBar): ?>
          <fieldset class="choice inline-choice">
            <legend>Position</legend>
            <label class="radio"><input type="radio" name="position" value="top" <?= $s['position'] === 'top' ? 'checked' : '' ?>><span>top of the page</span></label>
            <label class="radio"><input type="radio" name="position" value="bottom" <?= $s['position'] === 'bottom' ? 'checked' : '' ?>><span>bottom of the page</span></label>
          </fieldset>
        <?php else: ?>
          <div class="cols">
            <label>Alignment
              <select name="align"><?php foreach (['center' => 'centered', 'left' => 'left'] as $k => $l): ?><option value="<?= $k ?>" <?= $s['align'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
            </label>
          </div>
        <?php endif; ?>
      </section>
      <div class="stack-lg">
      <section class="card stack">
        <h2>Fonts &amp; sizes</h2>
        <div class="cols">
          <label>Text font <input name="font" list="google-fonts" value="<?= dl_h($s['font']) ?>" placeholder="the page's font" autocomplete="off"></label>
          <label>Number font <input name="digit_font" list="google-fonts" value="<?= dl_h($s['digit_font']) ?>" placeholder="same as text" autocomplete="off"></label>
        </div>
        <p class="hint">Pick from the list or type any font name from <a href="https://fonts.google.com" target="_blank" rel="noopener">Google Fonts</a>, spelled the way it's shown there.</p>
        <datalist id="google-fonts"></datalist>
        <?= $range('head_size', 'Headline size') ?>
        <?= $range('digit_size', 'Number size') ?>
        <?= $range('label_size', 'Label size', 'days, hours, minutes') ?>
        <?= $range('pad_y', $isBar ? 'Bar height' : 'Timer height', 'space above and below') ?>
        <?= $range('box_pad', 'Number box height') ?>
        <button type="button" class="linkish" id="reset-sizes">reset sizes</button>
      </section>
      <section class="card stack">
        <h2>Colors</h2>
        <div class="color-grid">
          <?= $color('bg', 'Background') ?>
          <?= $color('fg', 'Text') ?>
          <?= $color('digit_bg', 'Number boxes') ?>
          <?= $color('digit_fg', 'Numbers') ?>
          <?= $color('button_bg', 'Button') ?>
          <?= $color('button_fg', 'Button text') ?>
        </div>
        <p class="hint">The color pickers suggest the brand colors first.</p>
      </section>
      </div>
      </div>
      <section class="card stack">
        <h2>Embed code</h2>
        <textarea id="embed" class="code" rows="2" readonly><?= dl_h(embed_code($t)) ?></textarea>
        <button type="button" class="linkish" data-copy="embed">copy embed code</button>
      </section>
      <div class="card save-bar row-between">
        <span class="muted">Changes show on your pages after you save.</span>
        <button class="btn" name="action" value="save_timer">save timer</button>
      </div>
    </form>
    <div class="row end">
      <?= action_button('delete_timer', (int) $t['id'], 'delete timer', 'btn btn-outline btn-sm danger', 'Delete this timer? Pages using its embed code will show nothing.') ?>
    </div>

    <script src="<?= dl_h($appUrl) ?>timer.js" data-preview></script>
    <script>
      (function () {
        var form = document.getElementById('timer-form');
        var type = <?= json_encode($t['type']) ?>;
        var campaignMsg = <?= json_encode($c['expired_message']) ?>;
        var expired = document.getElementById('show-expired');
        var sample = 2 * 86400 + 7 * 3600 + 41 * 60 + 9;
        var view, timer;
        function settings() {
          var s = {};
          form.querySelectorAll('input, select').forEach(function (n) {
            if (!n.name || n.name === 'csrf' || n.name === 'id' || n.name === 'name') return;
            if (n.type === 'checkbox') s[n.name] = n.checked;
            else if (n.type === 'radio') { if (n.checked) s[n.name] = n.value; }
            else s[n.name] = n.value;
          });
          return s;
        }
        function render() {
          clearInterval(timer);
          var s = settings(), left = sample;
          var msg = s.expired_text || campaignMsg;
          view = DeadlineTimers.preview(document.getElementById('preview'), type, s, expired.checked ? 0 : left, msg);
          if (!expired.checked) timer = setInterval(function () { view.update(--left); }, 1000);
        }
        var list = document.getElementById('google-fonts');
        DeadlineTimers.fonts.forEach(function (f) {
          var o = document.createElement('option');
          o.value = f;
          list.appendChild(o);
        });
        function syncOutputs() {
          form.querySelectorAll('input[type=range]').forEach(function (r) {
            r.nextElementSibling.textContent = r.value + 'px';
          });
        }
        var defaults = <?= json_encode(array_intersect_key(dl_timer_defaults($t['type']), DL_SIZE_RANGES)) ?>;
        document.getElementById('reset-sizes').addEventListener('click', function () {
          Object.keys(defaults).forEach(function (k) { form.elements[k].value = defaults[k]; });
          syncOutputs();
          render();
        });
        form.addEventListener('input', function () { syncOutputs(); render(); });
        form.addEventListener('change', render);
        expired.addEventListener('change', render);
        render();
      })();
    </script>
    <?php
    foot();
    exit;
}

// --- one person ---

if ($page === 'person') {
    $st = $db->prepare('SELECT * FROM visitors WHERE id = ?');
    $st->execute([(int) ($_GET['id'] ?? 0)]);
    $v = $st->fetch() or go();
    $c = dl_campaign((int) $v['campaign_id']);
    $tz = $c['timezone'];
    $hits = $db->prepare('SELECT * FROM hits WHERE visitor_id = ? ORDER BY at DESC LIMIT 300');
    $hits->execute([$v['id']]);
    $deadline = dl_visitor_deadline($c, $v);
    $viaLabels = ['new' => 'started their timer', 'email' => 'from an email link', 'cookie' => 'same browser', 'reset' => 'timer reset'];

    head($v['email'] ?: 'Visitor');
    ?>
    <p class="crumbs"><a href="?">campaigns</a> / <a href="?p=campaign&amp;id=<?= (int) $c['id'] ?>&amp;tab=people"><?= dl_h($c['name']) ?></a> /</p>
    <div class="page-head">
      <div>
        <p class="eyebrow">Person</p>
        <h1><?= $v['email'] ? dl_h($v['email']) : 'No email yet' ?></h1>
        <p class="muted">Started <?= dl_h(fmt_time((int) $v['started_at'], $tz)) ?> · deadline <?= dl_h(fmt_time($deadline, $tz)) ?> · <?= dl_h(fmt_left($deadline - $now)) ?></p>
      </div>
      <?= action_button('clear_person', (int) $v['id'], 'clear their timer', 'btn btn-outline btn-sm danger', 'Clear this person\'s timer? They get a fresh deadline on their next visit.') ?>
    </div>
    <section class="card">
      <h2>Visits</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>When</th><th>Page</th><th>How we knew them</th></tr></thead>
          <tbody>
          <?php foreach ($hits as $h): ?>
            <tr>
              <td><?= dl_h(fmt_time((int) $h['at'], $tz)) ?></td>
              <td class="url"><?= dl_h(preg_replace('#^https?://[^/]+#', '', $h['url']) ?: '/') ?></td>
              <td><?= dl_h($viaLabels[$h['via']] ?? $h['via']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php
    foot();
    exit;
}

// --- settings ---

if ($page === 'settings') {
    head('Settings');
    ?>
    <p class="crumbs"><a href="?">campaigns</a> /</p>
    <div class="page-head"><div><p class="eyebrow">Settings</p><h1>Settings</h1></div></div>
    <form method="post" class="two-col">
      <?= csrf_field() ?>
      <section class="card stack">
        <h2>Flodesk email tag</h2>
        <p>This tag goes in your email links so each subscriber's address is filled in when Flodesk sends the email.</p>
        <label>Email merge tag <input name="merge_tag" value="<?= dl_h(merge_tag()) ?>"></label>
        <p class="hint">If a test email shows the tag itself instead of your address, copy the exact email tag from Flodesk's personalization menu and paste it here.</p>
      </section>
      <section class="card stack">
        <h2>Change password</h2>
        <label>Current password <input type="password" name="current_password" autocomplete="current-password"></label>
        <label>New password <input type="password" name="new_password" minlength="10" autocomplete="new-password"></label>
        <p class="hint">Leave these blank to keep your password.</p>
      </section>
      <div><button class="btn" name="action" value="save_settings">save settings</button></div>
    </form>
    <?php
    foot();
    exit;
}

go();
