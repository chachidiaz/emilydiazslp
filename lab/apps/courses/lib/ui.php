<?php
// Shared layout pieces for the course app. Every screen builds from these.

const SITE_NAME = 'Learn with Emily Diaz, SLP';
const APP_BASE = '/apps/courses';
const MAIN_SITE = 'https://emilydiazslp.com';
const LOGO_SRC = '/brand/assets/logo-maroon.png';

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return APP_BASE . '/' . ltrim($path, '/');
}

/** Opens the document. $title is the page name; the site name is appended. */
function page_start(string $title, string $bodyClass = ''): void
{
    $v = @filemtime(__DIR__ . '/../assets/app.css') ?: 1;
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Newsreader:opsz,wght@6..72,400;6..72,500&amp;display=swap">
<link rel="stylesheet" href="<?= url('assets/app.css') ?>?v=<?= $v ?>">
<script src="<?= url('assets/app.js') ?>?v=<?= $v ?>" defer></script>
</head>
<body<?= $bodyClass ? ' class="' . e($bodyClass) . '"' : '' ?>>
<?php
}

function page_end(): void
{
    echo "</body>\n</html>\n";
}

function logo(string $href, string $size = ''): string
{
    $cls = 'logo' . ($size === 'lg' ? ' logo-lg' : '');
    return '<a class="' . $cls . '" href="' . e($href) . '" aria-label="' . e(SITE_NAME) . ' home"><img src="' . LOGO_SRC . '" alt="Emily Diaz"></a>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = $parts[0] ?? '';
    $last = count($parts) > 1 ? end($parts) : '';
    return strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
}

/** Student top nav. $active: 'courses' | 'settings'. */
function student_nav(string $active, string $userName): void
{
    $link = fn(string $key, string $label, string $href) =>
        '<a class="navlink' . ($active === $key ? ' active' : '') . '" href="' . e($href) . '"' . ($active === $key ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
    ?>
<header class="nav">
  <?= logo(url('')) ?>
  <nav class="nav-links" aria-label="Primary">
    <?= $link('courses', 'My courses', url('')) ?>
    <?= $link('settings', 'Settings', url('settings.php')) ?>
  </nav>
  <div class="nav-spacer"></div>
  <a class="navlink" href="<?= e(MAIN_SITE) ?>">Back to main site <?= icon('external', 16) ?></a>
  <a class="avatar" href="<?= url('settings.php') ?>" aria-label="Your settings"><?= e(initials($userName)) ?></a>
</header>
<?php
}

function student_footer(): void
{
    ?>
<footer class="footer">
  <div class="footer-in">
    <div class="stack gap-8">
      <?= logo(url('')) ?>
      <div class="small muted">© <?= date('Y') ?> Emily Diaz. All rights reserved.</div>
    </div>
    <nav class="nav-links" aria-label="Footer">
      <a class="navlink" href="<?= e(MAIN_SITE) ?>">Main site</a>
      <a class="navlink" href="<?= url('help.php') ?>">Help center</a>
      <a class="navlink" href="mailto:hello@emilydiazslp.com">Contact support</a>
      <a class="navlink" href="<?= e(MAIN_SITE) ?>/terms">Terms</a>
      <a class="navlink" href="<?= e(MAIN_SITE) ?>/privacy">Privacy</a>
    </nav>
  </div>
</footer>
<?php
}

/** Opens the admin shell: header, sidebar, <main>. Close with admin_end(). */
function admin_start(string $active, string $adminName = 'Emily Diaz'): void
{
    $items = [
        'students' => ['Students', url('admin/')],
        'courses'  => ['Courses', url('admin/courses.php')],
        'orders'   => ['Orders', url('admin/orders.php')],
        'settings' => ['Settings', url('admin/settings.php')],
    ];
    ?>
<div class="page">
<header class="nav" style="justify-content:space-between">
  <div style="display:flex;align-items:center;gap:14px"><?= logo(url('admin/')) ?><span class="badge">Admin</span></div>
  <div style="display:flex;align-items:center;gap:12px"><a class="navlink" href="<?= url('') ?>">View student site</a><span class="avatar"><?= e(initials($adminName)) ?></span></div>
</header>
<div class="admin">
  <nav class="admin-side" aria-label="Admin">
    <p class="sidehead">Manage</p>
    <?php foreach ($items as $key => [$label, $href]): ?>
    <a class="sidelink<?= $active === $key ? ' active' : '' ?>" href="<?= e($href) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <main class="admin-main">
<?php
}

function admin_end(): void
{
    echo "  </main>\n</div>\n</div>\n";
}

/** Status badge for students and orders. */
function status_badge(string $status): string
{
    $map = [
        'active'    => ['Active', ''],
        'paid'      => ['Paid', ''],
        'completed' => ['Completed', ''],
        'invited'   => ['Invited', ' badge-muted'],
        'suspended' => ['Suspended', ' badge-danger'],
        'refunded'  => ['Refunded', ' badge-danger'],
    ];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), ' badge-muted'];
    return '<span class="badge' . $cls . '">' . e($label) . '</span>';
}

/** Switch with a live "Has access" / "No access" style label. */
function switch_control(string $id, bool $on, string $ariaLabel, string $onText = '', string $offText = ''): string
{
    $labelled = $onText !== '';
    $out = '<button type="button" class="switch" role="switch" id="' . e($id) . '" aria-checked="' . ($on ? 'true' : 'false') . '" aria-label="' . e($ariaLabel) . '"';
    if ($labelled) {
        $out .= ' data-label="' . e($id) . '-state" data-on="' . e($onText) . '" data-off="' . e($offText) . '"';
    }
    $out .= '></button>';
    if ($labelled) {
        $out = '<span id="' . e($id) . '-state" class="small muted">' . e($on ? $onText : $offText) . '</span>' . $out;
    }
    return $out;
}

