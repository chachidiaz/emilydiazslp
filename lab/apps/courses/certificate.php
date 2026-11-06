<?php
// Certificate of completion: a printable page (Print → Save as PDF). Only after every lesson is done.
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/catalog.php';

$user = require_user();
$course = course_by_slug((string) ($_GET['c'] ?? ''));
if (!$course || !can_access_course($user, $course)) {
    flash('You don\'t have access to that course.');
    redirect(url(''));
}
$row = q('SELECT COUNT(*) AS total, SUM(p.completed_at IS NOT NULL) AS done, MAX(p.completed_at) AS finished
          FROM lessons l LEFT JOIN lesson_progress p ON p.lesson_id = l.id AND p.user_id = ? WHERE l.course_id = ?', [$user['id'], $course['id']])->fetch();
if (!$row['total'] || (int) $row['done'] < (int) $row['total']) {
    flash('Finish every lesson in ' . $course['title'] . ' to get your certificate.');
    redirect(lesson_url($course, 1));
}
$finished = (new DateTime($row['finished'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone((string) config('timezone', 'America/Phoenix')));
$number = strtoupper(substr(hash('sha256', $user['id'] . '|' . $course['id'] . '|' . $row['finished']), 0, 10));

page_start('Certificate · ' . $course['title']);
?>
<style>
  @page { size: landscape; margin: 0; }
  .cert-wrap { padding: 40px 16px; display: flex; flex-direction: column; align-items: center; gap: 24px; }
  .cert { width: 100%; max-width: 1000px; aspect-ratio: 1.414; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-card); padding: 6%; display: flex; flex-direction: column; align-items: center; justify-content: space-between; text-align: center; position: relative; }
  .cert::before { content: ''; position: absolute; inset: 18px; border: 2px solid var(--accent); border-radius: 12px; pointer-events: none; }
  @media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .cert-wrap { padding: 0; }
    .cert { border: 0; border-radius: 0; max-width: none; height: 100vh; aspect-ratio: auto; }
  }
</style>
<div class="cert-wrap">
  <div class="no-print" style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
    <a class="btn" href="<?= e(lesson_url($course, 1)) ?>">Back to course</a>
    <button class="btn btn-primary" type="button" onclick="window.print()">Print or save as PDF</button>
  </div>
  <div class="cert">
    <img src="<?= LOGO_SRC ?>" alt="Emily Diaz" style="width:150px;height:auto">
    <div class="stack gap-16" style="align-items:center">
      <div class="eyebrow">Certificate of completion</div>
      <div class="serif" style="font-size:clamp(32px,6vw,60px);line-height:1.05"><?= e($user['name']) ?></div>
      <p class="muted" style="font-size:17px;max-width:46ch">has completed all <?= (int) $row['total'] ?> lessons of</p>
      <div class="serif" style="font-size:clamp(24px,4vw,40px);color:var(--accent)"><?= e($course['title']) ?></div>
    </div>
    <div style="display:flex;gap:48px;flex-wrap:wrap;justify-content:center" class="small muted">
      <span>Completed <?= e($finished->format('F j, Y')) ?></span>
      <span><?= e(SITE_NAME) ?></span>
      <span>No. <?= e($number) ?></span>
    </div>
  </div>
</div>
<?php
page_end();
