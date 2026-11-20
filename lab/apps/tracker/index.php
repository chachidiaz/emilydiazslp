<?php
// Purchase path tracker dashboard. Phase 1: Overview, Pages, Setup.
// Uses the lab's single admin login (the same one as /admin and the deadline timers app).

declare(strict_types=1);

require_once __DIR__ . '/../deadlines/lib/auth.php';
require_once __DIR__ . '/lib/core.php';

dl_session_start();
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: SAMEORIGIN');

$self = strtok($_SERVER['REQUEST_URI'] ?? '/apps/tracker/', '?');
$appUrl = rtrim(dirname($self . 'x'), '/') . '/';
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $r = dl_login((string) ($_POST['password'] ?? ''));
        if ($r === true) {
            header('Location: ' . $self);
            exit;
        }
        $error = is_string($r) ? $r : 'That password didn’t work.';
    } elseif ($action === 'logout' && dl_is_admin()) {
        dl_check_csrf();
        dl_logout();
        header('Location: ' . $self);
        exit;
    }
}

function secs(?float $s): string
{
    if (!$s) {
        return '—';
    }
    $s = (int) round($s);
    return $s < 60 ? $s . 's' : intdiv($s, 60) . 'm ' . str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT) . 's';
}

function num(int|float|null $n): string
{
    return number_format((float) $n);
}

function day(?int $ts, string $fmt = 'M j, Y'): string
{
    return $ts ? (new DateTime('@' . $ts))->setTimezone(new DateTimeZone(PT_TIMEZONE))->format($fmt) : '—';
}

function site_label(string $site): string
{
    return PT_SITES[$site] ?? $site;
}

function head(string $title): void
{ ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= pt_h($title) ?> · Purchase path tracker</title>
<link rel="stylesheet" href="/brand/styles.css">
<link rel="stylesheet" href="/apps/deadlines/admin.css">
<link rel="stylesheet" href="tracker.css">
</head>
<body>
<?php }

// ---------- login ----------
if (!dl_is_admin()) {
    head('Log in');
    if (!dl_has_password()) { ?>
  <main class="wrap"><div class="card narrow">
    <p class="eyebrow">Purchase path tracker</p>
    <h1>Set a password first</h1>
    <p>The lab apps share one login. Open the <a href="/apps/deadlines/">deadline timers app</a> to set it, then come back here.</p>
  </div></main>
<?php } else { ?>
  <main class="wrap"><form class="card narrow stack" method="post">
    <p class="eyebrow" style="margin:0">Purchase path tracker</p>
    <h1 style="margin:0">Log in</h1>
    <?php if ($error): ?><p class="flash"><?= pt_h($error) ?></p><?php endif; ?>
    <input type="hidden" name="action" value="login">
    <label>Password<input type="password" name="password" required autofocus autocomplete="current-password"></label>
    <button class="btn" type="submit">log in</button>
  </form></main>
<?php }
    echo '</body></html>';
    exit;
}

// ---------- dashboard ----------
$tab = in_array($_GET['p'] ?? '', ['overview', 'pages', 'setup'], true) ? $_GET['p'] : 'overview';
$range = in_array($_GET['range'] ?? '', ['today', '7d', '30d', 'custom'], true) ? $_GET['range'] : '30d';
$site = isset(PT_SITES[$_GET['site'] ?? '']) ? $_GET['site'] : 'all';
[$from, $to, $rangeLabel] = pt_range($range, (string) ($_GET['from'] ?? ''), (string) ($_GET['to'] ?? ''));
$fromInput = day($from, 'Y-m-d');
$toInput = day($to - 1, 'Y-m-d');

function link_to(array $changes): string
{
    global $tab, $range, $site, $fromInput, $toInput;
    $q = array_merge(['p' => $tab, 'range' => $range, 'site' => $site], $changes);
    if ($q['range'] === 'custom') {
        $q += ['from' => $fromInput, 'to' => $toInput];
    }
    if ($q['site'] === 'all') unset($q['site']);
    if ($q['range'] === '30d') unset($q['range']);
    if ($q['p'] === 'overview') unset($q['p']);
    return '?' . http_build_query($q);
}

