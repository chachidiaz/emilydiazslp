<?php
// Public endpoint timer.js calls on every page view. Starts or looks up this visitor's
// deadline for each campaign on the page and returns what the timers need to render.
//
//   GET api.php?t=<timer keys, comma separated>&c=<campaign slugs>&email=<optional>&u=<page url>
//   Add &reset=1 while logged in to the dashboard to restart your own timer.

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

function dl_list_param(string $name): array
{
    $items = array_filter(array_map('trim', explode(',', (string) ($_GET[$name] ?? ''))));
    return array_slice(array_values(array_unique($items)), 0, 20);
}

// Sandboxed previews (like the thumbnails on /admin) send "Origin: null". Don't track them.
if (($_SERVER['HTTP_ORIGIN'] ?? '') === 'null') {
    echo json_encode(['now' => time(), 'campaigns' => [], 'timers' => []]);
    exit;
}

$now = time();
$email = dl_normalize_email($_GET['email'] ?? null);
$url = (string) ($_GET['u'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
$reset = !empty($_GET['reset']) && dl_is_admin();
$db = dl_db();

// Collect the campaigns on this page: from the timers, plus any named directly.
$timers = [];
$campaignIds = [];
foreach (dl_list_param('t') as $key) {
    $st = $db->prepare('SELECT * FROM timers WHERE public_key = ?');
    $st->execute([$key]);
    if ($t = $st->fetch()) {
        $timers[$key] = $t;
        $campaignIds[(int) $t['campaign_id']] = true;
    }
}
foreach (dl_list_param('c') as $slug) {
    if ($c = dl_campaign_by_slug($slug)) {
        $campaignIds[(int) $c['id']] = true;
    }
}

$out = ['now' => $now, 'campaigns' => [], 'timers' => []];
$slugs = [];

foreach (array_keys($campaignIds) as $id) {
    $c = dl_campaign($id);
    if (!$c || $c['archived']) {
        continue;
    }
    $token = $_COOKIE[dl_cookie_name($c)] ?? null;
    if ($reset && $token) {
        $db->prepare('DELETE FROM visitors WHERE campaign_id = ? AND token = ?')->execute([$id, $token]);
        if ($email) {
            $db->prepare('DELETE FROM visitors WHERE campaign_id = ? AND email = ?')->execute([$id, $email]);
        }
    }
    $v = dl_track($c, $email, $token, $url, $now);
    $deadline = dl_visitor_deadline($c, $v);
    dl_set_visitor_cookie($c, $v['token'], $deadline, $now);
    $out['campaigns'][$c['slug']] = dl_public_campaign($c, $deadline, $now);
    $slugs[$id] = $c['slug'];
}

foreach ($timers as $key => $t) {
    if (!isset($slugs[(int) $t['campaign_id']])) {
        continue;
    }
    $out['timers'][$key] = [
        'type' => $t['type'],
        'campaign' => $slugs[(int) $t['campaign_id']],
        'settings' => dl_timer_settings($t),
    ];
}

echo json_encode($out, JSON_UNESCAPED_SLASHES);
