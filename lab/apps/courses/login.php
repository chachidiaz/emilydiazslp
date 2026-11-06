<?php
// Screen 1 · Login
require __DIR__ . '/lib/auth.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if (current_user()) {
    redirect($next);
}

$email = '';
$error = null;
if (is_post()) {
    check_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    [$user, $error] = password_login($email, (string) ($_POST['password'] ?? ''));
    if ($user) {
        sign_in($user);
        redirect($next);
    }
}
$notice = flash();
$q = $next !== url('') ? '?next=' . rawurlencode($next) : '';

page_start('Sign in');
auth_shell_start('lg');
?>
<div class="auth-box" style="text-align:center">
  <div>
    <h1 class="serif title-xl" style="margin-bottom:12px">Welcome back.</h1>
    <p class="muted">Sign in to pick up your courses where you left off.</p>
  </div>
  <?= alert($notice, str_contains((string) $notice, 'paused') ? 'danger' : 'ok') ?>
  <?= alert($error) ?>
  <div class="stack gap-12">
    <?php if (google_enabled()): ?>
    <a class="btn btn-lg btn-block" href="<?= url('google.php') . $q ?>"><?= icon('google') ?> Continue with Google</a>
    <?php endif; ?>
    <a class="btn btn-lg btn-block" href="<?= url('magic.php') . $q ?>"><?= icon('magic') ?> Continue with magic link</a>
  </div>
  <div class="or">or use your email</div>
  <form method="post" class="stack gap-16" style="text-align:left" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <?= field('login-email', 'email', 'Email', 'email', $email, null, ['placeholder' => 'you@example.com', 'autocomplete' => 'email', 'required' => 'required']) ?>
    <?= field('login-password', 'password', 'Password', 'password', '', null, ['placeholder' => 'Your password', 'autocomplete' => 'current-password', 'required' => 'required']) ?>
    <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in with email</button>
  </form>
  <a href="<?= url('forgot.php') ?>" class="link-quiet" style="font-size:14px">Forgot password?</a>
</div>
<?php
auth_shell_end('Don\'t have an account? <a class="link" href="' . url('signup.php') . $q . '">Sign up</a>' . legal_line());
page_end();
