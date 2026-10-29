<?php
// Shared code for the deadline timers app: database, campaigns, visitor tracking.

declare(strict_types=1);

const DL_COOKIE_PREFIX = 'dl_c';
const DL_COOKIE_PATH = '/apps/deadlines/';
const DL_MAX_COOKIE_DAYS = 400; // browsers cap cookie lifetime at 400 days

function dl_db(): PDO
{
    static $db = null;
    if ($db) {
        return $db;
    }
    $dir = dirname(__DIR__) . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $db = new PDO('sqlite:' . $dir . '/deadlines.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA busy_timeout = 5000');
    dl_migrate($db);
    return $db;
}

function dl_migrate(PDO $db): void
{
    $db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT
);
CREATE TABLE IF NOT EXISTS campaigns (
  id INTEGER PRIMARY KEY,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  mode TEXT NOT NULL DEFAULT 'evergreen',          -- evergreen | fixed
  duration_minutes INTEGER NOT NULL DEFAULT 10080, -- evergreen length (7 days)
  fixed_deadline INTEGER,                          -- unix time, fixed mode
  timezone TEXT NOT NULL DEFAULT 'America/Phoenix',
  reset_days INTEGER NOT NULL DEFAULT 0,           -- days after expiry until a fresh timer; 0 = never
  expired_action TEXT NOT NULL DEFAULT 'message',  -- hide | message | redirect
  expired_message TEXT NOT NULL DEFAULT 'This offer has ended.',
  redirect_url TEXT NOT NULL DEFAULT '',
  archived INTEGER NOT NULL DEFAULT 0,
  created_at INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS timers (
  id INTEGER PRIMARY KEY,
  campaign_id INTEGER NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
  public_key TEXT NOT NULL UNIQUE,
  type TEXT NOT NULL,                              -- bar | inline
  name TEXT NOT NULL,
  settings TEXT NOT NULL DEFAULT '{}',
  created_at INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS visitors (
  id INTEGER PRIMARY KEY,
  campaign_id INTEGER NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
  token TEXT NOT NULL UNIQUE,
  email TEXT,
  started_at INTEGER NOT NULL,
  deadline_at INTEGER NOT NULL,
  last_seen INTEGER NOT NULL,
  visits INTEGER NOT NULL DEFAULT 0,
  UNIQUE (campaign_id, email)
);
CREATE TABLE IF NOT EXISTS hits (
  id INTEGER PRIMARY KEY,
  visitor_id INTEGER NOT NULL REFERENCES visitors(id) ON DELETE CASCADE,
  at INTEGER NOT NULL,
  url TEXT NOT NULL DEFAULT '',
  via TEXT NOT NULL DEFAULT ''                     -- new | email | cookie | reset
);
CREATE INDEX IF NOT EXISTS hits_visitor ON hits (visitor_id, at);
SQL);
}

