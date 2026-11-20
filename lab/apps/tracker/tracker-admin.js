// Dashboard behavior: chart tooltips, sortable/filterable pages table, "exclude this browser".
(function () {
  'use strict';

  // ---- chart tooltip ----
  document.querySelectorAll('.pt-chart').forEach(function (chart) {
    var tip = chart.querySelector('.pt-tip');
    var svg = chart.querySelector('svg');
    function show(bar) {
      chart.querySelectorAll('.pt-bar.is-active').forEach(function (b) { b.classList.remove('is-active'); });
      bar.classList.add('is-active');
      tip.textContent = bar.getAttribute('data-tip');
      tip.hidden = false;
      var box = bar.querySelector('.pt-hit').getBoundingClientRect();
      var outer = chart.getBoundingClientRect();
      var x = box.left + box.width / 2 - outer.left;
      var half = tip.offsetWidth / 2;
      tip.style.left = Math.max(half, Math.min(outer.width - half, x)) + 'px';
      // Sit just above the bar so the tooltip never covers the card heading.
      var mark = bar.querySelector('path') || bar.querySelector('.pt-hit');
      var markTop = mark.getBoundingClientRect().top - outer.top;
      tip.style.top = Math.max(0, markTop - tip.offsetHeight - 8) + 'px';
    }
    function hide() {
      tip.hidden = true;
      chart.querySelectorAll('.pt-bar.is-active').forEach(function (b) { b.classList.remove('is-active'); });
    }
    svg.addEventListener('pointermove', function (e) {
      var bar = e.target.closest && e.target.closest('.pt-bar');
      if (bar) show(bar);
    });
    svg.addEventListener('pointerleave', hide);
  });

  // ---- sortable table ----
  document.querySelectorAll('.pt-sortable').forEach(function (table) {
    var heads = table.querySelectorAll('th[data-sort]');
    heads.forEach(function (th, col) {
      th.tabIndex = 0;
      function sort() {
        var desc = th.getAttribute('aria-sort') !== 'descending';
        if (th.dataset.sort === 'text' && !th.hasAttribute('aria-sort')) desc = false;
        heads.forEach(function (h) { h.removeAttribute('aria-sort'); });
        th.setAttribute('aria-sort', desc ? 'descending' : 'ascending');
        var body = table.tBodies[0];
        var rows = Array.prototype.slice.call(body.rows);
        rows.sort(function (a, b) {
          var x = a.cells[col].dataset.v, y = b.cells[col].dataset.v;
          var r = th.dataset.sort === 'num' ? (+x) - (+y) : x.localeCompare(y);
          return desc ? -r : r;
        });
        rows.forEach(function (r) { body.appendChild(r); });
      }
      th.addEventListener('click', sort);
      th.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sort(); } });
    });
  });

  // ---- filter box ----
  document.querySelectorAll('[data-filter]').forEach(function (input) {
    var table = document.querySelector(input.dataset.filter);
    if (!table) { input.hidden = true; return; }
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      Array.prototype.forEach.call(table.tBodies[0].rows, function (r) {
        r.hidden = q !== '' && r.cells[0].textContent.toLowerCase().indexOf(q) === -1;
      });
    });
  });

  // ---- exclude this browser ----
  var state = document.getElementById('ignore-state');
  var toggle = document.getElementById('ignore-toggle');
  if (state && toggle) {
    var shared = /(^|\.)emilydiazslp\.com$/.test(location.hostname) ? '; domain=.emilydiazslp.com' : '';
    var secure = location.protocol === 'https:' ? '; secure' : '';
    var isIgnored = function () { return /(?:^|; )pt_ignore=1/.test(document.cookie); };
    var render = function () {
      var on = isIgnored();
      state.textContent = on ? 'excluded. Your visits aren’t counted.' : 'counted, like any visitor.';
      toggle.textContent = on ? 'count this browser again' : 'exclude this browser';
    };
    toggle.addEventListener('click', function () {
      var age = isIgnored() ? 0 : 400 * 86400;
      document.cookie = 'pt_ignore=1; path=/; max-age=' + age + shared + '; samesite=lax' + secure;
      render();
    });
    render();
  }
})();
