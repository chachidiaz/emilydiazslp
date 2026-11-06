<?php
// Continue with Google (OpenID Connect). Shown only when google.client_id/secret are set.
// Without ?code it sends the person to Google; Google sends them back here with ?code.
require __DIR__ . '/lib/auth.php';

if (!google_enabled()) {
    redirect(url('login.php'));
}
session_boot();
$redirectUri = absolute_url(url('google.php'));

function google_fail(string $message): never
{
    flash($message);
    redirect(url('login.php'));
}

if (!isset($_GET['code']) && !isset($_GET['error'])) {
    $_SESSION['g_state'] = bin2hex(random_bytes(16));
    $_SESSION['g_next'] = safe_next($_GET['next'] ?? '');
    redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => config('google.client_id'),
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $_SESSION['g_state'],
        'prompt' => 'select_account',
    ]));
}

$state = (string) ($_SESSION['g_state'] ?? '');
$next = safe_next($_SESSION['g_next'] ?? '');
unset($_SESSION['g_state'], $_SESSION['g_next']);
if (isset($_GET['error'])) {
    google_fail('Google sign-in was cancelled.');
}
if ($state === '' || !hash_equals($state, (string) ($_GET['state'] ?? ''))) {
    google_fail('Google sign-in timed out. Please try again.');
}

// Swap the code for tokens. The ID token comes straight from Google over TLS, so its
// claims can be read without checking the signature (per Google's OpenID guide).
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => (string) $_GET['code'],
        'client_id' => config('google.client_id'),
        'client_secret' => config('google.client_secret'),
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
    ]),
]);
$res = json_decode((string) curl_exec($ch), true);
curl_close($ch);
$parts = explode('.', (string) ($res['id_token'] ?? ''));
$claims = count($parts) === 3 ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) : null;

if (!is_array($claims)
    || !in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
    || ($claims['aud'] ?? '') !== config('google.client_id')
    || (int) ($claims['exp'] ?? 0) < time()
    || empty($claims['sub']) || empty($claims['email']) || empty($claims['email_verified'])) {
    google_fail('We couldn\'t sign you in with Google. Try again or use your email.');
}

$sub = (string) $claims['sub'];
$email = normalize_email((string) $claims['email']);
$isNew = false;
$user = q('SELECT * FROM users WHERE google_sub = ?', [$sub])->fetch() ?: null;
if (!$user && ($user = find_user_by_email($email))) {
    q('UPDATE users SET google_sub = ? WHERE id = ?', [$sub, $user['id']]);
}
if (!$user) {
    $name = trim((string) ($claims['name'] ?? '')) ?: explode('@', $email)[0];
    $user = create_user(mb_substr($name, 0, 120), $email, null, true, $sub);
    $isNew = true;
}
if ($user['status'] === 'suspended') {
    google_fail(suspended_message());
}
// Google confirmed the address, which counts as proof of ownership. Keep any password
// only if the account was already verified (otherwise someone else may have set it).
$user = mark_email_verified($user, (bool) $user['email_verified_at']);
sign_in($user);
redirect($isNew ? url('welcome.php') . ($next !== url('') ? '?next=' . rawurlencode($next) : '') : $next);