function dl_setting(string $key, ?string $default = null): ?string
{
    $st = dl_db()->prepare('SELECT value FROM settings WHERE key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function dl_set_setting(string $key, ?string $value): void
{
    dl_db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute([$key, $value]);
}

function dl_random(int $bytes = 16): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function dl_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function dl_normalize_email(?string $email): ?string
{
    $email = strtolower(trim((string) $email));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

function dl_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

// ---------- campaigns ----------

function dl_campaign(int $id): ?array
{
    $st = dl_db()->prepare('SELECT * FROM campaigns WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function dl_campaign_by_slug(string $slug): ?array
{
    $st = dl_db()->prepare('SELECT * FROM campaigns WHERE slug = ?');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function dl_slugify(string $name): string
{
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    $slug = $slug === '' ? 'campaign' : substr($slug, 0, 48);
    $base = $slug;
    for ($i = 2; dl_campaign_by_slug($slug); $i++) {
        $slug = $base . '-' . $i;
    }
    return $slug;
}

// The deadline a brand-new visitor would get right now.
function dl_new_deadline(array $c, int $now): int
{
    if ($c['mode'] === 'fixed') {
        return (int) $c['fixed_deadline'];
    }
    return $now + max(1, (int) $c['duration_minutes']) * 60;
}

// A visitor's current deadline. Fixed campaigns always follow the campaign date,
// so editing the date moves everyone.
function dl_visitor_deadline(array $c, array $v): int
{
    return $c['mode'] === 'fixed' ? (int) $c['fixed_deadline'] : (int) $v['deadline_at'];
}

function dl_reset_due(array $c, array $v, int $now): bool
{
    $days = (int) $c['reset_days'];
    return $days > 0 && $now > dl_visitor_deadline($c, $v) + $days * 86400;
}

function dl_cookie_name(array $c): string
{
    return DL_COOKIE_PREFIX . $c['id'];
}

function dl_cookie_lifetime(array $c, int $deadline, int $now): int
{
    $max = DL_MAX_COOKIE_DAYS * 86400;
    if ((int) $c['reset_days'] <= 0) {
        return $max;
    }
    return max(3600, min($max, $deadline + (int) $c['reset_days'] * 86400 - $now));
}

function dl_set_visitor_cookie(array $c, string $token, int $deadline, int $now): void
{
    setcookie(dl_cookie_name($c), $token, [
        'expires' => $now + dl_cookie_lifetime($c, $deadline, $now),
        'path' => DL_COOKIE_PATH,
        'secure' => dl_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function dl_find_visitor(string $column, int $campaignId, string $value): ?array
{
    $st = dl_db()->prepare("SELECT * FROM visitors WHERE campaign_id = ? AND $column = ?");
    $st->execute([$campaignId, $value]);
    return $st->fetch() ?: null;
}

/**
 * Finds or starts the visitor's timer for a campaign.
 * The email from a link wins over the browser cookie, so the same person sees the
 * same deadline on every device; a cookie-only visitor who later clicks an email
 * link keeps the deadline they already had.
 */
function dl_track(array $c, ?string $email, ?string $token, string $url, int $now): array
{
    $db = dl_db();
    $byEmail = $email ? dl_find_visitor('email', (int) $c['id'], $email) : null;
    $byToken = $token ? dl_find_visitor('token', (int) $c['id'], $token) : null;

    $v = $byEmail ?: $byToken;
    $via = $byEmail ? 'email' : ($byToken ? 'cookie' : 'new');

    if (!$byEmail && $byToken && $email) {
        if ($byToken['email'] === null) {
            $db->prepare('UPDATE visitors SET email = ? WHERE id = ?')->execute([$email, $byToken['id']]);
            $byToken['email'] = $email;
            $via = 'email';
        } else {
            // A different subscriber on a browser someone else already used.
            $v = null;
            $via = 'new';
        }
    }

    if ($v && dl_reset_due($c, $v, $now)) {
        $deadline = dl_new_deadline($c, $now);
        $db->prepare('UPDATE visitors SET started_at = ?, deadline_at = ? WHERE id = ?')
            ->execute([$now, $deadline, $v['id']]);
        $v['started_at'] = $now;
        $v['deadline_at'] = $deadline;
        $via = 'reset';
    }

    if (!$v) {
        $v = [
            'campaign_id' => (int) $c['id'],
            'token' => dl_random(),
            'email' => $email,
            'started_at' => $now,
            'deadline_at' => dl_new_deadline($c, $now),
            'last_seen' => $now,
            'visits' => 0,
        ];
        $db->prepare('INSERT INTO visitors (campaign_id, token, email, started_at, deadline_at, last_seen, visits) VALUES (?, ?, ?, ?, ?, ?, 0)')
            ->execute([$v['campaign_id'], $v['token'], $v['email'], $v['started_at'], $v['deadline_at'], $now]);
        $v['id'] = (int) $db->lastInsertId();
    }

    $db->prepare('UPDATE visitors SET last_seen = ?, visits = visits + 1 WHERE id = ?')->execute([$now, $v['id']]);
    $db->prepare('INSERT INTO hits (visitor_id, at, url, via) VALUES (?, ?, ?, ?)')
        ->execute([$v['id'], $now, substr($url, 0, 500), $via]);

    $v['via'] = $via;
    return $v;
}

// ---------- timers ----------

function dl_timer_defaults(string $type): array
{
    $shared = [
        'headline' => 'Your fast-action discount ends in',
        'expired_text' => '',
        'show_days' => true,
        'show_seconds' => true,
        'bg' => '#743E5A',
        'fg' => '#FFFBFD',
        'digit_bg' => '#FFFBFD',
        'digit_fg' => '#743E5A',
        'button_text' => '',
        'button_url' => '',
        'button_bg' => '#E192B9',
        'button_fg' => '#4A2739',
    ];
    $shared += ['font' => 'Poppins', 'digit_font' => ''];
    if ($type === 'bar') {
        return $shared + [
            'position' => 'top',
            'head_size' => 15, 'digit_size' => 18, 'label_size' => 10, 'pad_y' => 10, 'box_pad' => 4,
        ];
    }
    return array_merge($shared, [
        'headline' => 'This offer ends in',
        'bg' => '#FFFFFF',
        'fg' => '#743E5A',
        'digit_bg' => '#743E5A',
        'digit_fg' => '#FFFBFD',
        'button_bg' => '#743E5A',
        'button_fg' => '#FFFBFD',
        'align' => 'center',
        'head_size' => 20, 'digit_size' => 36, 'label_size' => 12, 'pad_y' => 28, 'box_pad' => 12,
    ]);
}

// Allowed range for each size setting, in px: [min, max].
const DL_SIZE_RANGES = [
    'head_size' => [10, 48],
    'digit_size' => [12, 96],
    'label_size' => [8, 24],
    'pad_y' => [0, 80],
    'box_pad' => [0, 40],
];

// Font names go into a Google Fonts URL and CSS, so keep them to plain names.
function dl_clean_font(string $name): string
{
    return substr(trim(preg_replace('/[^A-Za-z0-9 ]+/', '', $name)), 0, 40);
}

function dl_timer_settings(array $t): array
{
    $saved = json_decode($t['settings'] ?: '{}', true) ?: [];
    // Inline timers saved before the size sliders had a small / medium / large choice.
    $legacy = ['sm' => ['head_size' => 16, 'digit_size' => 24, 'box_pad' => 8], 'lg' => ['head_size' => 26, 'digit_size' => 52, 'box_pad' => 16]];
    if (isset($saved['size'])) {
        $saved = array_merge($legacy[$saved['size']] ?? [], $saved);
        unset($saved['size']);
    }
    $s = array_merge(dl_timer_defaults($t['type']), $saved);
    foreach (DL_SIZE_RANGES as $k => [$min, $max]) {
        $s[$k] = max($min, min($max, (int) $s[$k]));
    }
    return $s;
}

function dl_campaign_timers(int $campaignId): array
{
    $st = dl_db()->prepare('SELECT * FROM timers WHERE campaign_id = ? ORDER BY type, id');
    $st->execute([$campaignId]);
    return $st->fetchAll();
}

function dl_create_timer(int $campaignId, string $type, string $name, array $settings = [], ?string $key = null): int
{
    dl_db()->prepare('INSERT INTO timers (campaign_id, public_key, type, name, settings, created_at) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$campaignId, $key ?? strtolower(dl_random(6)), $type, $name, json_encode($settings), time()]);
    return (int) dl_db()->lastInsertId();
}

// Public data for one campaign as the visitor sees it. Never includes other people's data.
function dl_public_campaign(array $c, int $deadline, int $now): array
{
    return [
        'slug' => $c['slug'],
        'deadline' => $deadline,
        'expired' => $now >= $deadline,
        'expired_action' => $c['expired_action'],
        'expired_message' => $c['expired_message'],
        'redirect_url' => $c['redirect_url'],
    ];
}
