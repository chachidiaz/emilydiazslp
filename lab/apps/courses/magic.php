<?php
// Magic link sign-in. Asking for a link and using one are both POSTs: opening the emailed
// link shows a "Sign in" button first, so email scanners that prefetch links can't use it up.
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/catalog.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$email = trim((string) ($_GET['email'] ?? ''));
$error = null;
$sent = false;

if ($token !== '') {
    if (is_post()) {
        check_csrf();
        $user = consume_token($token, 'magic');
        if ($user) {
            if ($user['status'] === 'suspended') {
                flash(suspended_message());
                redirect(url('login.php'));
            }
            sign_in(mark_email_verified($user, false));
            redirect($next);
        }
    }
    $user = peek_token($token, 'magic');
    page_start('Sign in');
    auth_shell_start('lg');
    if ($user) {
?>
<div class="auth-box" style="text-align:center">
  <div>
    <h1 class="serif title-xl" style="margin-bottom:12px">Welcome back.</h1>
    <p class="muted">Sign in as <?= e($user['email']) ?>.</p>
  </div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in</button>
  </form>
</div>
<?php } else { ?>
<div class="auth-box" style="text-align:center">
  <div>
    <h1 class="serif title-xl" style="margin-bottom:12px">This link has expired.</h1>
    <p class="muted">Sign-in links work once, for 15 minutes. Ask for a new one and use the newest email.</p>
  </div>
  <a class="btn btn-primary btn-lg btn-block" href="<?= url('magic.php') ?>">Send a new link</a>
</div>
<?php
    }
    auth_shell_end('<a class="link" href="' . url('login.php') . '">Back to sign in</a>');
    page_end();
    exit;
}

if (current_user()) {
    redirect($next);
}

if (is_post()) {
    check_csrf();
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    if (!valid_email($email)) {
        $error = 'Enter an email like you@example.com.';
    } else {
        $sent = true; // same answer either way, so this can't be used to find accounts
        if (rate_limit('magic-email:' . $email, 5, 3600) && rate_limit('mail-ip:' . client_ip(), 20, 3600)) {
            $user = find_user_by_email($email);
            if ($user && $user['status'] !== 'suspended' && prefs($user)['magic_link']) {
                $link = url('magic.php') . '?token=' . issue_token($user, 'magic', MAGIC_TTL);
                if ($next !== url('')) {
                    $link .= '&next=' . rawurlencode($next);
                }
                mail_magic($user, absolute_url($link));
            } elseif (!$user) {
                mail_no_account($email, absolute_url(url('signup.php')));
            }
        }
    }
}

page_start('Sign in with a magic link');
auth_shell_start('lg');
if ($sent) {
    echo check_email_box('Check your email.', 'We sent an email to ' . $email . '. If you have an account, it has a link that signs you in. The link works once, for the next 15 minutes.', url('login.php'));
} else {
?>
<div class="auth-box">
  <div style="text-align:center">
    <h1 class="serif title-xl" style="margin-bottom:12px">Sign in with a link.</h1>
    <p class="muted">No password needed. We'll email you a link that signs you in.</p>
  </div>
  <form method="post" class="stack gap-16" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <?= field('ml-email', 'email', 'Email', 'email', $email, $error, ['placeholder' => 'you@example.com', 'autocomplete' => 'email', 'required' => 'required']) ?>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Email me a link</button>
  </form>
</div>
<?php
}
auth_shell_end($sent ? '' : '<a class="link" href="' . url('login.php') . '">Back to sign in</a>');
page_end();
