<?php
// Confirms a login email change from the link sent to the new address.
// Opening the link shows a button first, so email scanners can't use it up.
require __DIR__ . '/lib/auth.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$user = peek_token($token, 'email');

if ($user && is_post()) {
    check_csrf();
    $user = consume_token($token, 'email');
    $new = $user['pending_email'] ?? null;
    if ($user && $new) {
        if (find_user_by_email($new)) {
            q('UPDATE users SET pending_email = NULL WHERE id = ?', [$user['id']]);
            flash('Another account started using ' . $new . ', so your email wasn\'t changed.');
        } else {
            q('UPDATE users SET email = ?, pending_email = NULL WHERE id = ?', [$new, $user['id']]);
            mark_email_verified(find_user((int) $user['id']), true);
            flash('Your login email is now ' . $new . '.');
        }
    }
    redirect(current_user() ? url('settings.php') . '#account' : url('login.php'));
}

page_start('Confirm your new email');
auth_shell_start();
if ($user && $user['pending_email']) {
?>
<div class="auth-box" style="text-align:center">
  <div>
    <h1 class="serif title-xl" style="margin-bottom:12px">Confirm your new email.</h1>
    <p class="muted">Change the login email for <?= e($user['name']) ?> to <?= e($user['pending_email']) ?>.</p>
  </div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <button class="btn btn-primary btn-lg btn-block" type="submit">Confirm email</button>
  </form>
</div>
<?php } else { ?>
<div class="auth-box" style="text-align:center">
  <div>
    <h1 class="serif title-xl" style="margin-bottom:12px">This link has expired.</h1>
    <p class="muted">Email change links work once, for 24 hours. Change your email again in Settings to get a new one.</p>
  </div>
  <a class="btn btn-primary btn-lg btn-block" href="<?= url('settings.php') ?>">Go to settings</a>
</div>
<?php
}
auth_shell_end('');
page_end();