$snippet = '<script defer src="https://lab.emilydiazslp.com/apps/tracker/p.js"></script>';
head(['overview' => 'Overview', 'pages' => 'Pages', 'setup' => 'Setup'][$tab]);
?>
  <header class="top">
    <div class="wrap top-in">
      <a class="brand" href="<?= pt_h($self) ?>"><img src="/brand/assets/logo-maroon.png" alt="Emily Diaz, SLP"><span>purchase path tracker</span></a>
      <nav>
        <a href="/admin/">lab dashboard</a>
        <form method="post" class="inline-form">
          <input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?= pt_h(dl_csrf()) ?>">
          <button class="linkish" type="submit">log out</button>
        </form>
      </nav>
    </div>
  </header>

  <main class="wrap">
    <div class="page-head">
      <div>
        <p class="eyebrow">Purchase path tracker</p>
        <h1>Where people go before they buy</h1>
      </div>
    </div>

    <nav class="pt-tabs" aria-label="Sections">
      <?php foreach (['overview' => 'overview', 'pages' => 'pages', 'setup' => 'setup'] as $k => $label): ?>
        <a href="<?= pt_h(link_to(['p' => $k])) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
    </nav>

<?php if ($tab !== 'setup'): ?>
    <form class="pt-filters" method="get">
      <?php if ($tab !== 'overview'): ?><input type="hidden" name="p" value="<?= pt_h($tab) ?>"><?php endif; ?>
      <div class="pt-seg" role="group" aria-label="Date range">
        <?php foreach (['today' => 'today', '7d' => '7 days', '30d' => '30 days', 'custom' => 'custom'] as $k => $label): ?>
          <a href="<?= pt_h(link_to(['range' => $k])) ?>"<?= $range === $k ? ' aria-current="true"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
      </div>
      <?php if ($range === 'custom'): ?>
        <input type="hidden" name="range" value="custom">
        <label class="pt-date">from<input type="date" name="from" value="<?= pt_h($fromInput) ?>"></label>
        <label class="pt-date">to<input type="date" name="to" value="<?= pt_h($toInput) ?>"></label>
      <?php else: ?>
        <input type="hidden" name="range" value="<?= pt_h($range) ?>">
      <?php endif; ?>
      <label class="pt-date">site
        <select name="site" onchange="this.form.submit()">
          <option value="all">all sites</option>
          <?php foreach (PT_SITES as $k => $label): ?>
            <option value="<?= $k ?>"<?= $site === $k ? ' selected' : '' ?>><?= pt_h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ($range === 'custom'): ?><button class="btn btn-sm" type="submit">apply</button><?php endif; ?>
      <p class="muted pt-range-label"><?= pt_h($rangeLabel) ?></p>
    </form>
<?php endif; ?>

<?php if ($tab === 'overview'):
    $o = pt_overview($from, $to, $site);
    $daily = pt_daily($from, $to, $site);
    $sources = pt_sources($from, $to, $site);
    $top = array_slice(pt_pages($from, $to, $site, 8), 0, 8);
