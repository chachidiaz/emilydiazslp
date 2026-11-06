// Lesson page: "Mark as completed" without a reload, and the video player's resume
// position, default speed and autoplay-next (from the student's Playback settings).
(() => {
  const root = document.getElementById('lesson');
  if (!root) return;
  const lessonId = root.dataset.lesson;
  const csrf = root.dataset.csrf;
  const endpoint = root.dataset.progressUrl;

  const send = (fields, keepalive = false) => {
    const body = new URLSearchParams({ csrf, lesson: lessonId, ...fields });
    return fetch(endpoint, { method: 'POST', body, keepalive, headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then((r) => (r.ok ? r.json() : Promise.reject(r)));
  };

  // ----- Mark as completed -----
  const form = document.getElementById('complete-form');
  const btn = document.getElementById('complete-btn');
  const showDone = (done) => {
    btn.classList.toggle('btn-primary', !done);
    btn.classList.toggle('btn-selected', done);
    btn.setAttribute('aria-pressed', String(done));
    btn.querySelector('.lbl').textContent = done ? 'Completed' : 'Mark as completed';
    form.elements.done.value = done ? '0' : '1';
    for (const scope of [btn, document.getElementById('current-outline')]) {
      if (!scope) continue;
      scope.querySelector('.ico-todo').hidden = done;
      scope.querySelector('.ico-done').hidden = !done;
    }
  };
  const showCount = (done, total) => {
    document.getElementById('progress-label').textContent = `${done} of ${total} complete`;
    document.getElementById('progress-bar').style.width = `${total ? Math.round((done / total) * 100) : 0}%`;
    const all = total > 0 && done >= total;
    document.getElementById('cert-btn').hidden = !all;
    document.getElementById('cert-note').hidden = all;
  };
  const setDone = (done) => {
    showDone(done); // update right away; undo if the save fails
    return send({ done: done ? '1' : '0' })
      .then((r) => showCount(r.done, r.total))
      .catch(() => { showDone(!done); alert('That didn\'t save. Check your connection and try again.'); });
  };
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    setDone(form.elements.done.value === '1');
  });

  // ----- Video player -----
  const video = document.getElementById('player');
  if (!video) return;
  const resume = Number(video.dataset.resume || 0);
  video.playbackRate = Number(video.dataset.speed || 1);
  const applyStart = () => {
    if (resume > 5 && resume < video.duration - 3) video.currentTime = resume;
    video.playbackRate = Number(video.dataset.speed || 1);
  };
  // The metadata may already be loaded by the time this script runs.
  if (video.readyState >= 1) applyStart(); else video.addEventListener('loadedmetadata', applyStart, { once: true });

  let lastSaved = resume;
  const savePosition = (keepalive = false) => {
    const t = Math.floor(video.currentTime);
    if (Math.abs(t - lastSaved) < 5) return;
    lastSaved = t;
    send({ seconds: String(t) }, keepalive).catch(() => {});
  };
  video.addEventListener('timeupdate', () => { if (!video.paused && Math.floor(video.currentTime) - lastSaved >= 15) savePosition(); });
  video.addEventListener('pause', () => savePosition());
  document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') savePosition(true); });

  video.addEventListener('ended', () => {
    lastSaved = 0;
    send({ seconds: '0' }).catch(() => {});
    const go = () => { if (root.dataset.next) location.href = root.dataset.next; };
    if (btn.getAttribute('aria-pressed') !== 'true') setDone(true).then(go); else go();
  });
})();
