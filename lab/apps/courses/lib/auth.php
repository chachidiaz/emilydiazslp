<?php
// Accounts and sign-in: sessions, CSRF, rate limits, emailed tokens, roles.
//
// Sessions store the user id and the user's session_version. Bumping session_version
// signs that user out everywhere (password reset, admin "Sign out everywhere").
//
// Admins: an account becomes admin when its email is listed in config admin_emails AND the
// person has proved they own that address (magic link, password reset or Google). Signing up
// with the address alone never grants admin.

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/ui.php';

const SESSION_NAME = 'edc_session';
const MIN_PASSWORD = 12;
const RESET_TTL = 3600;   // 1 hour
const MAGIC_TTL = 900;    // 15 minutes

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => APP_BASE . '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 30));
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ---------- CSRF and flash ----------

function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('This page expired. Go back, refresh and try again.');
    }
}

function flash(?string $message = null): ?string
{
    session_boot();
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

/** Only allow redirects back into this app. */
function safe_next(?string $next): string
{
    $next = (string) $next;
    if ($next !== '' && str_starts_with($next, APP_BASE . '/') && !str_contains($next, '//') && !preg_match('/[\r\n\\\\]/', $next)) {
        return $next;
    }
    return url('');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// ---------- Users ----------

function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function valid_email(string $email): bool
{
    return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function password_problem(string $pw): ?string
{
    if (mb_strlen($pw) < MIN_PASSWORD) {
        return 'Use at least ' . MIN_PASSWORD . ' characters.';
    }
    if (strlen($pw) > 200) {
        return 'Use 200 characters or fewer.';
    }
    return null;
}

function find_user(int $id): ?array
{
    return q('SELECT * FROM users WHERE id = ?', [$id])->fetch() ?: null;
}

function find_user_by_email(string $email): ?array
{
    return q('SELECT * FROM users WHERE email = ?', [normalize_email($email)])->fetch() ?: null;
}

function create_user(string $name, string $email, ?string $password, bool $verified = false, ?string $googleSub = null): array
{
    q('INSERT INTO users (name, email, password_hash, google_sub, email_verified_at, created_at) VALUES (?, ?, ?, ?, ?, ?)', [
        trim($name), normalize_email($email),
        $password !== null ? password_hash($password, PASSWORD_DEFAULT) : null,
        $googleSub, $verified ? now_utc() : null, now_utc(),
    ]);
    $user = find_user((int) db()->lastInsertId());
    return $verified ? mark_email_verified($user, false) : $user;
}

function set_password(array $user, string $password): void
{
    q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
}

/** Signs the user out of every browser by invalidating existing sessions. */
function bump_session_version(array $user): void
{
    q('UPDATE users SET session_version = session_version + 1 WHERE id = ?', [$user['id']]);
}

/**
 * Records that the person controls this email, and promotes listed admin emails.
 * $passwordJustSet: true when proven by a password reset (that password is theirs to keep).
 */
function mark_email_verified(array $user, bool $passwordJustSet): array
{
    if (!$user['email_verified_at']) {
        q('UPDATE users SET email_verified_at = ? WHERE id = ?', [now_utc(), $user['id']]);
    }
    if ($user['role'] !== 'admin' && in_array($user['email'], admin_emails(), true)) {
        // Someone else may have signed up with this address first. Drop their password and
        // sessions so only the real owner keeps access to the admin account.
        q('UPDATE users SET role = ?, session_version = session_version + 1' . ($passwordJustSet ? '' : ', password_hash = NULL') . ' WHERE id = ?', ['admin', $user['id']]);
    }
    return find_user((int) $user['id']);
}

function is_admin(?array $user): bool
{
    return $user && $user['role'] === 'admin' && $user['email_verified_at'] !== null;
}

function first_name(array $user): string
{
    return explode(' ', trim($user['name']))[0] ?: $user['name'];
}

// ---------- Sessions ----------

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    // Don't start a session for visitors who never had one.
    if (session_status() !== PHP_SESSION_ACTIVE && empty($_COOKIE[SESSION_NAME])) {
        return $cache = null;
    }
    session_boot();
    $id = (int) ($_SESSION['uid'] ?? 0);
    if (!$id) {
        return $cache = null;
    }
    $user = find_user($id);
    if (!$user || (int) $user['session_version'] !== (int) ($_SESSION['sv'] ?? -1)) {
        unset($_SESSION['uid'], $_SESSION['sv']);
        return $cache = null;
    }
    if ($user['status'] === 'suspended') {
        unset($_SESSION['uid'], $_SESSION['sv']);
        flash(suspended_message());
        return $cache = null;
    }
    return $cache = $user;
}

function sign_in(array $user): void
{
    session_boot();
    session_regenerate_id(true);
    $user = find_user((int) $user['id']); // pick up any session_version change
    $_SESSION['uid'] = (int) $user['id'];
    $_SESSION['sv'] = (int) $user['session_version'];
    q("UPDATE users SET last_sign_in_at = ?, status = IF(status = 'invited', 'active', status) WHERE id = ?", [now_utc(), $user['id']]);
}

function sign_out(): void
{
    session_boot();
    $_SESSION = [];
    session_regenerate_id(true);
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        redirect(url('login.php') . '?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? url('')));
    }
    return $user;
}

function require_admin(): array
{
    $user = require_user();
    if (!is_admin($user)) {
        http_response_code(403);
        page_start('Not available');
        auth_shell_start();
        echo '<div class="auth-box" style="text-align:center"><h1 class="serif title-md">This page is for Emily.</h1><p class="muted">You don\'t have access to the admin area.</p><a class="btn btn-primary btn-lg btn-block" href="' . url('') . '">Go to my courses</a></div>';
        auth_shell_end('');
        page_end();
        exit;
    }
    return $user;
}

function suspended_message(): string
{
    return 'Your account is paused. Email hello@emilydiazslp.com and we\'ll help you get back in.';
}

// ---------- Password sign-in ----------

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/** Records one hit in $bucket. Returns false when the bucket already has $max hits in the window. */
function rate_limit(string $bucket, int $max, int $windowSeconds): bool
{
    if (random_int(1, 50) === 1) {
        q('DELETE FROM rate_limits WHERE at < ?', [gmdate('Y-m-d H:i:s', time() - 86400)]);
    }
    $since = gmdate('Y-m-d H:i:s', time() - $windowSeconds);
    $count = (int) q('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND at >= ?', [$bucket, $since])->fetchColumn();
    if ($count >= $max) {
        return false;
    }
    q('INSERT INTO rate_limits (bucket, at) VALUES (?, ?)', [substr($bucket, 0, 190), now_utc()]);
    return true;
}

/** @return array{0: ?array, 1: ?string} [user, error message] */
function password_login(string $email, string $password): array
{
    $email = normalize_email($email);
    if (!rate_limit('login-ip:' . client_ip(), 30, 900) || !rate_limit('login-email:' . $email, 10, 900)) {
        return [null, 'Too many tries. Wait 15 minutes, or use a magic link instead.'];
    }
    $user = find_user_by_email($email);
    // Verify against a dummy hash when there's no user, so timing doesn't reveal accounts.
    static $dummy = null;
    $dummy ??= password_hash('not-a-real-password', PASSWORD_DEFAULT);
    $hash = $user['password_hash'] ?? $dummy;
    $ok = password_verify($password, $hash) && $user && $user['password_hash'];
    if (!$ok) {
        usleep(300000);
        return [null, 'That email and password don\'t match. Try again or reset your password.'];
    }
    if ($user['status'] === 'suspended') {
        return [null, suspended_message()];
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        set_password($user, $password);
    }
    return [$user, null];
}

// ---------- Emailed tokens (password reset, magic link) ----------

function issue_token(array $user, string $kind, int $ttl): string
{
    q('UPDATE auth_tokens SET used_at = ? WHERE user_id = ? AND kind = ? AND used_at IS NULL', [now_utc(), $user['id'], $kind]);
    $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    q('INSERT INTO auth_tokens (user_id, kind, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?, ?)', [
        $user['id'], $kind, hash('sha256', $raw), gmdate('Y-m-d H:i:s', time() + $ttl), now_utc(),
    ]);
    return $raw;
}

/** Looks up a valid token without using it (for showing the confirm page). */
function peek_token(string $raw, string $kind): ?array
{
    if ($raw === '' || strlen($raw) > 100) {
        return null;
    }
    $row = q('SELECT user_id FROM auth_tokens WHERE token_hash = ? AND kind = ? AND used_at IS NULL AND expires_at > ?', [
        hash('sha256', $raw), $kind, now_utc(),
    ])->fetch();
    return $row ? find_user((int) $row['user_id']) : null;
}

/** Uses a token once. Returns its user, or null if it's invalid, used or expired. */
function consume_token(string $raw, string $kind): ?array
{
    $user = peek_token($raw, $kind);
    if (!$user) {
        return null;
    }
    $st = q('UPDATE auth_tokens SET used_at = ? WHERE token_hash = ? AND used_at IS NULL', [now_utc(), hash('sha256', $raw)]);
    return $st->rowCount() === 1 ? $user : null;
}
