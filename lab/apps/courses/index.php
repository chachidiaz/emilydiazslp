<?php
// Screen 2 · My courses. Placeholder until build step 3; for now it proves sign-in works.
require __DIR__ . '/lib/auth.php';

$user = require_user();
$notice = flash();

page_start('My courses');
echo '<div class="page">';
student_nav('courses', $user['name']);
?>
<main class="wrap stack gap-24">
  <?= alert($notice, 'ok') ?>
  <div class="card card-pad stack gap-16">
    <div class="eyebrow">Signed in</div>
    <h1 class="serif title-lg">Hello, <?= e(first_name($user)) ?>.</h1>
    <p class="muted">You're signed in as <?= e($user['email']) ?>. Your courses will show here once build step 3 is done.</p>
    <div style="display:flex;flex-wrap:wrap;gap:12px">
      <?php if (is_admin($user)): ?><a class="btn btn-primary" href="<?= url('admin/') ?>">Open admin</a><?php endif; ?>
      <form method="post" action="<?= url('logout.php') ?>"><?= csrf_field() ?><button class="btn" type="submit">Sign out</button></form>
    </div>
  </div>
</main>
<?php
student_footer();
echo '</div>';
page_end();
