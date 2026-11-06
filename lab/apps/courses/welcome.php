<?php
// Screen 6 · Account confirmation
require __DIR__ . '/lib/auth.php';

$user = require_user();
$next = safe_next($_GET['next'] ?? '');

page_start('You\'re all set');
auth_shell_start();
?>
<div class="auth-box" style="max-width:460px;align-items:center;text-align:center">
  <span class="done-mark"><?= icon('check', 34) ?></span>
  <div>
    <h1 class="serif title-xl" style="margin-bottom:12px">You're all set, <?= e(first_name($user)) ?>.</h1>
    <p class="muted" style="font-size:17px">Your account has been created. Your courses are ready and waiting for you.</p>
  </div>
  <a class="btn btn-primary btn-lg btn-block" href="<?= e($next) ?>"><?= $next === url('') ? 'Go to my courses' : 'Continue' ?></a>
</div>
<?php
auth_shell_end('<span style="font-size:13px">Need help? <a class="link" href="mailto:hello@emilydiazslp.com">Contact support</a></span>');
page_end();
