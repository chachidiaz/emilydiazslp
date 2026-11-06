<?php
// Settings come from data/config.php, which is never committed (this repo is public).
// On the live site the deploy workflow writes it from GitHub secrets; see the README.

declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $file = dirname(__DIR__) . '/data/config.php';
        $cfg = is_file($file) ? (array) require $file : [];
    }
    $value = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return ($value === '' || $value === null) ? $default : $value;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Absolute URL for links in emails and OAuth redirects. */
function absolute_url(string $path): string
{
    $base = rtrim((string) config('base_url', ''), '/');
    if ($base === '') {
        $base = (is_https() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    return $base . $path;
}

/** Emails that become admins once they prove they own the address. */
function admin_emails(): array
{
    $list = config('admin_emails', '');
    $list = is_array($list) ? $list : explode(',', (string) $list);
    return array_values(array_filter(array_map(fn($e) => strtolower(trim((string) $e)), $list)));
}

function google_enabled(): bool
{
    return config('google.client_id') && config('google.client_secret');
}
