/* Purchase path tracker pixel. Add to every page:
   <script defer src="https://lab.emilydiazslp.com/apps/tracker/p.js"></script>
   Manual goals: tracker.goal('watched_replay') */
(function () {
  'use strict';
  var script = document.currentScript;
  if (!script || window.__ptLoaded) return;
  window.__ptLoaded = true;

  var endpoint = script.src.replace(/p\.js(\?.*)?$/, 'collect.php');
  var queued = Array.isArray(window.tracker) ? window.tracker : [];
  var noop = { goal: function () {} };

  // Skip robots and browsers you've excluded from the dashboard's Setup tab.
  if (navigator.webdriver || /(?:^|; )pt_ignore=1/.test(document.cookie)) {
    window.tracker = noop;
    return;
  }

  // One cookie for emilydiazslp.com, lab. and campus. so a visitor keeps the same ID across all three.
  var host = location.hostname;
  var shared = /(^|\.)emilydiazslp\.com$/.test(host) ? '; domain=.emilydiazslp.com' : '';
  var secure = location.protocol === 'https:' ? '; secure' : '';

  function getCookie(name) {
    var m = document.cookie.match('(?:^|; )' + name + '=([^;]*)');
    return m ? decodeURIComponent(m[1]) : null;
  }
  function setCookie(name, value, seconds) {
    document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + seconds + shared + '; samesite=lax' + secure;
  }
  function store(key, value) {
    try { if (value === undefined) return localStorage.getItem(key); localStorage.setItem(key, value); } catch (e) { return null; }
  }
  function randomId() {
    var a = new Uint8Array(12);
    (window.crypto || window.msCrypto).getRandomValues(a);
    return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
  }
  var valid = /^[A-Za-z0-9_-]{8,40}$/;

  // Visitor ID: cookie first, localStorage as a backup (some browsers shorten script-set cookies).
  var vid = getCookie('pt_vid') || store('pt_vid');
  if (!vid || !valid.test(vid)) vid = randomId();
  setCookie('pt_vid', vid, 400 * 86400);
  store('pt_vid', vid);

  // Session ID: a new one after 30 minutes without activity.
  var sid = getCookie('pt_sid');
  if (!sid || !valid.test(sid)) sid = randomId();
  function touchSession() { setCookie('pt_sid', sid, 1800); }
  touchSession();

  var pvId = randomId();
  var params = new URLSearchParams(location.search);
  var utm = {};
  ['source', 'medium', 'campaign', 'content', 'term'].forEach(function (k) {
    var v = params.get('utm_' + k);
    if (v) utm[k] = v;
  });

  function send(data) {
    data.v = vid;
    data.s = sid;
    data.u = location.href;
    touchSession();
    var body = JSON.stringify(data);
    // text/plain keeps this a "simple" request, so other subdomains can send without CORS preflight.
    if (navigator.sendBeacon && navigator.sendBeacon(endpoint, new Blob([body], { type: 'text/plain' }))) return;
    try { fetch(endpoint, { method: 'POST', body: body, keepalive: true, mode: 'no-cors', headers: { 'Content-Type': 'text/plain' } }); } catch (e) {}
  }

  // ---- pageview ----
  send({
    t: 'pageview', p: pvId, ti: document.title, r: document.referrer, utm: utm,
    w: window.innerWidth, sc: screen.width + 'x' + screen.height
  });

  // ---- time on page (visible time only) and scroll depth ----
  var visibleMs = 0;
  var visibleSince = document.visibilityState === 'visible' ? Date.now() : null;
  var maxScroll = 0;
  function measureScroll() {
    var doc = document.documentElement;
    var total = Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0);
    var seen = (window.scrollY || doc.scrollTop) + window.innerHeight;
    var pct = total > 0 ? Math.min(100, Math.round(seen / total * 100)) : 100;
    if (pct > maxScroll) maxScroll = pct;
  }
  measureScroll();
  window.addEventListener('scroll', measureScroll, { passive: true });

  function flush() {
    if (visibleSince !== null) {
      visibleMs += Date.now() - visibleSince;
      visibleSince = null;
    }
    // Sends the running total each time; the server keeps the largest value.
    send({ t: 'leave', p: pvId, vs: Math.round(visibleMs / 1000), sd: maxScroll });
  }
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') flush();
    else if (visibleSince === null) visibleSince = Date.now();
  });
  window.addEventListener('pagehide', function () { if (visibleSince !== null) flush(); });

  // ---- goals ----
  function goal(name) {
    if (typeof name !== 'string' || !name) return;
    send({ t: 'goal', g: name, ti: document.title });
  }
  document.addEventListener('click', function (e) {
    var el = e.target && e.target.closest ? e.target.closest('[data-goal]') : null;
    if (el && el.tagName !== 'FORM') goal(el.getAttribute('data-goal'));
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (a) tagCheckoutLink(a);
  }, true);
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form && form.getAttribute && form.hasAttribute('data-goal')) goal(form.getAttribute('data-goal'));
  }, true);

  // ---- checkout links carry the visitor ID so the order can be joined to this path ----
  function tagCheckoutLink(a) {
    try {
      var url = new URL(a.href, location.href);
      if (url.hostname !== 'campus.emilydiazslp.com' || url.pathname.indexOf('/checkout') !== 0) return;
      if (url.searchParams.get('vid') === vid) return;
      url.searchParams.set('vid', vid);
      a.href = url.toString();
    } catch (e) {}
  }
  function tagAll() {
    Array.prototype.forEach.call(document.querySelectorAll('a[href*="campus.emilydiazslp.com"]'), tagCheckoutLink);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', tagAll);
  else tagAll();

  window.tracker = { goal: goal, visitorId: function () { return vid; } };
  queued.forEach(function (args) { if (args && args[0] === 'goal') goal(args[1]); });
})();
