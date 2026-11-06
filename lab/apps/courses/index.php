<?php
// Screen 2 · My courses
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/catalog.php';

$user = require_user();
$notice = flash();
$filters = ['all' => 'All', 'in-progress' => 'In progress', 'completed' => 'Completed', 'not-started' => 'Not started'];
$filter = array_key_exists($_GET['filter'] ?? '', $filters) ? $_GET['filter'] : 'all';

$all = user_courses($user);
$courses = $filter === 'all' ? $all : array_values(array_filter($all, fn($c) => course_state($c) === $filter));
$next = continue_target($user, $all);

$hour = (int) (new DateTime('now', new DateTimeZone((string) config('timezone', 'America/Phoenix'))))->format('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

page_start('My courses');
echo '<div class="page">';
student_nav('courses', $user);
?>
<main class="wrap stack gap-56">
  <?= alert($notice, str_contains((string) $notice, 'access') ? 'danger' : 'ok') ?>

  <section class="card card-pad" style="padding:36px;display:flex;flex-direction:column;gap:28px">
    <div>
      <div class="eyebrow"><?= $next ? 'Continue learning' : 'Welcome' ?></div>
      <h1 class="serif title-lg" style="margin:10px 0 8px"><?= e($greeting) ?>, <?= e(first_name($user)) ?>.</h1>
      <?php if ($next): ?>
      <p class="muted" style="font-size:17px"><?= e($next['course']['title']) ?> · Lesson <?= (int) $next['lesson']['position'] ?>, <?= e($next['lesson']['title']) ?> · <?= (int) $next['lesson']['minutes'] ?> min</p>
      <?php elseif ($all): ?>
      <p class="muted" style="font-size:17px">You've finished every course you have. Nice work.</p>
      <?php else: ?>
      <p class="muted" style="font-size:17px">You don't have any courses yet. When you buy one, it shows up here.</p>
      <?php endif; ?>
    </div>
    <?php if ($next): ?>
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:20px">
      <a class="btn btn-primary btn-lg" href="<?= e(lesson_url($next['course'], (int) $next['lesson']['position'])) ?>"><?= $next['started'] ? 'Resume lesson' : 'Start course' ?></a>
      <div style="flex:1 1 200px;max-width:280px">
        <?= progress_bar((int) $next['course']['done_count'], (int) $next['course']['lesson_count'], false) ?>
        <div class="small muted" style="margin-top:8px"><?= (int) $next['course']['done_count'] ?> of <?= (int) $next['course']['lesson_count'] ?> lessons complete</div>
      </div>
    </div>
    <?php elseif (!$all): ?>
    <div><a class="btn btn-lg" href="<?= e(MAIN_SITE) ?>">Browse courses on the main site <?= icon('external', 16) ?></a></div>
    <?php endif; ?>
  </section>

  <?php if ($all): ?>
  <section aria-labelledby="courses-heading">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:24px">
      <h2 class="serif title-sm" id="courses-heading">My courses</h2>
      <nav class="chips" aria-label="Filter courses">
        <?php foreach ($filters as $key => $label): ?>
        <a class="chip<?= $filter === $key ? ' on' : '' ?>" href="<?= url('') . ($key === 'all' ? '' : '?filter=' . $key) ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>

    <?php if (!$courses): ?>
    <div class="card card-pad" style="text-align:center"><p class="muted">No <?= e(strtolower($filters[$filter])) ?> courses right now.</p></div>
    <?php else: ?>
    <div class="grid-3">
      <?php foreach ($courses as $c):
          $state = course_state($c);
          $done = (int) $c['done_count'];
          $total = (int) $c['lesson_count'];
          $pct = percent($done, $total);
          $target = $state === 'completed' ? 1 : null;
          if ($target === null) {
              $t = continue_target($user, [$c]);
              $target = $t ? (int) $t['lesson']['position'] : 1;
          }
      ?>
      <a class="course" href="<?= e(lesson_url($c, $target)) ?>">
        <?= course_cover($c) ?>
        <div class="course-body">
          <div>
            <div class="eyebrow"><?= e($c['category']) ?></div>
            <h3 class="serif title-card" style="margin:6px 0 4px"><?= e($c['title']) ?></h3>
            <?php if ($c['instructor']): ?><div class="small muted" style="font-size:14px">with <?= e($c['instructor']) ?></div><?php endif; ?>
          </div>
          <div>
            <?= progress_bar($done, $total, false) ?>
            <div class="progress-meta">
              <?php if ($state === 'completed'): ?>
              <span><?= $done ?> of <?= $total ?> lessons</span><span class="badge">Completed</span>
              <?php elseif ($state === 'not-started'): ?>
              <span><?= $total ?> lessons</span><span class="badge">Not started</span>
              <?php else: ?>
              <span><?= $done ?> of <?= $total ?> lessons</span><span><?= $pct ?>%</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</main>
<?php
student_footer();
echo '</div>';
page_end();
