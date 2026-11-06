<?php
// Screen 5 · Sign up (one step)
require __DIR__ . '/lib/auth.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if (current_user()) {
    redirect($next);
}

$name = $email = '';
$errors = [];
$formError = null;
if (is_post()) {
    check_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || mb_strlen($name) > 120) {
        $errors['name'] = $name === '' ? 'Add your name.' : 'Use 120 characters or fewer.';
    }
    if (!valid_email($email)) {
        $errors['email'] = 'Enter an email like you@example.com.';
    } elseif (find_user_by_email($email)) {
        $errors['email'] = 'There\'s already an account with this email. Sign in or reset your password.';
    }
    if ($p = password_problem($password)) {
        $errors['password'] = $p;
    }
    if (!$errors && !rate_limit('signup-ip:' . client_ip(), 10, 3600)) {
        $formError = 'Too many new accounts from this connection. Try again in an hour.';
    }
    if (!$errors && !$formError) {
        try {
            $user = create_user($name, $email, $password);
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $errors['email'] = 'There\'s already an account with this email. Sign in or reset your password.';
        }
        if (!$errors) {
            sign_in($user);
            redirect(url('welcome.php') . ($next !== url('') ? '?next=' . rawurlencode($next) : ''));
        }
    }
}
$q = $next !== url('') ? '?next=' . rawurlencode($next) : '';

page_start('Create your account');
auth_shell_start();
?>
<div class="auth-box" style="max-width:460px">
  <div>
    <h1 class="serif" style="font-size:52px;line-height:1.04;margin-bottom:12px">Create your account.</h1>
    <p class="muted">It takes about a minute. You can change any of this later in Settings.</p>
  </div>
  <?= alert($formError) ?>
  <form method="post" class="stack gap-32" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="stack gap-16">
      <?= field('su-name', 'name', 'Full name', 'text', $name, $errors['name'] ?? null, ['placeholder' => 'Jane Smith', 'autocomplete' => 'name', 'required' => 'required']) ?>
      <?= field('su-email', 'email', 'Email', 'email', $email, $errors['email'] ?? null, ['placeholder' => 'you@example.com', 'autocomplete' => 'email', 'required' => 'required']) ?>
      <?= field('su-password', 'password', 'Password', 'password', '', $errors['password'] ?? null, ['placeholder' => 'At least 12 characters', 'autocomplete' => 'new-password', 'minlength' => MIN_PASSWORD, 'required' => 'required']) ?>
    </div>
    <div class="stack gap-16">
      <button class="btn btn-primary btn-lg btn-block" type="submit">Create account</button>
      <div style="text-align:center;font-size:14px" class="muted">Already have an account? <a class="link" href="<?= url('login.php') . $q ?>">Sign in</a></div>
    </div>
  </form>
</div>
<?php
auth_shell_end('<span style="font-size:13px">By continuing, you agree to our <a href="' . e(MAIN_SITE) . '/terms">terms</a> and <a href="' . e(MAIN_SITE) . '/privacy">privacy policy</a>.</span>');
page_end();
