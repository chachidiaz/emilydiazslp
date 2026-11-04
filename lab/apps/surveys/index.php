<?php
// Survey results: tallies, written answers and a CSV download. Uses the lab dashboard's login.

declare(strict_types=1);

require_once __DIR__ . '/lib/core.php';
require_once __DIR__ . '/../deadlines/lib/auth.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store');

if (!dl_is_admin()) {
    header('Location: /admin/');
    exit;
}

$slug = (string) ($_GET['survey'] ?? array_key_first(SV_SURVEYS));
$survey = SV_SURVEYS[$slug] ?? null;
if (!$survey) {
    http_response_code(404);
    exit('No survey called ' . dl_h($slug) . '.');
}
$rows = sv_responses($slug);
date_default_timezone_set('America/Chicago');

// Turns a stored answer into words, e.g. ["monday","friday"] → "Monday, Friday".
function sv_words(array $q, mixed $a): string
{
    if ($q['type'] === 'text') {
        return (string) $a;
    }
    $keys = is_array($a) ? $a : ($a === '' ? [] : [$a]);
    return implode(', ', array_map(fn($k) => $q['options'][$k] ?? $k, $keys));
}

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $slug . '-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // so Excel reads the en dashes correctly
    fputcsv($out, array_merge(['Answered', 'Email'], array_column($survey['questions'], 'label')));
    foreach ($rows as $r) {
        $line = [date('Y-m-d H:i', (int) $r['updated_at']), $r['email'] ?? ''];
        foreach ($survey['questions'] as $key => $q) {
            $line[] = sv_words($q, $r['answers'][$key] ?? '');
        }
        fputcsv($out, $line);
    }
    exit;
}

$total = count($rows);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= dl_h($survey['title']) ?> results</title>
<link rel="stylesheet" href="/brand/styles.css">
<style>
body{margin:0;background:var(--surface-page);color:var(--text-body);font-family:var(--font-sans)}
main{max-width:860px;margin:0 auto;padding:var(--space-7) 16px var(--space-9)}
.top{display:flex;flex-wrap:wrap;gap:var(--space-4);align-items:flex-end;justify-content:space-between;margin-bottom:var(--space-6)}
h1{margin:var(--space-2) 0 0;color:var(--text-heading);font-size:var(--text-h2);line-height:var(--leading-snug)}
h2{margin:0 0 var(--space-4);color:var(--text-heading);font-size:var(--text-h4);line-height:var(--leading-snug)}
.meta{margin:var(--space-2) 0 0;color:var(--text-muted);font-size:var(--text-body-sm)}
.meta a,.back{color:var(--text-link)}
.btn{display:inline-block;padding:12px 24px;border-radius:var(--radius-pill);background:var(--action-primary);color:var(--white);font-weight:700;font-size:var(--text-body-sm);text-decoration:none;transition:background var(--dur-base) var(--ease-standard)}
.btn:hover{background:var(--action-primary-hover)}
.card{background:var(--surface-card);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);padding:var(--space-6);margin-bottom:var(--space-5)}
.bars{display:grid;gap:var(--space-3);margin:0;padding:0;list-style:none}
.bar{display:grid;grid-template-columns:minmax(120px,1fr) 2fr auto;gap:var(--space-4);align-items:center;font-size:var(--text-body-sm)}
.track{height:12px;border-radius:var(--radius-pill);background:var(--ink-100);overflow:hidden}
.fill{display:block;height:100%;border-radius:var(--radius-pill);background:var(--rose-500)}
.bar.top-pick .fill{background:var(--plum-700)}
.bar.top-pick .label{color:var(--plum-700);font-weight:700}
.num{font-variant-numeric:tabular-nums;color:var(--ink-900);font-weight:600;min-width:5ch;text-align:right}
.quotes{margin:0;padding:0;list-style:none;display:grid;gap:var(--space-4)}
.quotes li{padding-bottom:var(--space-4);border-bottom:1px solid var(--border-soft);white-space:pre-line;line-height:var(--leading-loose)}
.quotes li:last-child{border-bottom:0;padding-bottom:0}
.who{display:block;margin-top:var(--space-1);color:var(--text-muted);font-size:var(--text-micro)}
.empty{color:var(--text-muted)}
@media (max-width:560px){.bar{grid-template-columns:1fr auto}.track{grid-column:1/-1;grid-row:2}}
</style>
</head>
<body>
<main>
  <a class="back" href="/admin/">← lab dashboard</a>
  <div class="top">
    <div>
      <h1><?= dl_h($survey['title']) ?></h1>
      <p class="meta"><?= $total ?> <?= $total === 1 ? 'response' : 'responses' ?> · <a href="<?= dl_h($survey['page']) ?>" target="_blank">open the survey</a></p>
    </div>
    <?php if ($total): ?><a class="btn" href="?survey=<?= dl_h($slug) ?>&amp;csv=1">download spreadsheet</a><?php endif ?>
  </div>

  <?php if (!$total): ?>
    <div class="card"><p class="empty">No answers yet. They'll show up here as soon as someone sends the survey.</p></div>
  <?php endif ?>

  <?php foreach ($total ? $survey['questions'] : [] as $key => $q): ?>
    <section class="card">
      <h2><?= dl_h($q['label']) ?></h2>
      <?php if ($q['type'] === 'text'):
        $said = array_filter($rows, fn($r) => trim((string) ($r['answers'][$key] ?? '')) !== ''); ?>
        <?php if (!$said): ?><p class="empty">Nobody has written an answer yet.</p><?php endif ?>
        <ul class="quotes">
          <?php foreach ($said as $r): ?>
            <li><?= dl_h($r['answers'][$key]) ?><span class="who"><?= dl_h($r['email'] ?? 'no email') ?> · <?= date('M j, g:ia', (int) $r['updated_at']) ?> CT</span></li>
          <?php endforeach ?>
        </ul>
      <?php else:
        $counts = array_fill_keys(array_keys($q['options']), 0);
        foreach ($rows as $r) {
            foreach ((array) ($r['answers'][$key] ?? []) as $k) {
                if (isset($counts[$k])) { $counts[$k]++; }
            }
        }
        $max = max($counts); ?>
        <ul class="bars">
          <?php foreach ($q['options'] as $k => $label): $n = $counts[$k]; ?>
            <li class="bar<?= $n && $n === $max ? ' top-pick' : '' ?>">
              <span class="label"><?= dl_h($label) ?></span>
              <span class="track"><span class="fill" style="width:<?= $total ? round($n / $total * 100) : 0 ?>%"></span></span>
              <span class="num"><?= $n ?> · <?= $total ? round($n / $total * 100) : 0 ?>%</span>
            </li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </section>
  <?php endforeach ?>
</main>
</body>
</html>
