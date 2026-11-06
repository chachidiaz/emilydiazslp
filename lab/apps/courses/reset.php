<?php
// Choose a new password from an emailed reset link.
require __DIR__ . '/lib/auth.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$user = peek_token($token, 'reset');
$error = null;

if ($user && is_post()) {
    check_csrf();
    $password = (string) ($_POST['password'] ?? '');
    $error = password_problem($password);
    if (!$error) {
        $user = consume_token($token, 'reset');
        if ($user) {
            set_password($user, $password);
            bump_session_version($user);           // sign out every other browser
            $user = mark_email_verified($user, true);
            if ($user['status'] === 'suspended') {
                flash(suspended_message());
                redirect(url('login.php'));
            }
            sign_in($user);
            flash('Your password is changed.');
            redirect(url(''));
        }
    }
}

page_start('Choose a new password');
auth_shell_start();
if (!$user) {
?>
<div class="auth-box" style="text-align:center">
  <div>
    <h1 class="serif title-xl" style="margin-bottom:12px">This link has expired.</h1>
    <p class="muted">Reset links work once, for one hour. Ask for a new one and use the newest email.</p>
  </div>
  <a class="btn btn-primary btn-lg btn-block" href="<?= url('forgot.php') ?>">Send a new link</a>
</div>
<?php } else { ?>
<div class="auth-box">
  <div style="text-align:center">
    <h1 class="serif title-xl" style="margin-bottom:12px">Choose a new password.</h1>
    <p class="muted">For <?= e($user['email']) ?>. You'll be signed out everywhere else.</p>
  </div>
  <form method="post" class="stack gap-16" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <?= field('rp-password', 'password', 'New password', 'password', '', $error, ['placeholder' => 'At least 12 characters', 'autocomplete' => 'new-password', 'minlength' => MIN_PASSWORD, 'required' => 'required']) ?>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Save new password</button>
  </form>
</div>
<?php
}
auth_shell_end('<a class="link" href="' . url('login.php') . '">Back to sign in</a>');
page_end();
