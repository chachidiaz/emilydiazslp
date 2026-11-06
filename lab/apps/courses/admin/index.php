<?php
// Screen 10 · Admin: Students. Placeholder until build step 5; for now it proves the admin role.
require __DIR__ . '/../lib/auth.php';

$user = require_admin();

page_start('Students');
admin_start('students', $user['name']);
?>
<div class="page-head">
  <div><h1 class="serif title-md">Students</h1><p class="muted" style="margin-top:8px">Manage who can sign in and which courses they can open.</p></div>
</div>
<div class="card card-pad"><p class="muted">The students table arrives in build step 5. You're signed in as an admin (<?= e($user['email']) ?>).</p></div>
<?php
admin_end();
page_end();