/** Progress bar plus its caption. */
function progress_bar(int $done, int $total, bool $showMeta = true): string
{
    $pct = $total > 0 ? (int) round($done / $total * 100) : 0;
    $bar = '<div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $pct . '" aria-label="Course progress"><span style="width:' . $pct . '%"></span></div>';
    if (!$showMeta) {
        return $bar;
    }
    return $bar . '<div class="progress-meta"><span>' . $done . ' of ' . $total . ' lessons</span><span>' . $pct . '%</span></div>';
}

/** Line icons used across the screens (stroke = currentColor). */
function icon(string $name, int $size = 20): string
{
    $paths = [
        'external' => '<path d="M6 3H3v10h10v-3"/><path d="M9 3h4v4"/><path d="M13 3L7 9"/>',
        'chevron'  => '<path d="M8 5l5 5-5 5"/>',
        'back'     => '<path d="M12 15l-5-5 5-5"/>',
        'circle'   => '<circle cx="10" cy="10" r="7.5"/>',
        'check-circle' => '<circle cx="10" cy="10" r="7.5"/><path d="M6.5 10.2l2.4 2.4 4.6-4.9"/>',
        'current'  => '<circle cx="10" cy="10" r="7.5"/><circle cx="10" cy="10" r="3" fill="currentColor"/>',
        'check'    => '<path d="M5 10.5l3.2 3.2L15 6.8"/>',
        'download' => '<path d="M10 3v10"/><path d="M6 9.5l4 4 4-4"/><path d="M4 16.5h12"/>',
        'lock'     => '<rect x="4.5" y="9" width="11" height="8" rx="2"/><path d="M7 9V6.5a3 3 0 016 0V9"/>',
        'play'     => '<path d="M8 5.5v13a1 1 0 001.5.9l11-6.5a1 1 0 000-1.8l-11-6.5A1 1 0 008 5.5z" fill="currentColor" stroke="none"/>',
        'magic'    => '<path d="M3 17L13 7"/><path d="M12 4l1 2 2 1-2 1-1 2-1-2-2-1 2-1z"/><path d="M16 12l.6 1.2L18 14l-1.4.8L16 16l-.6-1.2L14 14l1.4-.8z"/>',
        'google'   => '<circle cx="10" cy="10" r="8.5"/><path d="M14.2 8.6H10v2.2h2.4c-.4 1.2-1.4 1.9-2.6 1.9a3 3 0 010-6c.7 0 1.3.2 1.8.7l1.6-1.5A5.2 5.2 0 0010 4.8a5.2 5.2 0 100 10.4c3 0 4.8-2.1 4.8-5 0-.4 0-.6-.1-.9z" fill="currentColor" stroke="none"/>',
        'search'   => '<circle cx="9" cy="9" r="5.5"/><path d="M13 13l4 4"/>',
        'plus'     => '<path d="M10 4v12M4 10h12"/>',
    ];
    $vb = $name === 'external' ? '0 0 16 16' : ($name === 'play' ? '0 0 24 24' : '0 0 20 20');
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="' . $vb . '" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/** Centered layout for login, sign up, confirmations. Close with auth_shell_end(). */
function auth_shell_start(string $logoSize = ''): void
{
    echo '<div class="page"><div class="auth-top">' . logo(url('login.php'), $logoSize) . '</div><main class="auth-main">';
}

function auth_shell_end(string $footerHtml): void
{
    echo '</main>';
    if ($footerHtml !== '') {
        echo '<footer class="auth-foot">' . $footerHtml . '</footer>';
    }
    echo '</div>';
}

function legal_line(): string
{
    return '<div style="margin-top:6px;font-size:13px">By continuing, you agree to our <a href="' . e(MAIN_SITE) . '/terms">terms</a> and <a href="' . e(MAIN_SITE) . '/privacy">privacy policy</a>.</div>';
}

function alert(?string $message, string $kind = 'danger'): string
{
    return $message ? '<div class="alert alert-' . $kind . '" role="' . ($kind === 'danger' ? 'alert' : 'status') . '">' . e($message) . '</div>' : '';
}

/** Labelled input with an optional error under it. */
function field(string $id, string $name, string $label, string $type, string $value = '', ?string $error = null, array $attrs = []): string
{
    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' ' . e($k) . '="' . e((string) $v) . '"';
    }
    $err = $error ? ' aria-invalid="true" aria-describedby="' . e($id) . '-msg"' : '';
    return '<div class="field' . ($error ? ' field-error' : '') . '"><label class="label" for="' . e($id) . '">' . e($label) . '</label>'
        . '<input class="input" id="' . e($id) . '" name="' . e($name) . '" type="' . e($type) . '"' . ($type !== 'password' ? ' value="' . e($value) . '"' : '') . $err . $extra . '>'
        . ($error ? '<div class="field-msg" id="' . e($id) . '-msg">' . e($error) . '</div>' : '') . '</div>';
}

/** A "check your email" panel used by forgot password and magic link. */
function check_email_box(string $title, string $text, string $backHref): string
{
    return '<div class="auth-box" style="text-align:center;align-items:center">'
        . '<span class="done-mark">' . icon('magic', 32) . '</span>'
        . '<div><h1 class="serif title-xl" style="margin-bottom:12px">' . e($title) . '</h1><p class="muted" style="font-size:17px">' . e($text) . '</p></div>'
        . '<a class="btn btn-lg btn-block" href="' . e($backHref) . '">Back to sign in</a></div>';
}
