// Shared behaviors for the course app: switches, segmented controls and chip groups.
// Screens add their own logic on top; these only flip the ARIA state the CSS reads.
document.addEventListener('click', (e) => {
  const sw = e.target.closest('.switch[role="switch"]');
  if (sw && !sw.disabled) {
    const on = sw.getAttribute('aria-checked') !== 'true';
    sw.setAttribute('aria-checked', String(on));
    const label = sw.dataset.on && document.getElementById(sw.dataset.label || '');
    if (label) label.textContent = on ? sw.dataset.on : sw.dataset.off;
    sw.dispatchEvent(new CustomEvent('switch:change', { bubbles: true, detail: { on } }));
    return;
  }
  const seg = e.target.closest('.seg button, [data-chips] .chip');
  if (seg && seg.tagName === 'BUTTON') {
    const group = seg.parentElement;
    group.querySelectorAll('button').forEach((b) => {
      b.setAttribute('aria-pressed', String(b === seg));
      b.classList.toggle('on', b === seg && b.classList.contains('chip'));
    });
    group.dispatchEvent(new CustomEvent('choice:change', { bubbles: true, detail: { value: seg.value || seg.textContent.trim() } }));
  }
});