?>
    <section class="pt-tiles" aria-label="Totals">
      <div class="card pt-tile"><span class="pt-tile-label">Visitors</span><strong><?= num($o['visitors']) ?></strong></div>
      <div class="card pt-tile"><span class="pt-tile-label">Visits</span><strong><?= num($o['sessions']) ?></strong></div>
      <div class="card pt-tile"><span class="pt-tile-label">Pageviews</span><strong><?= num($o['pageviews']) ?></strong></div>
      <div class="card pt-tile"><span class="pt-tile-label">Avg. time on page</span><strong><?= secs($o['avg_seconds']) ?></strong></div>
      <div class="card pt-tile"><span class="pt-tile-label">Goal clicks</span><strong><?= num($o['goals']) ?></strong></div>
    </section>

    <?php if (!$o['pageviews']): ?>
      <div class="card empty">
        <p class="script big">nothing yet.</p>
        <p>No pageviews in this range. If you just added the pixel, open one of those pages in another tab, then refresh this one. The <a href="<?= pt_h(link_to(['p' => 'setup'])) ?>">setup tab</a> shows each event as it comes in.</p>
      </div>
    <?php else: ?>
      <?php if (count($daily) > 1):
          $max = max(1, max(array_column($daily, 'pageviews')));
          $n = count($daily);
          $w = 1000; $h = 220; $padB = 24; $gap = 2;
          $bw = ($w - $gap * ($n - 1)) / $n;
          $tickEvery = $n > 14 ? 7 : 1;
      ?>
      <section class="card">
        <div class="row-between"><h2>Pageviews by day</h2><p class="muted" style="margin:0">peak <?= num($max) ?></p></div>
        <div class="pt-chart">
          <svg viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="none" role="img" aria-label="Daily pageviews, <?= pt_h($rangeLabel) ?>">
            <line x1="0" x2="<?= $w ?>" y1="<?= $h - $padB ?>" y2="<?= $h - $padB ?>" class="pt-axis"/>
            <?php foreach ($daily as $i => $d):
                $bh = $d['pageviews'] ? max(3, ($h - $padB - 8) * $d['pageviews'] / $max) : 0;
                $x = $i * ($bw + $gap);
                $y = $h - $padB - $bh;
                $label = (new DateTime($d['day']))->format('D, M j');
            ?>
              <g class="pt-bar" data-tip="<?= pt_h($label . ' · ' . num($d['pageviews']) . ' pageviews · ' . num($d['visitors']) . ' visitors') ?>">
                <rect class="pt-hit" x="<?= round($x, 2) ?>" y="0" width="<?= round($bw + $gap, 2) ?>" height="<?= $h - $padB ?>"/>
                <?php if ($bh): ?><path d="M<?= round($x, 2) ?>,<?= $h - $padB ?> V<?= round($y + min(4, $bh), 2) ?> Q<?= round($x, 2) ?>,<?= round($y, 2) ?> <?= round($x + min(4, $bw / 2), 2) ?>,<?= round($y, 2) ?> H<?= round($x + $bw - min(4, $bw / 2), 2) ?> Q<?= round($x + $bw, 2) ?>,<?= round($y, 2) ?> <?= round($x + $bw, 2) ?>,<?= round($y + min(4, $bh), 2) ?> V<?= $h - $padB ?> Z"/><?php endif; ?>
              </g>
            <?php endforeach; ?>
          </svg>
          <div class="pt-ticks">
            <?php foreach ($daily as $i => $d): if ($i % $tickEvery) continue; ?>
              <span style="left: <?= round(($i * ($bw + $gap) + $bw / 2) / $w * 100, 3) ?>%"><?= (new DateTime($d['day']))->format('M j') ?></span>
            <?php endforeach; ?>
          </div>
          <div class="pt-tip" hidden></div>
        </div>
        <details class="pt-table-toggle">
          <summary>show as a table</summary>
          <div class="table-wrap"><table>
            <thead><tr><th>Day</th><th class="num">Pageviews</th><th class="num">Visitors</th></tr></thead>
            <tbody><?php foreach (array_reverse($daily) as $d): ?><tr><td><?= (new DateTime($d['day']))->format('D, M j') ?></td><td class="num"><?= num($d['pageviews']) ?></td><td class="num"><?= num($d['visitors']) ?></td></tr><?php endforeach; ?></tbody>
          </table></div>
        </details>
      </section>
      <?php endif; ?>

      <div class="two-col">
        <section class="card">
          <div class="row-between"><h2>Top pages</h2><a href="<?= pt_h(link_to(['p' => 'pages'])) ?>">all pages</a></div>
          <div class="table-wrap"><table>
            <thead><tr><th>Page</th><th class="num">Views</th><th class="num">Avg. time</th></tr></thead>
            <tbody><?php foreach ($top as $r): ?>
              <tr><td class="url"><span class="pt-site"><?= pt_h(site_label($r['site'])) ?></span><?= pt_h($r['path']) ?></td><td class="num"><?= num($r['views']) ?></td><td class="num"><?= secs($r['avg_seconds']) ?></td></tr>
            <?php endforeach; ?></tbody>
          </table></div>
        </section>
        <section class="card">
          <h2>Where visitors came from</h2>
          <div class="table-wrap"><table>
            <thead><tr><th>Source</th><th class="num">Visitors</th><th class="num">Views</th></tr></thead>
            <tbody><?php foreach ($sources as $r): ?>
              <tr><td><?= pt_h($r['source']) ?></td><td class="num"><?= num($r['visitors']) ?></td><td class="num"><?= num($r['views']) ?></td></tr>
            <?php endforeach; ?></tbody>
          </table></div>
          <p class="hint">UTM source when the link had one, otherwise the referring site.</p>
        </section>
      </div>
    <?php endif; ?>

<?php elseif ($tab === 'pages'):
    $rows = pt_pages($from, $to, $site);
