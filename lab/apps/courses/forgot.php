<?php
// Forgot password: emails a reset link. Not in the canvas; built in the same style as Login.
require __DIR__ . '/lib/auth.php';

$email = trim((string) ($_GET['email'] ?? ''));
$error = null;
$sent = false;
if (is_post()) {
    check_csrf();
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    if (!valid_email($email)) {
        $error = 'Enter an email like you@example.com.';
    } else {
        // Same answer whether or not the account exists, so this can't be used to find accounts.
        $sent = true;
        $user = find_user_by_email($email);
        if ($user && $user['status'] !== 'suspended'
            && rate_limit('reset-email:' . $email, 5, 3600) && rate_limit('mail-ip:' . client_ip(), 20, 3600)) {
            mail_reset($user, absolute_url(url('reset.php') . '?token=' . issue_token($user, 'reset', RESET_TTL)));
        }
    }
}

page_start('Reset your password');
auth_shell_start();
if ($sent) {
    echo check_email_box('Check your email.', 'If there\'s an account for ' . $email . ', we just sent it a link to reset the password. The link works for one hour.', url('login.php'));
} else {
?>
<div class="auth-box">
  <div style="text-align:center">
    <h1 class="serif title-xl" style="margin-bottom:12px">Reset your password.</h1>
    <p class="muted">Enter the email you use here and we'll send you a link to choose a new one.</p>
  </div>
  <form method="post" class="stack gap-16" novalidate>
    <?= csrf_field() ?>
    <?= field('fp-email', 'email', 'Email', 'email', $email, $error, ['placeholder' => 'you@example.com', 'autocomplete' => 'email', 'required' => 'required']) ?>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Send reset link</button>
  </form>
</div>
<?php
}
auth_shell_end($sent ? '' : 'Remembered it? <a class="link" href="' . url('login.php') . '">Sign in</a>');
page_end();
