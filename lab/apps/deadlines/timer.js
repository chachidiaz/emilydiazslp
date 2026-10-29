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
 * The page receives a "deadline:loaded" event (detail: the API response) once timers load.
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

  // Google Fonts offered in the dashboard, with the weights each one has.
  // Any other Google Fonts name also works; it loads in its regular weight.
  var FONTS = {
    'Poppins': '400;600;700', 'Montserrat': '400;600;700', 'Lato': '400;700', 'Open Sans': '400;600;700',
    'Raleway': '400;600;700', 'Nunito': '400;600;700', 'Quicksand': '400;600;700', 'DM Sans': '400;600;700',
    'Work Sans': '400;600;700', 'Josefin Sans': '400;600;700', 'Inter': '400;600;700', 'Space Grotesk': '400;600;700',
    'Playfair Display': '400;600;700', 'Lora': '400;600;700', 'Libre Baskerville': '400;700', 'Oswald': '400;600;700',
    'Bebas Neue': '400', 'Archivo Black': '400', 'Abril Fatface': '400', 'Roboto Mono': '400;600;700',
    'Pacifico': '400', 'Dancing Script': '400;600;700'
  };
  var BRAND_FONT = 'Oranges And Lemons Script';
  var SANS = '"Helvetica Neue",Arial,sans-serif';

  var CSS = [
    '[data-deadline-show]{display:none!important}',
    '[data-deadline-show].dlt-on{display:revert!important}',
    '.dlt{font-family:var(--dlt-font);box-sizing:border-box;line-height:1.3}',
    '.dlt *{box-sizing:border-box}',
    '.dlt-bar{position:fixed;left:0;right:0;z-index:2147483000;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px 20px;padding:var(--dlt-pad-y) 16px;box-shadow:0 2px 8px rgba(74,39,57,.12)}',
    '.dlt-bar.dlt-top{top:0}.dlt-bar.dlt-bottom{bottom:0}',
    '.dlt-bar.dlt-static{position:relative}',
    '.dlt-head{font-size:var(--dlt-head);font-weight:700}',
    '.dlt-bar .dlt-head{font-weight:600}',
    '.dlt-inline{padding:var(--dlt-pad-y) 24px;border-radius:20px;box-shadow:0 2px 8px rgba(74,39,57,.07);display:flex;flex-direction:column;gap:16px}',
    '.dlt-inline.dlt-center{align-items:center;text-align:center}.dlt-inline.dlt-left{align-items:flex-start;text-align:left}',
    '.dlt-digits{display:flex;gap:calc(var(--dlt-num) * .3);flex-wrap:wrap}',
    '.dlt-center .dlt-digits{justify-content:center}',
    '.dlt-unit{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:calc(var(--dlt-num) * 2.1);padding:var(--dlt-box-pad) calc(var(--dlt-num) * .25);border-radius:12px}',
    '.dlt-bar .dlt-unit{border-radius:8px}',
    '.dlt-num{font-family:var(--dlt-num-font);font-weight:700;font-size:var(--dlt-num);line-height:1.05;font-variant-numeric:tabular-nums}',
    '.dlt-label{font-size:var(--dlt-label);letter-spacing:.12em;text-transform:uppercase;font-weight:600;opacity:.85;margin-top:2px}',
    '.dlt-btn{display:inline-block;border-radius:999px;padding:.85em 1.9em;font-size:max(14px,calc(var(--dlt-head) * .85));font-weight:700;letter-spacing:.04em;text-transform:lowercase;text-decoration:none;transition:filter .2s cubic-bezier(.22,.61,.36,1)}',
    '.dlt-bar .dlt-btn{padding:.55em 1.4em}',
    '.dlt-btn:hover{filter:brightness(.9)}',
    '.dlt-expired{font-weight:600;font-size:var(--dlt-head)}',
    '@media (max-width:520px){',
    // Shrink to fit four boxes across a phone screen, however big they are on desktop.
    '.dlt-num{font-size:min(calc(var(--dlt-num) * .72),10vw)}',
    '.dlt-unit{min-width:min(calc(var(--dlt-num) * 1.55),18vw);padding:calc(var(--dlt-box-pad) * .7) 4px}',
    '.dlt-label{font-size:min(var(--dlt-label),2.8vw);letter-spacing:.06em}',
    '.dlt-head{font-size:calc(var(--dlt-head) * .88)}',
    '.dlt-digits{gap:min(calc(var(--dlt-num) * .2),2vw)}',
    '.dlt-inline{padding:calc(var(--dlt-pad-y) * .75) 16px}',
    '}'
  ].join('');

  function fontStack(name) {
    if (!name) return 'inherit';
    return '"' + name + '",' + (name === BRAND_FONT ? 'cursive' : SANS);
  }

  // Adds the stylesheet for a Google Font (or the brand script) once per page.
  function loadFont(name) {
    if (!name) return;
    var id = 'dlt-font-' + name.replace(/\W+/g, '-').toLowerCase();
    if (document.getElementById(id)) return;
    var head = document.head || document.documentElement;
    if (name === BRAND_FONT) {
      var st = document.createElement('style');
      st.id = id;
      st.textContent = '@font-face{font-family:"' + BRAND_FONT + '";src:url("/brand/assets/fonts/OrangesAndLemonsScript.otf") format("opentype");font-display:swap}';
      head.appendChild(st);
      return;
    }
    var link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    link.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(name).replace(/%20/g, '+') +
      (FONTS[name] ? ':wght@' + FONTS[name] : '') + '&display=swap';
    head.appendChild(link);
  }

  function cleanFont(name) {
    return String(name || '').replace(/[^A-Za-z0-9 ]+/g, '').trim().slice(0, 40);
  }

  function px(v, fallback) {
    var n = parseInt(v, 10);
    return (isNaN(n) ? fallback : n) + 'px';
  }

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
    var root = el('div', 'dlt ' + (type === 'bar' ? 'dlt-bar dlt-' + (s.position || 'top') : 'dlt-inline dlt-' + (s.align || 'center')));
    if (type === 'bar' && opts.static) root.className += ' dlt-static';
    root.style.background = s.bg;
    root.style.color = s.fg;
    var font = cleanFont(s.font), numFont = cleanFont(s.digit_font) || font;
    loadFont(font);
    loadFont(numFont);
    var bar = type === 'bar';
    root.style.setProperty('--dlt-font', fontStack(font));
    root.style.setProperty('--dlt-num-font', fontStack(numFont));
    root.style.setProperty('--dlt-head', px(s.head_size, bar ? 15 : 20));
    root.style.setProperty('--dlt-num', px(s.digit_size, bar ? 18 : 36));
    root.style.setProperty('--dlt-label', px(s.label_size, bar ? 10 : 12));
    root.style.setProperty('--dlt-pad-y', px(s.pad_y, bar ? 10 : 28));
    root.style.setProperty('--dlt-box-pad', px(s.box_pad, bar ? 4 : 12));
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
        document.dispatchEvent(new CustomEvent('deadline:loaded', { detail: data }));
      })
      .catch(function (err) {
        if (window.console) console.warn('Deadline timers could not load', err);
        document.dispatchEvent(new CustomEvent('deadline:error', { detail: err }));
      });
  }

  // Used by the dashboard's live preview.
  window.DeadlineTimers = {
    fonts: Object.keys(FONTS).concat([BRAND_FONT]),
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
