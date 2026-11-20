<?php
// Shared code for the purchase path tracker: database, event intake, report queries.

declare(strict_types=1);

const PT_ROOT_DOMAIN = 'emilydiazslp.com';
const PT_TIMEZONE = 'America/Phoenix';
const PT_MAX_VISIBLE_SECONDS = 4 * 3600; // ignore longer "visible" times (tabs left open)

// Crawlers, uptime checkers and headless browsers. Their events are dropped.
const PT_BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|lighthouse|headless|phantom|puppeteer|playwright|pingdom|uptime|monitor|curl|wget|python|httpclient|go-http|java\/|axios|node-fetch/i';

function pt_db(): PDO
{
    static $db = null;
    if ($db) {
        return $db;
    }
    $dir = dirname(__DIR__) . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $db = new PDO('sqlite:' . $dir . '/tracker.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->exec('PRAGMA journal_mode = WAL'); // lets the dashboard read while pages write
    $db->exec('PRAGMA synchronous = NORMAL');
    pt_migrate($db);
    return $db;
}

function pt_migrate(PDO $db): void
{
    $db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT
);
CREATE TABLE IF NOT EXISTS events (
  id INTEGER PRIMARY KEY,
  pv_id TEXT UNIQUE,                 -- pageview id from the pixel; page_leave updates this row
  visitor_id TEXT NOT NULL,
  session_id TEXT NOT NULL,
  type TEXT NOT NULL,                -- pageview | goal (purchase arrives in phase 3)
  site TEXT NOT NULL,                -- main | lab | campus | dev
  url TEXT NOT NULL,                 -- origin + path, never the query string
  path TEXT NOT NULL,
  title TEXT NOT NULL DEFAULT '',
  referrer TEXT NOT NULL DEFAULT '', -- external referrer, origin + path only
  ref_host TEXT NOT NULL DEFAULT '',
  utm_source TEXT NOT NULL DEFAULT '',
  utm_medium TEXT NOT NULL DEFAULT '',
  utm_campaign TEXT NOT NULL DEFAULT '',
  utm_content TEXT NOT NULL DEFAULT '',
  utm_term TEXT NOT NULL DEFAULT '',
  device TEXT NOT NULL DEFAULT '',   -- mobile | tablet | desktop
  browser TEXT NOT NULL DEFAULT '',
  screen TEXT NOT NULL DEFAULT '',
  country TEXT NOT NULL DEFAULT '',
  visible_seconds INTEGER NOT NULL DEFAULT 0,
  scroll_depth INTEGER NOT NULL DEFAULT 0,
  goal_name TEXT NOT NULL DEFAULT '',
  created_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS events_time ON events (created_at);
CREATE INDEX IF NOT EXISTS events_page ON events (site, path, created_at);
CREATE INDEX IF NOT EXISTS events_visitor ON events (visitor_id, created_at);
CREATE TABLE IF NOT EXISTS visitors (
  visitor_id TEXT PRIMARY KEY,
  first_seen INTEGER NOT NULL,
  last_seen INTEGER NOT NULL,
  first_source TEXT NOT NULL DEFAULT '',
  first_campaign TEXT NOT NULL DEFAULT '',
  email TEXT,                        -- set only once they opt in (phase 3)
  customer_id TEXT
);
CREATE TABLE IF NOT EXISTS pages (
  site TEXT NOT NULL,
  path TEXT NOT NULL,
  title TEXT NOT NULL DEFAULT '',
  first_seen INTEGER NOT NULL,
  last_seen INTEGER NOT NULL,
  PRIMARY KEY (site, path)
);
SQL);
}

