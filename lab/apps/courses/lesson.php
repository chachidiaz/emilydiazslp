<?php
// Screen 3 · Lesson
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/catalog.php';

$user = require_user();
$course = course_by_slug((string) ($_GET['c'] ?? ''));
if (!$course) {
    http_response_code(404);
    flash('We couldn\'t find that course.');
    redirect(url(''));
}
if (!can_access_course($user, $course)) {
    flash('You don\'t have access to ' . $course['title'] . '. If you think that\'s a mistake, email hello@emilydiazslp.com.');
    redirect(url(''));
}
$lessons = course_lessons((int) $course['id']);
if (!$lessons) {
    flash($course['title'] . ' has no lessons yet.');
    redirect(url(''));
}
$pos = max(1, (int) ($_GET['l'] ?? 1));
$lesson = null;
foreach ($lessons as $i => $l) {
    if ((int) $l['position'] === $pos) {
        $lesson = $l;
        $nextLesson = $lessons[$i + 1] ?? null;
    }
}
if (!$lesson) {
    redirect(lesson_url($course, (int) $lessons[0]['position']));
}

$progress = progress_map($user, (int) $course['id']);
$isDone = fn(array $l) => !empty($progress[$l['id']]['completed_at']);
$done = $isDone($lesson);
$total = count($lessons);
$doneCount = count(array_filter($lessons, $isDone));
$allDone = $doneCount === $total;
$prefs = prefs($user);
$resumeAt = (int) ($progress[$lesson['id']]['position_seconds'] ?? 0);
$downloads = lesson_downloads((int) $lesson['id']);
$hasCourseDownloads = (bool) course_downloads((int) $course['id']);

// Record that the student opened this lesson, so "Continue learning" points here.
if (!isset($progress[$lesson['id']])) {
    save_position($user, (int) $lesson['id'], 0);
}

/** Video embed: YouTube/Vimeo get an iframe; anything else plays in <video>. */
function video_embed(array $lesson, array $prefs, int $resumeAt): string
{
    $src = (string) $lesson['video_url'];
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{6,})~', $src, $m)) {
        return '<iframe src="https://www.youtube-nocookie.com/embed/' . e($m[1]) . '?rel=0&start=' . $resumeAt . ($prefs['captions'] ? '&cc_load_policy=1' : '') . '" title="' . e($lesson['title']) . '" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $src, $m)) {
        return '<iframe src="https://player.vimeo.com/video/' . e($m[1]) . '?dnt=1' . ($prefs['captions'] ? '&texttrack=en' : '') . '#t=' . $resumeAt . 's" title="' . e($lesson['title']) . '" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
    }
    $track = $lesson['captions_url'] ? '<track kind="captions" srclang="en" label="English" src="' . e($lesson['captions_url']) . '"' . ($prefs['captions'] ? ' default' : '') . '>' : '';
    return '<video id="player" controls playsinline preload="metadata" src="' . e($src) . '" data-resume="' . $resumeAt . '" data-speed="' . e($prefs['speed']) . '">' . $track . '</video>';
}