?>
    <section class="card">
      <div class="row-between">
        <h2><?= num(count($rows)) ?> page<?= count($rows) === 1 ? '' : 's' ?> with traffic</h2>
        <input type="search" class="pt-search" placeholder="filter pages" aria-label="Filter pages" data-filter="#pages-table">
      </div>
      <?php if (!$rows): ?>
        <p class="muted">No pageviews in this range.</p>
      <?php else: ?>
      <div class="table-wrap"><table id="pages-table" class="pt-sortable">
        <thead><tr>
          <th data-sort="text">Page</th>
          <th class="num" data-sort="num" aria-sort="descending">Views</th>
          <th class="num" data-sort="num">Visitors</th>
          <th class="num" data-sort="num">Avg. time on page</th>
          <th class="num" data-sort="num">Avg. scroll</th>
          <th data-sort="num">First seen</th>
          <th data-sort="num">Last seen</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="url" data-v="<?= pt_h($r['site'] . $r['path']) ?>">
              <span class="pt-site"><?= pt_h(site_label($r['site'])) ?></span><?= pt_h($r['path']) ?>
              <?php if ($r['title']): ?><span class="hint"><?= pt_h($r['title']) ?></span><?php endif; ?>
            </td>
            <td class="num" data-v="<?= (int) $r['views'] ?>"><?= num($r['views']) ?></td>
            <td class="num" data-v="<?= (int) $r['visitors'] ?>"><?= num($r['visitors']) ?></td>
            <td class="num" data-v="<?= (int) $r['avg_seconds'] ?>"><?= secs($r['avg_seconds']) ?></td>
            <td class="num" data-v="<?= (int) $r['avg_scroll'] ?>"><?= $r['avg_scroll'] ? round((float) $r['avg_scroll']) . '%' : '—' ?></td>
            <td data-v="<?= (int) $r['first_seen'] ?>"><?= day($r['first_seen'] ? (int) $r['first_seen'] : null) ?></td>
            <td data-v="<?= (int) $r['last_seen'] ?>"><?= day($r['last_seen'] ? (int) $r['last_seen'] : null) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <p class="hint">Avg. time counts only time the tab was in view. First seen and last seen are all time, not just this range. Query strings are never stored, so <code>/page?email=…</code> shows as <code>/page</code>.</p>
      <?php endif; ?>
    </section>

<?php else: // setup
    $recent = pt_recent_events();
?>
    <div class="two-col">
      <section class="card stack">
        <h2>Add the pixel</h2>
        <p>Paste this into the <code>&lt;head&gt;</code> of every page on emilydiazslp.com, lab.emilydiazslp.com and campus.emilydiazslp.com.</p>
        <textarea class="code" rows="3" readonly onclick="this.select()"><?= pt_h($snippet) ?></textarea>
        <h3>Goal clicks</h3>
        <p>Add <code>data-goal="checkout"</code> to any button or link, or to a <code>&lt;form&gt;</code> to count its submits. Use lowercase names with underscores.</p>
        <textarea class="code" rows="3" readonly onclick="this.select()"><a href="https://campus.emilydiazslp.com/checkout/…" data-goal="checkout_click">enroll now</a></textarea>
        <p>For anything else, call <code>tracker.goal('watched_replay')</code> from your own script.</p>
        <h3>Checkout links</h3>
        <p class="muted" style="margin:0">The pixel adds <code>?vid=…</code> to every link to <code>campus.emilydiazslp.com/checkout</code>, so phase 3 can tie each order to the buyer’s path.</p>
      </section>
      <section class="card stack">
        <h2>Leave yourself out</h2>
        <p>Your own visits skew the numbers. Exclude this browser and the pixel won’t send anything from it on any of the three sites. Do this on each browser and phone you use.</p>
        <p><strong>This browser is <span id="ignore-state">…</span></strong></p>
        <button class="btn btn-outline btn-sm" type="button" id="ignore-toggle">exclude this browser</button>
      </section>
    </div>

    <section class="card">
      <div class="row-between"><h2>Latest events</h2><a href="<?= pt_h(link_to(['p' => 'setup'])) ?>">refresh</a></div>
      <?php if (!$recent): ?>
        <p class="muted">Nothing has come in yet. Open a page with the pixel in another tab, then refresh.</p>
      <?php else: ?>
      <div class="table-wrap"><table>
        <thead><tr><th>When</th><th>Event</th><th>Page</th><th>From</th><th>Device</th><th class="num">Time on page</th><th>Visitor</th></tr></thead>
        <tbody><?php foreach ($recent as $r): ?>
          <tr>
            <td><?= day((int) $r['created_at'], 'M j · g:i:s a') ?></td>
            <td><span class="badge<?= $r['type'] === 'goal' ? ' badge-rose' : '' ?>"><?= pt_h($r['type'] === 'goal' ? 'goal: ' . $r['goal_name'] : $r['type']) ?></span></td>
            <td class="url"><span class="pt-site"><?= pt_h(site_label($r['site'])) ?></span><?= pt_h($r['path']) ?></td>
            <td><?= pt_h($r['utm_source'] ?: ($r['ref_host'] ?: '—')) ?></td>
            <td><?= pt_h($r['device'] ?: '—') ?></td>
            <td class="num"><?= $r['type'] === 'pageview' ? secs((float) $r['visible_seconds']) : '' ?></td>
            <td><code><?= pt_h(substr($r['visitor_id'], 0, 8)) ?></code></td>
          </tr>
        <?php endforeach; ?></tbody>
      </table></div>
      <?php endif; ?>
    </section>
<?php endif; ?>
  </main>
<script src="tracker-admin.js" defer></script>
</body>
</html>
