<?php
// Screen 4 · Settings
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/catalog.php';

$user = require_user();
$errors = [];

// ---------- Actions ----------
if (is_post()) {
    check_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'pref') { // switches and speed save as you change them (fetch)
        set_pref($user, (string) ($_POST['key'] ?? ''), ($_POST['key'] ?? '') === 'speed' ? ($_POST['value'] ?? '1') : ($_POST['value'] ?? '') === 'true');
        header('Content-Type: application/json');
        exit(json_encode(['ok' => true]));
    }

    if ($action === 'profile') {
        $first = trim((string) ($_POST['first'] ?? ''));
        $last = trim((string) ($_POST['last'] ?? ''));
        $email = normalize_email((string) ($_POST['email'] ?? ''));
        $name = trim("$first $last");
        if ($first === '') {
            $errors['first'] = 'Add your first name.';
        } elseif (mb_strlen($name) > 120) {
            $errors['first'] = 'Use 120 characters or fewer.';
        }
        if (!valid_email($email)) {
            $errors['email'] = 'Enter an email like you@example.com.';
        } elseif ($email !== $user['email'] && find_user_by_email($email)) {
            $errors['email'] = 'Another account already uses this email.';
        }
        if (!$errors) {
            q('UPDATE users SET name = ? WHERE id = ?', [$name, $user['id']]);
            $msg = 'Profile saved.';
            if ($email !== $user['email']) {
                if (rate_limit('email-change:' . $user['id'], 5, 3600)) {
                    q('UPDATE users SET pending_email = ? WHERE id = ?', [$email, $user['id']]);
                    $link = absolute_url(url('confirm-email.php') . '?token=' . issue_token($user, 'email', 86400));
                    send_mail($email, 'Confirm your new email', "Hi " . $first . ",\n\nTo use this address for your Learn with Emily Diaz, SLP account, open this link within 24 hours:\n\n$link\n\nUntil then, keep signing in with " . $user['email'] . ".");
                    $msg = 'Profile saved. We sent a link to ' . $email . '. Your login email changes when you open it.';
                } else {
                    $msg = 'Profile saved. Too many email changes; try the new email again in an hour.';
                }
            }
            flash($msg);
            redirect(url('settings.php') . '#account');
        }
    }

    if ($action === 'avatar') {
        $f = $_FILES['photo'] ?? null;
        $info = $f && $f['error'] === UPLOAD_ERR_OK && $f['size'] <= 5 * 1024 * 1024 ? @getimagesize($f['tmp_name']) : false;
        $types = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp', IMAGETYPE_GIF => 'imagecreatefromgif'];
        $src = $info && isset($types[$info[2]]) && $info[0] * $info[1] <= 40_000_000 ? @$types[$info[2]]($f['tmp_name']) : false;
        if (!$src) {
            flash('That photo didn\'t work. Use a JPG, PNG or WebP under 5 MB.');
            redirect(url('settings.php') . '#account');
        }
        // Crop to a centered square and re-encode, which also strips anything hidden in the file.
        $side = min(imagesx($src), imagesy($src));
        $out = imagecreatetruecolor(256, 256);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $src, 0, 0, (int) ((imagesx($src) - $side) / 2), (int) ((imagesy($src) - $side) / 2), 256, 256, $side, $side);
        $name = bin2hex(random_bytes(12)) . '.jpg';
        $dir = __DIR__ . '/data/avatars';
        is_dir($dir) || mkdir($dir, 0755, true);
        imagejpeg($out, "$dir/$name", 86);
        if ($user['avatar_file']) {
            @unlink("$dir/" . basename($user['avatar_file']));
        }
        q('UPDATE users SET avatar_file = ? WHERE id = ?', [$name, $user['id']]);
        flash('Photo updated.');
        redirect(url('settings.php') . '#account');
    }

    if ($action === 'avatar_remove') {
        if ($user['avatar_file']) {
            @unlink(__DIR__ . '/data/avatars/' . basename($user['avatar_file']));
            q('UPDATE users SET avatar_file = NULL WHERE id = ?', [$user['id']]);
        }
        flash('Photo removed.');
        redirect(url('settings.php') . '#account');
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        if ($user['password_hash'] && !password_verify($current, $user['password_hash'])) {
            $errors['current'] = 'That isn\'t your current password.';
        } elseif (!rate_limit('pw-change:' . $user['id'], 10, 900)) {
            $errors['current'] = 'Too many tries. Wait 15 minutes.';
        }
        if ($p = password_problem($new)) {
            $errors['new'] = $p;
        }
        if (!$errors) {
            set_password($user, $new);
            sign_out_others($user);
            flash('Password updated. Any other devices were signed out.');
            redirect(url('settings.php') . '#security');
        }
    }

    if ($action === 'signout_others') {
        sign_out_others($user);
        flash('Signed out of every other device.');
        redirect(url('settings.php') . '#security');
    }

    if ($action === 'export') {
        $data = [
            'exported_at' => gmdate('c'),
            'profile' => array_intersect_key($user, array_flip(['name', 'email', 'created_at', 'last_sign_in_at'])),
            'settings' => prefs($user),
            'courses' => q('SELECT c.title, e.granted_at, e.has_access, e.source FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.user_id = ?', [$user['id']])->fetchAll(),
            'progress' => q('SELECT c.title AS course, l.position AS lesson, l.title AS lesson_title, p.completed_at, p.position_seconds FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id JOIN courses c ON c.id = l.course_id WHERE p.user_id = ? ORDER BY c.title, l.position', [$user['id']])->fetchAll(),
        ];
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="my-data-' . gmdate('Y-m-d') . '.json"');
        exit(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    if ($action === 'delete') {
        if (normalize_email((string) ($_POST['confirm'] ?? '')) !== $user['email']) {
            $errors['delete'] = 'Type your login email exactly to confirm.';
        } elseif ($user['role'] === 'admin' && (int) q("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() <= 1) {
            $errors['delete'] = 'You\'re the only admin, so this account can\'t be deleted.';
        } else {
            if ($user['avatar_file']) {
                @unlink(__DIR__ . '/data/avatars/' . basename($user['avatar_file']));
            }
            q('DELETE FROM users WHERE id = ?', [$user['id']]);
            sign_out();
            flash('Your account and progress are deleted.');
            redirect(url('login.php'));
        }
    }
}

// ---------- Page ----------
$prefs = prefs($user);
$notice = flash();
$parts = explode(' ', $user['name'], 2);
$devices = user_devices($user);
$here = session_hash();

function pref_row(string $key, string $title, string $hint, bool $on): string
{
    return '<div class="row"><div class="row-text"><div class="row-title" id="pl-' . $key . '">' . e($title) . '</div><div class="row-hint" style="font-size:14px">' . e($hint) . '</div></div>'
        . '<button type="button" class="switch" role="switch" data-pref="' . $key . '" aria-checked="' . ($on ? 'true' : 'false') . '" aria-labelledby="pl-' . $key . '"></button></div>';
}

page_start('Settings');
echo '<div class="page">';
student_nav('settings', $user);
?>
<div style="flex-grow:1;width:100%;max-width:1200px;margin:0 auto;padding:48px 40px 96px;display:flex;flex-wrap:wrap;gap:48px;align-items:flex-start" class="settings-wrap">
  <nav aria-label="Settings" style="flex:0 0 220px" class="stack gap-24 settings-side">
    <div><h2 class="sidehead">Account</h2>
      <a class="sidelink" href="#account">Profile</a>
      <a class="sidelink" href="#security">Security</a>
      <a class="sidelink" href="#notifications">Notifications</a>
    </div>
    <div><h2 class="sidehead">Learning</h2><a class="sidelink" href="#playback">Playback</a></div>
    <div><h2 class="sidehead">Billing</h2>
      <a class="sidelink" href="#plan">Plan &amp; payment</a>
      <a class="sidelink" href="#data">Data &amp; privacy</a>
    </div>
    <form method="post" action="<?= url('logout.php') ?>"><?= csrf_field() ?><button type="submit" class="sidelink" style="border:0;background:none;font:inherit;cursor:pointer;width:100%">Sign out</button></form>
  </nav>

  <main style="flex:1 1 560px;min-width:0" class="stack gap-24" id="settings" data-csrf="<?= e(csrf_token()) ?>">
    <div>
      <h1 class="serif title-md" style="margin-bottom:6px">Settings</h1>
      <p class="muted">Manage your account, security and how your courses work for you.</p>
    </div>
    <?= alert($notice, preg_match('/didn|Too many|only admin/', (string) $notice) ? 'danger' : 'ok') ?>

    <section class="card card-pad stack gap-20" id="account" style="gap:20px;scroll-margin-top:24px">
      <h2 class="serif title-card">Profile</h2>
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <?= avatar($user, 'avatar avatar-lg') ?>
        <form method="post" enctype="multipart/form-data" id="photo-form" style="display:flex;flex-wrap:wrap;gap:8px">
          <?= csrf_field() ?><input type="hidden" name="action" value="avatar">
          <label class="btn" style="position:relative">Upload photo<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="this.form.submit()"></label>
          <?php if ($user['avatar_file']): ?><button class="btn" type="submit" form="photo-remove">Remove</button><?php endif; ?>
        </form>
        <form method="post" id="photo-remove"><?= csrf_field() ?><input type="hidden" name="action" value="avatar_remove"></form>
      </div>
      <form method="post" class="stack" style="gap:20px" novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="profile">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">
          <?= field('s-first', 'first', 'First name', 'text', $_POST['first'] ?? $parts[0], $errors['first'] ?? null, ['autocomplete' => 'given-name']) ?>
          <?= field('s-last', 'last', 'Last name', 'text', $_POST['last'] ?? ($parts[1] ?? ''), null, ['autocomplete' => 'family-name']) ?>
        </div>
        <div>
          <?= field('s-email', 'email', 'Login email', 'email', $_POST['email'] ?? $user['email'], $errors['email'] ?? null, ['autocomplete' => 'email', 'aria-describedby' => 's-email-hint']) ?>
          <div class="small muted" id="s-email-hint" style="margin-top:6px"><?= $user['pending_email'] ? 'Waiting for you to confirm ' . e($user['pending_email']) . '. Check that inbox.' : 'We\'ll send a confirmation link to the new address before changing it.' ?></div>
        </div>
        <div><button class="btn btn-primary" type="submit">Save profile</button></div>
      </form>
    </section>

    <section class="card card-pad stack" id="security" style="gap:8px;scroll-margin-top:24px">
      <h2 class="serif title-card" style="margin-bottom:12px">Security</h2>
      <form method="post" class="stack gap-16" novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">
          <?php if ($user['password_hash']): ?>
          <?= field('s-cur', 'current', 'Current password', 'password', '', $errors['current'] ?? null, ['placeholder' => 'Current password', 'autocomplete' => 'current-password']) ?>
          <?php endif; ?>
          <?= field('s-new', 'new', $user['password_hash'] ? 'New password' : 'Set a password', 'password', '', $errors['new'] ?? null, ['placeholder' => 'At least 12 characters', 'autocomplete' => 'new-password']) ?>
        </div>
        <div style="padding-bottom:8px"><button class="btn btn-primary" type="submit"><?= $user['password_hash'] ? 'Update password' : 'Set password' ?></button></div>
      </form>
      <?= pref_row('magic_link', 'Magic link sign-in', 'Let me sign in with an emailed link instead of a password.', $prefs['magic_link']) ?>
      <div class="row">
        <div class="row-text"><div class="row-title">Signed-in devices</div>
          <div class="row-hint" style="font-size:14px">
            <?php if (!$devices): ?>Just this device.<?php endif; ?>
            <?php usort($devices, fn($a, $b) => ($b['sid_hash'] === $here) <=> ($a['sid_hash'] === $here)); ?>
            <?php foreach (array_slice($devices, 0, 4) as $d): ?>
            <div><?= e(device_label($d['user_agent'])) ?> — <?= $d['sid_hash'] === $here ? 'this device' : e(time_ago($d['last_seen_at'])) ?></div>
            <?php endforeach; ?>
            <?php if (count($devices) > 4): ?><div>and <?= count($devices) - 4 ?> more</div><?php endif; ?>
          </div>
        </div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="signout_others"><button class="btn" type="submit"<?= count($devices) > 1 ? '' : ' disabled' ?>>Sign out others</button></form>
      </div>
    </section>

    <section class="card card-pad" id="notifications" style="scroll-margin-top:24px">
      <h2 class="serif title-card" style="margin-bottom:8px">Notifications</h2>
      <?= pref_row('notify_new', 'New lessons and courses', 'Email me when something I\'m enrolled in is updated.', $prefs['notify_new']) ?>
      <?= pref_row('notify_weekly', 'Weekly progress summary', 'A short recap every Monday morning.', $prefs['notify_weekly']) ?>
      <?= pref_row('notify_reminders', 'Study reminders', 'A gentle nudge if you haven\'t learned in a few days.', $prefs['notify_reminders']) ?>
    </section>

    <section class="card card-pad" id="playback" style="scroll-margin-top:24px">
      <h2 class="serif title-card" style="margin-bottom:8px">Playback</h2>
      <?= pref_row('autoplay', 'Autoplay next lesson', 'Continue automatically when a lesson ends.', $prefs['autoplay']) ?>
      <?= pref_row('captions', 'Captions on by default', 'Show captions whenever they\'re available.', $prefs['captions']) ?>
      <div class="row">
        <div class="row-text"><div class="row-title" id="speed-label">Default speed</div></div>
        <div class="seg" role="group" aria-labelledby="speed-label" data-pref-seg="speed">
          <?php foreach (['0.75', '1', '1.25', '1.5'] as $sp): ?>
          <button type="button" value="<?= $sp ?>" aria-pressed="<?= $prefs['speed'] === $sp ? 'true' : 'false' ?>"><?= $sp ?>x</button>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="card card-pad stack" id="plan" style="gap:8px;scroll-margin-top:24px">
      <h2 class="serif title-card" style="margin-bottom:8px">Plan &amp; payment</h2>
      <div class="row"><div class="row-text"><div class="row-title">Payment method</div><div class="row-hint" style="font-size:14px">No card saved yet. A card is saved when you buy a course.</div></div></div>
      <div class="row"><div class="row-text"><div class="row-title">Receipts</div><div class="row-hint" style="font-size:14px">Receipts for your purchases will show here.</div></div></div>
    </section>

    <section class="card card-pad" id="data" style="scroll-margin-top:24px">
      <h2 class="serif title-card" style="margin-bottom:8px">Data &amp; privacy</h2>
      <div class="row">
        <div class="row-text"><div class="row-title">Download my data</div><div class="row-hint" style="font-size:14px">Get a copy of your profile and course progress.</div></div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="export"><button class="btn" type="submit">Download</button></form>
      </div>
      <div class="row" style="flex-wrap:wrap">
        <div class="row-text"><div class="row-title">Delete account</div><div class="row-hint" style="font-size:14px">Permanently remove your account and progress. This can't be undone.</div></div>
        <button class="btn btn-danger" type="button" onclick="document.getElementById('delete-form').hidden=false;document.getElementById('s-del').focus()"<?= isset($errors['delete']) ? ' hidden' : '' ?>>Delete account</button>
        <form method="post" id="delete-form" class="stack gap-12" style="width:100%"<?= isset($errors['delete']) ? '' : ' hidden' ?> novalidate>
          <?= csrf_field() ?><input type="hidden" name="action" value="delete">
          <?= field('s-del', 'confirm', 'Type ' . $user['email'] . ' to confirm', 'email', '', $errors['delete'] ?? null, ['autocomplete' => 'off']) ?>
          <div><button class="btn btn-danger" type="submit">Delete my account forever</button></div>
        </form>
      </div>
    </section>
  </main>
</div>
<script>
(() => {
  const root = document.getElementById('settings');
  const save = (key, value) => fetch(location.pathname, {
    method: 'POST', credentials: 'same-origin',
    body: new URLSearchParams({ csrf: root.dataset.csrf, action: 'pref', key, value }),
  }).then((r) => { if (!r.ok) throw r; }).catch(() => alert('That setting didn\'t save. Check your connection and try again.'));
  document.addEventListener('switch:change', (e) => { const k = e.target.dataset.pref; if (k) save(k, String(e.detail.on)); });
  document.addEventListener('choice:change', (e) => { const k = e.target.dataset.prefSeg; if (k) save(k, e.detail.value); });
  // Highlight the sidebar link for the section in view.
  const links = [...document.querySelectorAll('.settings-side .sidelink')];
  const mark = (id) => links.forEach((a) => a.classList.toggle('active', a.hash === '#' + id));
  const io = new IntersectionObserver((entries) => entries.forEach((en) => en.isIntersecting && mark(en.target.id)), { rootMargin: '-20% 0px -70% 0px' });
  document.querySelectorAll('main section[id]').forEach((s) => io.observe(s));
  mark(location.hash.slice(1) || 'account');
})();
</script>
<?php
student_footer();
echo '</div>';
page_end();
