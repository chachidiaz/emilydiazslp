<?php
// All downloads for one course ("View all course downloads" on the Lesson screen).
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/catalog.php';

$user = require_user();
$course = course_by_slug((string) ($_GET['c'] ?? ''));
if (!$course || !can_access_course($user, $course)) {
    flash('You don\'t have access to that course.');
    redirect(url(''));
}
$groups = [];
foreach (course_downloads((int) $course['id']) as $d) {
    $key = $d['position'] ? 'Lesson ' . $d['position'] . ' · ' . $d['lesson_title'] : 'Course files';
    $groups[$key][] = $d;
}

page_start('Downloads · ' . $course['title']);
echo '<div class="page">';
student_nav('courses', $user);
?>
<main class="wrap stack gap-32" style="max-width:860px">
  <?= alert(flash(), 'ok') ?>
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="<?= url('') ?>">My courses</a><span aria-hidden="true">/</span>
    <a href="<?= e(lesson_url($course, 1)) ?>"><?= e($course['title']) ?></a><span aria-hidden="true">/</span>
    <span aria-current="page">Downloads</span>
  </nav>
  <div>
    <h1 class="serif title-md">Course downloads</h1>
    <p class="muted" style="margin-top:8px">Every worksheet and file for <?= e($course['title']) ?>.</p>
  </div>
  <?php if (!$groups): ?>
  <div class="card card-pad"><p class="muted">This course has no downloads yet.</p></div>
  <?php endif; foreach ($groups as $label => $items): ?>
  <section class="stack gap-12">
    <h2 class="eyebrow"><?= e($label) ?></h2>
    <?php foreach ($items as $d) echo download_row($d); ?>
  </section>
  <?php endforeach; ?>
</main>
<?php
student_footer();
echo '</div>';
page_end();
