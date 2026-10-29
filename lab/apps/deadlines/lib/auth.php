<?php
// Single-admin login for the dashboard. The password is set on first visit and stored hashed.

declare(strict_types=1);

require_once __DIR__ . '/core.php';

const DL_MAX_FAILED_LOGINS = 10;
const DL_LOCKOUT_SECONDS = 900;

function dl_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('dl_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => DL_COOKIE_PATH,
        'secure' => dl_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function dl_is_admin(): bool
{
    // Only resume a session the browser already has; never start one for public visitors.
    if (session_status() !== PHP_SESSION_ACTIVE && empty($_COOKIE['dl_admin'])) {
        return false;
    }
    dl_session_start();
    return !empty($_SESSION['dl_admin']);
}

function dl_has_password(): bool
{
    return dl_setting('admin_hash') !== null;
}

function dl_set_password(string $password): void
{
    dl_set_setting('admin_hash', password_hash($password, PASSWORD_DEFAULT));
}

function dl_login(string $password): bool|string
{
    $lockedUntil = (int) dl_setting('locked_until', '0');
    if ($lockedUntil > time()) {
        return 'Too many tries. Wait ' . (int) ceil(($lockedUntil - time()) / 60) . ' minutes and try again.';
    }
    if (password_verify($password, (string) dl_setting('admin_hash'))) {
        dl_set_setting('failed_logins', '0');
        session_regenerate_id(true);
        $_SESSION['dl_admin'] = true;
        return true;
    }
    $failed = (int) dl_setting('failed_logins', '0') + 1;
    dl_set_setting('failed_logins', (string) $failed);
    if ($failed >= DL_MAX_FAILED_LOGINS) {
        dl_set_setting('failed_logins', '0');
        dl_set_setting('locked_until', (string) (time() + DL_LOCKOUT_SECONDS));
    }
    usleep(400000);
    return false;
}

function dl_logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function dl_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = dl_random();
    }
    return $_SESSION['csrf'];
}

function dl_check_csrf(): void
{
    if (!hash_equals(dl_csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}