function pt_setting(string $key, ?string $default = null): ?string
{
    $st = pt_db()->prepare('SELECT value FROM settings WHERE key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function pt_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// ---------- intake ----------

/** Which of our sites a hostname belongs to, or null if it isn't ours. */
function pt_site_for_host(string $host): ?string
{
    $host = strtolower($host);
    if ($host === PT_ROOT_DOMAIN || $host === 'www.' . PT_ROOT_DOMAIN) {
        return 'main';
    }
    if ($host === 'lab.' . PT_ROOT_DOMAIN) {
        return 'lab';
    }
    if ($host === 'campus.' . PT_ROOT_DOMAIN) {
        return 'campus';
    }
    if (in_array($host, ['localhost', '127.0.0.1'], true)) {
        return 'dev';
    }
    return null;
}

function pt_is_own_host(string $host): bool
{
    $host = strtolower($host);
    return $host === PT_ROOT_DOMAIN || str_ends_with($host, '.' . PT_ROOT_DOMAIN);
}

function pt_str(mixed $v, int $max): string
{
    if (!is_string($v) && !is_int($v) && !is_float($v)) {
        return '';
    }
    $v = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $v) ?? '');
    return mb_substr($v, 0, $max);
}

function pt_id(mixed $v): ?string
{
    return is_string($v) && preg_match('/^[A-Za-z0-9_-]{8,40}$/', $v) ? $v : null;
}

/** Strips the query string and fragment so emails and tokens in links never get stored. */
function pt_clean_url(string $url): ?array
{
    $p = parse_url($url);
    if (!$p || empty($p['host']) || !in_array($p['scheme'] ?? '', ['http', 'https'], true)) {
        return null;
    }
    $path = $p['path'] ?? '/';
    $path = $path === '' ? '/' : mb_substr($path, 0, 300);
    $host = strtolower($p['host']);
    $port = isset($p['port']) ? ':' . $p['port'] : '';
    return ['host' => $host, 'path' => $path, 'url' => $p['scheme'] . '://' . $host . $port . $path];
}

function pt_device(mixed $width): string
{
    $w = (int) $width;
    if ($w <= 0) {
        return '';
    }
    return $w < 768 ? 'mobile' : ($w < 1024 ? 'tablet' : 'desktop');
}

function pt_browser(string $ua): string
{
    return match (true) {
        str_contains($ua, 'Edg/') => 'Edge',
        str_contains($ua, 'OPR/') => 'Opera',
        str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
        str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS') => 'Firefox',
        str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS') => 'Chrome',
        str_contains($ua, 'Safari/') => 'Safari',
        default => 'Other',
    };
}

/**
 * Records one event from the pixel. Returns false when the event is dropped.
 * $e is the decoded JSON body; $ua is the request's user agent.
 */
