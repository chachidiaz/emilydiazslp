/*
 * Deadline timers embed. Add once per page:
 *
 *   <script src="/apps/deadlines/timer.js" defer></script>
 *
 * then place timers anywhere:
 *
 *   <div data-deadline-timer="TIMER_KEY"></div>
 *
 * and optionally show content only before or after a campaign's deadline:
 *
 *   <a data-deadline-campaign="SLUG" data-deadline-show="active" href="...">claim the discount</a>
 *   <p data-deadline-campaign="SLUG" data-deadline-show="expired">The discount has ended.</p>
 *
 * Links from emails should carry the subscriber's email (?email=...) so the same
 * deadline follows them across devices. The email is removed from the address bar
 * after the page loads.
 */
(function () {
  'use strict';

  var script = document.currentScript;
  var base = script ? script.src.replace(/[^/]*(\?.*)?$/, '') : '/apps/deadlines/';
  var EMAIL_PARAMS = ['email', 'em', 'e'];
  var UNITS = [['days', 86400], ['hours', 3600], ['minutes', 60], ['seconds', 1]];

  var CSS = [
    '[data-deadline-show]{display:none!important}',
    '[data-deadline-show].dlt-on{display:revert!important}',
    '.dlt{font-family:Poppins,"Helvetica Neue",Arial,sans-serif;box-sizing:border-box;line-height:1.3}',
    '.dlt *{box-sizing:border-box}',
    '.dlt-bar{position:fixed;left:0;right:0;z-index:2147483000;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px 20px;padding:10px 16px;box-shadow:0 2px 8px rgba(74,39,57,.12)}',
    '.dlt-bar.dlt-top{top:0}.dlt-bar.dlt-bottom{bottom:0}',
    '.dlt-bar.dlt-static{position:relative}',
    '.dlt-bar .dlt-head{font-weight:600;font-size:15px}',
    '.dlt-bar .dlt-digits{gap:6px}',
    '.dlt-bar .dlt-unit{min-width:46px;padding:4px 6px;border-radius:8px}',
    '.dlt-bar .dlt-num{font-size:18px}',
    '.dlt-bar .dlt-label{font-size:10px}',
    '.dlt-bar .dlt-btn{padding:8px 20px;font-size:14px}',
    '.dlt-inline{padding:28px 24px;border-radius:20px;box-shadow:0 2px 8px rgba(74,39,57,.07);display:flex;flex-direction:column;gap:16px}',
    '.dlt-inline.dlt-center{align-items:center;text-align:center}.dlt-inline.dlt-left{align-items:flex-start;text-align:left}',
    '.dlt-inline .dlt-head{font-weight:700;font-size:20px}',
    '.dlt-digits{display:flex;gap:12px;flex-wrap:wrap}',
    '.dlt-center .dlt-digits{justify-content:center}',
    '.dlt-unit{display:flex;flex-direction:column;align-items:center;min-width:76px;padding:12px 10px;border-radius:12px}',
    '.dlt-num{font-weight:700;font-size:36px;line-height:1.05;font-variant-numeric:tabular-nums}',
    '.dlt-label{font-size:12px;letter-spacing:.12em;text-transform:uppercase;font-weight:600;opacity:.85;margin-top:4px}',
    '.dlt-sm .dlt-unit{min-width:58px;padding:8px}.dlt-sm .dlt-num{font-size:24px}.dlt-sm .dlt-head{font-size:16px}',
    '.dlt-lg .dlt-unit{min-width:96px;padding:16px 12px}.dlt-lg .dlt-num{font-size:52px}.dlt-lg .dlt-head{font-size:26px}',
    '.dlt-btn{display:inline-block;border-radius:999px;padding:14px 30px;font-weight:700;letter-spacing:.04em;text-transform:lowercase;text-decoration:none;transition:filter .2s cubic-bezier(.22,.61,.36,1)}',
    '.dlt-btn:hover{filter:brightness(.9)}',
    '.dlt-expired{font-weight:600}',
    '@media (max-width:520px){.dlt-unit{min-width:60px;padding:8px 6px}.dlt-num{font-size:26px}.dlt-bar .dlt-unit{min-width:40px}.dlt-bar .dlt-num{font-size:16px}}'
  ].join('');

  function injectCss() {
    if (document.getElementById('dlt-css')) return;
    var s = document.createElement('style');
    s.id = 'dlt-css';
    s.textContent = CSS;
    (document.head || document.documentElement).appendChild(s);
  }
  injectCss();

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function parts(seconds, s) {
    var left = Math.max(0, seconds);
    var out = [];
    UNITS.forEach(function (u) {
      if (u[0] === 'days' && !s.show_days) return;
      if (u[0] === 'seconds' && !s.show_seconds) return;
      var n = Math.floor(left / u[1]);
      left -= n * u[1];
      out.push([u[0], n]);
    });
    return out;
  }

  function pad(n) { return n < 10 ? '0' + n : String(n); }

  function safeUrl(u) {
    return /^(https?:\/\/|\/|#)/i.test(u || '') ? u : '';
  }

  // Builds one timer. Returns an object with update(secondsLeft) and expire(message).
  function build(host, type, s, opts) {
    opts = opts || {};
    var root = el('div', 'dlt ' + (type === 'bar' ? 'dlt-bar dlt-' + (s.position || 'top') : 'dlt-inline dlt-' + (s.size || 'md') + ' dlt-' + (s.align || 'center')));
    if (type === 'bar' && opts.static) root.className += ' dlt-static';
    root.style.background = s.bg;
    root.style.color = s.fg;
    root.setAttribute('role', 'timer');

    if (s.headline) root.appendChild(el('div', 'dlt-head', s.headline));
    var digits = el('div', 'dlt-digits');
    root.appendChild(digits);

    var btn = null;
    if (s.button_text && (safeUrl(s.button_url) || opts.static)) {
      btn = el('a', 'dlt-btn', s.button_text);
      btn.href = safeUrl(s.button_url) || '#';
      btn.style.background = s.button_bg;
      btn.style.color = s.button_fg;
      root.appendChild(btn);
    }

    host.innerHTML = '';
    host.appendChild(root);

    var spacer = null;
    function fitSpacer() {
      if (!spacer) return;
      spacer.style.height = root.offsetHeight + 'px';
    }
    if (type === 'bar' && !opts.static) {
      spacer = el('div');
      spacer.setAttribute('aria-hidden', 'true');
      var body = document.body;
      if (s.position === 'bottom') body.appendChild(spacer); else body.insertBefore(spacer, body.firstChild);
      window.addEventListener('resize', fitSpacer);
    }

    var cells = null;
    return {
      update: function (secondsLeft) {
        var p = parts(secondsLeft, s);
        if (!cells || cells.length !== p.length) {
          digits.innerHTML = '';
          cells = p.map(function (x) {
            var u = el('div', 'dlt-unit');
            u.style.background = s.digit_bg;
            u.style.color = s.digit_fg;
            var num = el('div', 'dlt-num');
            u.appendChild(num);
            u.appendChild(el('div', 'dlt-label', x[0]));
            digits.appendChild(u);
            return num;
          });
          fitSpacer();
        }
        p.forEach(function (x, i) { cells[i].textContent = x[0] === 'days' ? String(x[1]) : pad(x[1]); });
      },
      expire: function (message) {
        if (message === null) {
          host.innerHTML = '';
          if (spacer) spacer.remove();
          return;
        }
        digits.innerHTML = '';
        cells = null;
        digits.appendChild(el('div', 'dlt-expired', message));
        var head = root.querySelector('.dlt-head');
        if (head) head.remove();
        if (btn) btn.remove();
        fitSpacer();
      }
    };
  }

  // Ticks a set of timers against a server deadline, correcting for the visitor's clock.
  function run(views, campaign, serverNow, onExpire) {
    var offset = serverNow * 1000 - Date.now();
    var done = false;
    function tick() {
      var left = Math.floor((campaign.deadline * 1000 - (Date.now() + offset)) / 1000);
      if (left <= 0) {
        if (!done) { done = true; onExpire(); }
        return;
      }
      views.forEach(function (v) { v.update(left); });
      setTimeout(tick, 1000 - ((Date.now() + offset) % 1000) + 5);
    }
    tick();
  }

  function expiredMessage(campaign, s) {
    return s.expired_text || campaign.expired_message || 'This offer has ended.';
  }

  function applyCampaign(campaign, now, entries) {
    var shows = document.querySelectorAll('[data-deadline-campaign="' + campaign.slug + '"][data-deadline-show]');
    function setShows(expired) {
      Array.prototype.forEach.call(shows, function (n) {
        var want = n.getAttribute('data-deadline-show') === 'expired';
        n.classList.toggle('dlt-on', want === expired);
      });
    }
    function expire() {
      setShows(true);
      if (campaign.expired_action === 'redirect' && safeUrl(campaign.redirect_url)) {
        var target = new URL(campaign.redirect_url, location.href);
        if (target.href.split('#')[0] !== location.href.split('#')[0]) {
          location.replace(target.href);
          return;
        }
      }
      entries.forEach(function (e) {
        e.view.expire(campaign.expired_action === 'hide' ? null : expiredMessage(campaign, e.settings));
      });
    }
    setShows(campaign.expired);
    if (campaign.expired) { expire(); return; }
    run(entries.map(function (e) { return e.view; }), campaign, now, expire);
  }

  function takeParams() {
    var url = new URL(location.href);
    var email = null;
    EMAIL_PARAMS.forEach(function (k) {
      if (!email && url.searchParams.get(k)) email = url.searchParams.get(k);
      url.searchParams.delete(k);
    });
    var reset = url.searchParams.get('dl_reset') === '1';
    url.searchParams.delete('dl_reset');
    if (url.href !== location.href && history.replaceState) history.replaceState(history.state, '', url.href);
    return { email: email, reset: reset, cleanUrl: url.href };
  }

  function load() {
    var hosts = document.querySelectorAll('[data-deadline-timer]');
    var shows = document.querySelectorAll('[data-deadline-campaign]');
    if (!hosts.length && !shows.length) return;

    var keys = [], slugs = [];
    Array.prototype.forEach.call(hosts, function (h) {
      var k = h.getAttribute('data-deadline-timer');
      if (k && keys.indexOf(k) < 0) keys.push(k);
    });
    Array.prototype.forEach.call(shows, function (n) {
      var c = n.getAttribute('data-deadline-campaign');
      if (c && slugs.indexOf(c) < 0) slugs.push(c);
    });

    var p = takeParams();
    var q = new URLSearchParams({ t: keys.join(','), c: slugs.join(','), u: p.cleanUrl });
    if (p.email) q.set('email', p.email);
    if (p.reset) q.set('reset', '1');

    fetch(base + 'api.php?' + q.toString(), { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var byCampaign = {};
        Object.keys(data.campaigns).forEach(function (slug) { byCampaign[slug] = []; });
        Array.prototype.forEach.call(hosts, function (h) {
          var t = data.timers[h.getAttribute('data-deadline-timer')];
          if (!t) return;
          byCampaign[t.campaign].push({ view: build(h, t.type, t.settings), settings: t.settings });
        });
        Object.keys(data.campaigns).forEach(function (slug) {
          applyCampaign(data.campaigns[slug], data.now, byCampaign[slug]);
        });
      })
      .catch(function (err) { if (window.console) console.warn('Deadline timers could not load', err); });
  }

  // Used by the dashboard's live preview.
  window.DeadlineTimers = {
    preview: function (host, type, settings, secondsLeft, expiredMsg) {
      var v = build(host, type, settings, { static: true });
      if (secondsLeft > 0) v.update(secondsLeft); else v.expire(expiredMsg);
      return v;
    }
  };

  if (!script || script.getAttribute('data-preview') === null) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', load);
    else load();
  }
})();