page_start($lesson['title'] . ' · ' . $course['title']);
echo '<div class="page">';
student_nav('courses', $user);
?>
<main class="wrap wrap-wide" id="lesson"
      data-lesson="<?= (int) $lesson['id'] ?>" data-csrf="<?= e(csrf_token()) ?>" data-progress-url="<?= url('progress.php') ?>"
      data-total="<?= $total ?>" data-done="<?= $doneCount ?>"
      data-next="<?= $nextLesson && $prefs['autoplay'] ? e(lesson_url($course, (int) $nextLesson['position'])) : '' ?>">
  <?php if ($notice = flash()): ?><div style="margin-bottom:24px"><?= alert($notice, 'ok') ?></div><?php endif; ?>
  <nav class="crumbs" aria-label="Breadcrumb" style="margin-bottom:24px">
    <a href="<?= url('') ?>">My courses</a><span aria-hidden="true">/</span>
    <a href="<?= e(lesson_url($course, 1)) ?>"><?= e($course['title']) ?></a><span aria-hidden="true">/</span>
    <span aria-current="page">Lesson <?= (int) $lesson['position'] ?></span>
  </nav>

  <div style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
    <div style="flex:1 1 640px;min-width:0" class="stack gap-32">
      <div class="video">
        <?php if ($lesson['video_url']): ?>
          <?= video_embed($lesson, $prefs, $resumeAt) ?>
        <?php else: ?>
          <div style="position:absolute;inset:0;opacity:.35"><?= course_cover($course, 'aspect-ratio:auto;height:100%') ?></div>
          <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:var(--media-on)">
            <span style="width:84px;height:84px;border-radius:50%;background:var(--media-on);color:var(--ink);display:flex;align-items:center;justify-content:center;opacity:.9"><?= icon('play', 30) ?></span>
            <span style="font-weight:500">The video for this lesson is coming soon.</span>
          </div>
        <?php endif; ?>
      </div>

      <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:20px">
        <div style="min-width:0">
          <div class="eyebrow"><?= e($course['title']) ?> · Lesson <?= (int) $lesson['position'] ?> of <?= $total ?> · <?= (int) $lesson['minutes'] ?> min</div>
          <h1 class="serif title-md" style="margin-top:8px"><?= e($lesson['title']) ?></h1>
        </div>
        <form method="post" action="<?= url('progress.php') ?>" id="complete-form" style="flex:none">
          <?= csrf_field() ?>
          <input type="hidden" name="lesson" value="<?= (int) $lesson['id'] ?>">
          <input type="hidden" name="done" value="<?= $done ? '0' : '1' ?>">
          <button type="submit" id="complete-btn" class="btn btn-lg<?= $done ? ' btn-selected' : ' btn-primary' ?>" aria-pressed="<?= $done ? 'true' : 'false' ?>">
            <span class="ico-todo"<?= $done ? ' hidden' : '' ?>><?= icon('circle', 18) ?></span><span class="ico-done"<?= $done ? '' : ' hidden' ?>><?= icon('check-circle', 18) ?></span>
            <span class="lbl"><?= $done ? 'Completed' : 'Mark as completed' ?></span>
          </button>
        </form>
      </div>

      <section class="card card-pad stack gap-16">
        <h2 class="serif" style="font-size:28px">About this lesson</h2>
        <?php if ($lesson['description']): ?><p class="prose"><?= nl2br(e($lesson['description'])) ?></p><?php endif; ?>
        <?php $practice = array_filter(array_map('trim', explode("\n", (string) $lesson['practice']))); if ($practice): ?>
        <h3 style="margin-top:8px;font-size:16px;font-weight:600">What you'll practice</h3>
        <ul class="prose" style="margin:0;padding-left:22px;display:flex;flex-direction:column;gap:6px;font-size:16px;line-height:1.55">
          <?php foreach ($practice as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </section>
    </div>

    <aside style="flex:1 1 340px;max-width:420px;min-width:0" class="stack gap-24">
      <div class="card card-pad-sm stack gap-16">
        <div>
          <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
            <h2 class="serif title-panel">Course content</h2>
            <span class="small muted" id="progress-label"><?= $doneCount ?> of <?= $total ?> complete</span>
          </div>
          <div class="progress"><span id="progress-bar" style="width:<?= percent($doneCount, $total) ?>%"></span></div>
        </div>
        <?php
        $section = null;
        foreach ($lessons as $l):
            if ($l['section'] !== $section):
                if ($section !== null) echo '</div>';
                $section = $l['section'];
                echo '<div>' . ($section !== '' ? '<div class="eyebrow" style="margin-bottom:4px">' . e($section) . '</div>' : '');
            endif;
            $current = $l['id'] === $lesson['id'];
            $ld = $isDone($l);
        ?>
          <a class="sidelink outline-link<?= $current ? ' current' : '' ?>" href="<?= e(lesson_url($course, (int) $l['position'])) ?>"<?= $current ? ' aria-current="page" id="current-outline"' : '' ?>>
            <?php if ($current): ?>
              <span class="done ico-todo"<?= $ld ? ' hidden' : '' ?>><?= icon('current') ?></span><span class="done ico-done"<?= $ld ? '' : ' hidden' ?>><?= icon('check-circle') ?></span>
            <?php else: ?>
              <span class="<?= $ld ? 'done' : 'todo' ?>"><?= icon($ld ? 'check-circle' : 'circle') ?></span>
            <?php endif; ?>
            <span class="t"><?= (int) $l['position'] ?>. <?= e($l['title']) ?></span><span class="m"><?= (int) $l['minutes'] ?> min</span>
            <span class="sr-only"><?= $ld ? '(completed)' : '' ?></span>
          </a>
        <?php endforeach; if ($section !== null) echo '</div>'; ?>

        <div style="border-top:1px solid var(--line);padding-top:20px" class="stack gap-12">
          <h3 style="font-size:14px;font-weight:600">Course completed?</h3>
          <a class="btn btn-primary btn-block btn-wrap" id="cert-btn" href="<?= url('certificate.php') . '?c=' . e(rawurlencode($course['slug'])) ?>"<?= $allDone ? '' : ' hidden' ?>>Generate your certificate of completion</a>
          <p class="small muted" id="cert-note"<?= $allDone ? ' hidden' : '' ?>>Finish all <?= $total ?> lessons to unlock your certificate of completion.</p>
        </div>
      </div>

      <?php if ($downloads || $hasCourseDownloads): ?>
      <section class="card card-pad-sm stack gap-16" aria-labelledby="downloads-heading">
        <h2 class="serif title-panel" id="downloads-heading">Lesson downloads</h2>
        <?php if ($downloads): ?>
        <div class="stack gap-12"><?php foreach ($downloads as $d) echo download_row($d); ?></div>
        <?php else: ?>
        <p class="small muted">No downloads for this lesson.</p>
        <?php endif; ?>
        <a href="<?= url('downloads.php') . '?c=' . e(rawurlencode($course['slug'])) ?>" style="font-size:14px;font-weight:600;text-underline-offset:3px;align-self:flex-start">View all course downloads</a>
      </section>
      <?php endif; ?>
    </aside>
  </div>
</main>
<script src="<?= url('assets/lesson.js') ?>?v=<?= @filemtime(__DIR__ . '/assets/lesson.js') ?: 1 ?>" defer></script>
<?php
student_footer();
echo '</div>';
page_end();