function pt_ingest(array $e, string $ua, int $now): bool
{
    if ($ua === '' || preg_match(PT_BOT_PATTERN, $ua)) {
        return false;
    }
    $type = $e['t'] ?? '';
    $vid = pt_id($e['v'] ?? null);
    $sid = pt_id($e['s'] ?? null);
    $pv = pt_id($e['p'] ?? null);
    $page = pt_clean_url(pt_str($e['u'] ?? '', 2000));
    if (!$vid || !$sid || !$page) {
        return false;
    }
    $site = pt_site_for_host($page['host']);
    if ($site === null) {
        return false;
    }
    $db = pt_db();

    if ($type === 'leave') {
        if (!$pv) {
            return false;
        }
        $secs = max(0, min(PT_MAX_VISIBLE_SECONDS, (int) ($e['vs'] ?? 0)));
        $depth = max(0, min(100, (int) ($e['sd'] ?? 0)));
        // The pixel sends the running total each time the tab is hidden, so keep the largest.
        $db->prepare('UPDATE events SET visible_seconds = MAX(visible_seconds, ?), scroll_depth = MAX(scroll_depth, ?)
                      WHERE pv_id = ? AND visitor_id = ?')
            ->execute([$secs, $depth, $pv, $vid]);
        return true;
    }

    if ($type !== 'pageview' && $type !== 'goal') {
        return false;
    }

    $referrer = '';
    $refHost = '';
    $ref = pt_clean_url(pt_str($e['r'] ?? '', 2000));
    if ($ref && !pt_is_own_host($ref['host']) && pt_site_for_host($ref['host']) === null) {
        $referrer = $ref['url'];
        $refHost = preg_replace('/^www\./', '', $ref['host']);
    }
    $utm = [];
    foreach (['source', 'medium', 'campaign', 'content', 'term'] as $k) {
        $utm[$k] = strtolower(pt_str($e['utm'][$k] ?? '', 120));
    }
    $goal = '';
    if ($type === 'goal') {
        $goal = strtolower(preg_replace('/[^a-z0-9_-]+/i', '_', pt_str($e['g'] ?? '', 60)));
        $goal = trim($goal, '_');
        if ($goal === '') {
            return false;
        }
    }
    $title = pt_str($e['ti'] ?? '', 200);

    $db->beginTransaction();
    try {
        $db->prepare('INSERT OR IGNORE INTO events (pv_id, visitor_id, session_id, type, site, url, path, title, referrer, ref_host,
                        utm_source, utm_medium, utm_campaign, utm_content, utm_term, device, browser, screen, goal_name, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                $type === 'pageview' ? $pv : null, $vid, $sid, $type, $site, $page['url'], $page['path'], $title,
                $referrer, $refHost, $utm['source'], $utm['medium'], $utm['campaign'], $utm['content'], $utm['term'],
                pt_device($e['w'] ?? 0), pt_browser($ua), pt_str($e['sc'] ?? '', 20), $goal, $now,
            ]);

        $source = $utm['source'] ?: ($refHost ?: 'direct');
        $db->prepare('INSERT INTO visitors (visitor_id, first_seen, last_seen, first_source, first_campaign) VALUES (?, ?, ?, ?, ?)
                      ON CONFLICT(visitor_id) DO UPDATE SET last_seen = excluded.last_seen')
            ->execute([$vid, $now, $now, $source, $utm['campaign']]);

        if ($type === 'pageview') {
            $db->prepare('INSERT INTO pages (site, path, title, first_seen, last_seen) VALUES (?, ?, ?, ?, ?)
                          ON CONFLICT(site, path) DO UPDATE SET last_seen = excluded.last_seen,
                            title = CASE WHEN excluded.title <> \'\' THEN excluded.title ELSE pages.title END')
                ->execute([$site, $page['path'], $title, $now, $now]);
        }
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        throw $ex;
    }
    return true;
}

// ---------- reports ----------

const PT_SITES = ['main' => 'emilydiazslp.com', 'lab' => 'lab', 'campus' => 'campus'];

/** Turns the range picker's query values into [from, to, label] as unix times in PT_TIMEZONE. */
function pt_range(string $range, string $fromStr = '', string $toStr = ''): array
{
    $tz = new DateTimeZone(PT_TIMEZONE);
    $today = new DateTimeImmutable('today', $tz);
    $tomorrow = $today->modify('+1 day');
    switch ($range) {
        case 'today':
            return [$today->getTimestamp(), $tomorrow->getTimestamp(), 'Today'];
        case '7d':
            return [$today->modify('-6 days')->getTimestamp(), $tomorrow->getTimestamp(), 'Last 7 days'];
        case 'custom':
            $f = DateTimeImmutable::createFromFormat('!Y-m-d', $fromStr, $tz);
            $t = DateTimeImmutable::createFromFormat('!Y-m-d', $toStr, $tz);
            if ($f && $t && $f <= $t) {
                return [$f->getTimestamp(), $t->modify('+1 day')->getTimestamp(),
                    $f->format('M j, Y') . ' – ' . $t->format('M j, Y')];
            }
            // fall through to 30 days on a bad custom range
        default:
            return [$today->modify('-29 days')->getTimestamp(), $tomorrow->getTimestamp(), 'Last 30 days'];
    }
}

/** SQL filter for the site picker. Returns [sql, params]. */
function pt_site_filter(string $site): array
{
    return isset(PT_SITES[$site]) ? [' AND site = ?', [$site]] : [" AND site <> 'dev'", []];
}

function pt_overview(int $from, int $to, string $site): array
{
    [$sf, $sp] = pt_site_filter($site);
    $st = pt_db()->prepare("SELECT
            COUNT(DISTINCT CASE WHEN type = 'pageview' THEN visitor_id END) AS visitors,
            COUNT(DISTINCT CASE WHEN type = 'pageview' THEN session_id END) AS sessions,
            SUM(type = 'pageview') AS pageviews,
            AVG(CASE WHEN type = 'pageview' AND visible_seconds > 0 THEN visible_seconds END) AS avg_seconds,
            SUM(type = 'goal') AS goals
        FROM events WHERE created_at >= ? AND created_at < ?$sf");
    $st->execute([$from, $to, ...$sp]);
    $row = $st->fetch() ?: [];
    return array_map(fn($v) => $v === null ? 0 : $v + 0, $row);
}

/** Daily pageviews and visitors, one row per day in the range (days with no traffic included). */
function pt_daily(int $from, int $to, string $site): array
{
    [$sf, $sp] = pt_site_filter($site);
    $tz = new DateTimeZone(PT_TIMEZONE);
    $offset = $tz->getOffset(new DateTime('@' . $from)); // Phoenix has no daylight saving
    $st = pt_db()->prepare("SELECT date(created_at + ?, 'unixepoch') AS day, COUNT(*) AS pageviews, COUNT(DISTINCT visitor_id) AS visitors
        FROM events WHERE type = 'pageview' AND created_at >= ? AND created_at < ?$sf GROUP BY day");
    $st->execute([$offset, $from, $to, ...$sp]);
    $byDay = [];
    foreach ($st as $r) {
        $byDay[$r['day']] = $r;
    }
    $out = [];
    for ($d = (new DateTimeImmutable('@' . $from))->setTimezone($tz); $d->getTimestamp() < $to; $d = $d->modify('+1 day')) {
        $k = $d->format('Y-m-d');
        $out[] = ['day' => $k, 'pageviews' => (int) ($byDay[$k]['pageviews'] ?? 0), 'visitors' => (int) ($byDay[$k]['visitors'] ?? 0)];
    }
    return $out;
}

function pt_pages(int $from, int $to, string $site, int $limit = 500): array
{
    [$sf, $sp] = pt_site_filter($site);
    $sf = str_replace('site', 'e.site', $sf);
    $st = pt_db()->prepare("SELECT e.site, e.path, p.title, p.first_seen, p.last_seen,
            COUNT(*) AS views, COUNT(DISTINCT e.visitor_id) AS visitors,
            AVG(CASE WHEN e.visible_seconds > 0 THEN e.visible_seconds END) AS avg_seconds,
            AVG(CASE WHEN e.scroll_depth > 0 THEN e.scroll_depth END) AS avg_scroll
        FROM events e LEFT JOIN pages p ON p.site = e.site AND p.path = e.path
        WHERE e.type = 'pageview' AND e.created_at >= ? AND e.created_at < ?$sf
        GROUP BY e.site, e.path ORDER BY views DESC LIMIT $limit");
    $st->execute([$from, $to, ...$sp]);
    return $st->fetchAll();
}

function pt_sources(int $from, int $to, string $site, int $limit = 10): array
{
    [$sf, $sp] = pt_site_filter($site);
    $st = pt_db()->prepare("SELECT CASE WHEN utm_source <> '' THEN utm_source WHEN ref_host <> '' THEN ref_host ELSE 'direct' END AS source,
            COUNT(*) AS views, COUNT(DISTINCT visitor_id) AS visitors
        FROM events WHERE type = 'pageview' AND created_at >= ? AND created_at < ?$sf
        GROUP BY source ORDER BY visitors DESC LIMIT $limit");
    $st->execute([$from, $to, ...$sp]);
    return $st->fetchAll();
}

function pt_recent_events(int $limit = 25): array
{
    return pt_db()->query("SELECT type, site, path, goal_name, ref_host, utm_source, device, visible_seconds, visitor_id, created_at
        FROM events ORDER BY id DESC LIMIT $limit")->fetchAll();
}
