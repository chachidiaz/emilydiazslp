<?php
// Component sheet: every token and shared component in one place (build step 1).
require __DIR__ . '/lib/ui.php';

$tokens = [
    ['--bg', 'Page background'], ['--surface', 'Cards, inputs'], ['--surface-sunken', 'Active links, tracks'],
    ['--line', 'Borders, dividers'], ['--line-strong', 'Button and chip borders'], ['--ink', 'Text, headings'],
    ['--muted', 'Secondary text'], ['--accent', 'Plum accent'], ['--accent-hover', 'Primary hover'],
    ['--accent-soft', 'Badges, selected'], ['--danger', 'Danger text'], ['--danger-soft', 'Danger fill'],
];

page_start('Components');
student_nav('courses', 'Jane Smith');
?>
<main class="wrap stack gap-56">

  <section class="stack gap-16">
    <div class="eyebrow">Build step 1</div>
    <h1 class="serif title-lg">Components</h1>
    <p class="muted" style="max-width:62ch">Tokens and shared pieces for <?= e(SITE_NAME) ?>, built from <code>hearth.css</code> in the design canvas. Every screen uses these classes and the helpers in <code>lib/ui.php</code>. Sample names only.</p>
  </section>

  <section class="stack gap-24" aria-labelledby="h-color">
    <h2 class="serif title-sm" id="h-color">Color tokens</h2>
    <div class="grid-stats" style="--n:4">
      <?php foreach ($tokens as [$var, $use]): ?>
      <div class="card" style="overflow:hidden">
        <div style="height:72px;background:var(<?= $var ?>);border-bottom:1px solid var(--line)"></div>
        <div style="padding:12px 16px"><code class="small"><?= $var ?></code><div class="small muted"><?= e($use) ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="stack gap-16" aria-labelledby="h-type">
    <h2 class="serif title-sm" id="h-type">Type</h2>
    <div class="card card-pad stack gap-16">
      <div class="serif title-xl">Welcome back.</div>
      <div class="serif title-md">Wet-on-wet washes</div>
      <div class="serif title-card">Watercolor Foundations</div>
      <div class="section-title">Section title, 17px sans semibold</div>
      <div class="eyebrow">Eyebrow · 12px uppercase</div>
      <p>Body text is Inter 15px at line height 1.5. Labels are 13px and muted.</p>
      <p class="prose">Long-form lesson copy runs 17px at line height 1.65 in a softer ink.</p>
    </div>
  </section>

  <section class="stack gap-16" aria-labelledby="h-btn">
    <h2 class="serif title-sm" id="h-btn">Buttons</h2>
    <div class="card card-pad" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center">
      <button class="btn btn-primary btn-lg" type="button">Resume lesson</button>
      <button class="btn btn-primary" type="button">Save changes</button>
      <button class="btn" type="button">Previous</button>
      <button class="btn btn-lg" type="button"><?= icon('google') ?> Continue with Google</button>
      <button class="btn btn-lg btn-selected" type="button" aria-pressed="true"><?= icon('check-circle', 18) ?> Completed</button>
      <button class="btn btn-danger" type="button">Delete student</button>
      <button class="btn" type="button" disabled>Disabled</button>
    </div>
  </section>

  <section class="stack gap-16" aria-labelledby="h-form">
    <h2 class="serif title-sm" id="h-form">Inputs</h2>
    <div class="card card-pad" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px">
      <div class="field"><label class="label" for="c-email">Email</label><input class="input" id="c-email" type="email" placeholder="you@example.com"></div>
      <div class="field field-error"><label class="label" for="c-pass">Password</label><input class="input" id="c-pass" type="password" value="short" aria-describedby="c-pass-msg" aria-invalid="true"><div class="field-msg" id="c-pass-msg">Use at least 12 characters.</div></div>
      <div class="field"><label class="label" for="c-course">Course</label><select class="input" id="c-course"><option>All courses</option><option>Watercolor Foundations</option></select></div>
      <div class="field"><span class="label">Card</span>
        <div class="grp">
          <input class="input" placeholder="1234 1234 1234 1234" aria-label="Card number" inputmode="numeric">
          <div class="split"><input class="input" placeholder="MM / YY" aria-label="Expiry"><input class="input" placeholder="CVC" aria-label="CVC"></div>
        </div>
      </div>
      <label class="check"><input type="checkbox" checked> Save my information for faster checkout</label>
    </div>
  </section>

  <section class="stack gap-16" aria-labelledby="h-chip">
    <h2 class="serif title-sm" id="h-chip">Chips, badges, avatars</h2>
    <div class="card card-pad stack gap-24">
      <div class="chips" data-chips role="group" aria-label="Filter courses">
        <button type="button" class="chip on" aria-pressed="true">All</button>
        <button type="button" class="chip" aria-pressed="false">In progress</button>
        <button type="button" class="chip" aria-pressed="false">Completed</button>
        <button type="button" class="chip" aria-pressed="false">Not started</button>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
        <?= status_badge('active') ?> <?= status_badge('completed') ?> <?= status_badge('invited') ?> <?= status_badge('suspended') ?> <?= status_badge('refunded') ?>
        <span class="badge">Order bump</span><span class="badge">Upsell</span>
      </div>
      <div style="display:flex;gap:12px;align-items:center">
        <span class="avatar avatar-lg">JS</span><span class="avatar">ML</span><span class="avatar avatar-sm">PP</span>
      </div>
    </div>
  </section>

  <section class="stack gap-16" aria-labelledby="h-ctl">
    <h2 class="serif title-sm" id="h-ctl">Switches, segmented control, progress</h2>
    <div class="card card-pad">
      <div class="row"><div class="row-text"><div class="row-title">Email me when a new lesson is added</div><div class="row-hint">A short note, no more than once a week.</div></div><?= switch_control('c-sw1', true, 'New lesson emails') ?></div>
      <div class="row"><div class="row-text"><div class="row-title">Watercolor Foundations</div><div class="row-hint">Granted Nov 2 · purchase</div></div><div style="display:flex;align-items:center;gap:8px"><?= switch_control('c-sw2', true, 'Access to Watercolor Foundations', 'Has access', 'No access') ?></div></div>
      <div class="row"><div class="row-text"><div class="row-title">Default speed</div></div>
        <div class="seg" role="group" aria-label="Default speed"><button type="button" aria-pressed="false">0.75x</button><button type="button" aria-pressed="true">1x</button><button type="button" aria-pressed="false">1.25x</button><button type="button" aria-pressed="false">1.5x</button></div>
      </div>
      <div class="row" style="display:block"><div style="max-width:320px"><?= progress_bar(3, 8) ?></div></div>
    </div>
  </section>

  <section class="stack gap-16" aria-labelledby="h-card">
    <h2 class="serif title-sm" id="h-card">Cards</h2>
    <div class="grid-3">
      <a class="course" href="#">
        <div class="cover" style="background:var(--accent-soft)"></div>
        <div class="course-body">
          <div><div class="eyebrow">Course</div><h3 class="serif title-card" style="margin:6px 0 4px">Course title</h3><div class="small muted">with Emily Diaz</div></div>
          <div><?= progress_bar(3, 8) ?></div>
        </div>
      </a>
      <div class="stack gap-16">
        <div class="card stat"><div class="eyebrow">Total students</div><div class="stat-value">248</div></div>
        <div class="card stat"><div class="eyebrow">Revenue, 30 days</div><div class="stat-value">$8,412</div></div>
      </div>
      <div class="stack gap-12">
        <a class="file" href="#"><span class="file-type">PDF</span><span style="flex-grow:1;min-width:0"><span class="file-name">Lesson 4 practice sheet</span><span class="file-size">1.2 MB</span></span><?= icon('download') ?></a>
        <div class="bump">
          <?= switch_control('c-bump', true, 'Add the one-time add-on') ?>
          <div><div class="eyebrow" style="color:var(--accent-ink)">One-time add-on</div><div style="font-weight:600;margin-top:4px">Add-on guide · $19 <span class="strike">$39</span></div><div class="small muted">One line about the add-on.</div></div>
        </div>
      </div>
    </div>
  </section>

  <section class="stack gap-16" aria-labelledby="h-table">
    <h2 class="serif title-sm" id="h-table">Table</h2>
    <div class="card table" style="--cols:2.4fr 1fr 1.2fr 1fr 40px">
      <div class="trow th"><span>Student</span><span class="hide-sm">Access</span><span class="hide-sm">Last sign-in</span><span>Status</span><span class="hide-sm"></span></div>
      <a class="trow" href="#"><div class="who"><span class="avatar avatar-sm">JS</span><div><div class="who-name">Jane Smith</div><div class="who-mail">jane@example.com</div></div></div><div class="hide-sm">3 courses</div><div class="hide-sm muted">Today</div><div><?= status_badge('active') ?></div><span class="hide-sm muted"><?= icon('chevron', 18) ?></span></a>
      <a class="trow" href="#"><div class="who"><span class="avatar avatar-sm">TB</span><div><div class="who-name">Sample Student</div><div class="who-mail">student@example.com</div></div></div><div class="hide-sm">1 course</div><div class="hide-sm muted">Oct 12</div><div><?= status_badge('suspended') ?></div><span class="hide-sm muted"><?= icon('chevron', 18) ?></span></a>
    </div>
    <div class="pager"><span>Showing 1–2 of 2</span><div style="display:flex;gap:8px"><button type="button" class="btn" disabled>Previous</button><button type="button" class="btn" disabled>Next</button></div></div>
  </section>

</main>
<?php
student_footer();
page_end();
