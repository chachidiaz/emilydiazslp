// Lab admin: filters, selection, row menus, dialogs, toasts, shortcuts and the command palette.
(function () {
  'use strict';

  var PAGE_SIZE = 25;
  var REPO = 'https://github.com/chachidiaz/emilydiazslp/blob/main/';
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  // ---------- toast ----------
  var toast = $('#toast'), toastTimer;
  function hideToast() { toast.hidden = true; }
  function armToast() {
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, toast.querySelector('form') ? 8000 : 4000);
  }
  function showToast(text) {
    var form = toast.querySelector('form');
    if (form) form.remove();
    toast.querySelector('.toast-text').textContent = text;
    toast.hidden = false;
    armToast();
  }
  toast.querySelector('.toast-close').addEventListener('click', hideToast);
  toast.addEventListener('mouseenter', function () { clearTimeout(toastTimer); });
  toast.addEventListener('mouseleave', armToast);
  if (!toast.hidden) armToast();

  function copy(text, done) {
    navigator.clipboard.writeText(text).then(function () { showToast(done); }, function () {
      window.prompt('Copy this:', text);
    });
  }

  $$('[data-copy-url]').forEach(function (b) {
    b.addEventListener('click', function () { copy(b.getAttribute('data-copy-url'), 'Link copied'); });
  });

  // ---------- dialogs ----------
  function openDialog(id) {
    $$('dialog[open]').forEach(function (d) { d.close(); }); // never stack modals
    var d = document.getElementById(id);
    d.showModal();
    var first = d.querySelector('input:not([type=hidden]), textarea, select');
    if (first) { first.focus(); if (first.select) first.select(); }
    return d;
  }
  $$('[data-open]').forEach(function (b) {
    b.addEventListener('click', function () { openDialog(b.getAttribute('data-open')); });
  });
  $$('dialog').forEach(function (d) {
    d.addEventListener('click', function (e) { if (e.target === d) d.close(); }); // backdrop
    $$('[data-close]', d).forEach(function (b) { b.addEventListener('click', function () { d.close(); }); });
  });
  function setUrls(dialog, urls) {
    var box = dialog.querySelector('[data-urls]');
    box.innerHTML = '';
    urls.forEach(function (u) {
      var i = document.createElement('input');
      i.type = 'hidden'; i.name = 'urls[]'; i.value = u;
      box.appendChild(i);
    });
  }
  function rowInfo(tr) {
    return {
      tr: tr, url: tr.getAttribute('data-url'), id: tr.getAttribute('data-id'), name: tr.getAttribute('data-name'),
      title: tr.getAttribute('data-title'), file: tr.getAttribute('data-file'), type: tr.getAttribute('data-type'),
      folder: tr.getAttribute('data-folder'), landing: !!tr.closest('#landing-pages')
    };
  }
  function label(r) { return r.id ? '"' + r.name + '" (' + r.id + ')' : '"' + r.name + '"'; }

  // Edit with Claude: copies the page IDs and the request, to paste into a new session.
  var claudeRows = [], claudePreset = '';
  function openClaude(rows, preset) {
    claudeRows = rows; claudePreset = preset || '';
    var d = $('#dlg-claude');
    $('[data-claude-sub]', d).textContent = rows.length === 1 ? label(rows[0]) : rows.length + ' pages: ' + rows.map(function (r) { return r.id || r.name; }).join(', ');
    $('[data-claude-ask]', d).value = claudePreset;
    openDialog('dlg-claude');
  }
  $('[data-claude-copy]').addEventListener('click', function () {
    var ask = $('[data-claude-ask]').value.trim();
    var pages = claudeRows.map(function (r) {
      return (r.id || '(no ID yet)') + ' ("' + r.name + '")\n  File: ' + r.file + '\n  Live: ' + location.origin + r.url;
    }).join('\n');
    var text = (claudeRows.length === 1 ? 'Edit lab page ' : 'Edit these lab pages:\n') + pages + '\n\n' +
      (ask ? 'What to change: ' + ask : "I'll tell you what to change.") + '\n\n' +
      'Follow CLAUDE.md. Pull the latest main first. Show me the change before publishing.';
    $('#dlg-claude').close();
    copy(text, 'Copied. Paste it into a new Claude session.');
  });

  function openDelete(rows) {
    var d = $('#dlg-delete');
    setUrls(d, rows.map(function (r) { return r.url; }));
    $('[data-delete-title]', d).textContent = rows.length === 1 ? 'Delete ' + label(rows[0]) + '?' : 'Delete ' + rows.length + ' pages?';
    var list = $('[data-delete-list]', d);
    list.innerHTML = '';
    list.hidden = rows.length === 1;
    rows.forEach(function (r) { var li = document.createElement('li'); li.textContent = r.name + (r.id ? ' (' + r.id + ')' : ''); list.appendChild(li); });
    openDialog('dlg-delete');
    d.querySelector('.btn-danger').focus();
  }
  function openMove(rows) {
    var d = $('#dlg-move');
    setUrls(d, rows.map(function (r) { return r.url; }));
    $('[data-move-sub]', d).textContent = rows.length === 1 ? label(rows[0]) : rows.length + ' pages';
    var sel = $('[data-move-folder]', d);
    sel.value = rows.length === 1 ? rows[0].folder : '0';
    $('[data-move-new]', d).hidden = true;
    openDialog('dlg-move');
    sel.focus();
  }
  $('[data-move-folder]').addEventListener('change', function (e) {
    var newBox = $('[data-move-new]');
    newBox.hidden = e.target.value !== 'new';
    if (!newBox.hidden) newBox.querySelector('input').focus();
  });
  $('#dlg-move form').addEventListener('submit', function (e) {
    var sel = $('[data-move-folder]'), name = $('[name=new_folder]', this);
    if (sel.value === 'new' && !name.value.trim()) { e.preventDefault(); name.focus(); }
    if (sel.value !== 'new') name.value = '';
  });
  function openRename(r) {
    var d = $('#dlg-rename');
    $('[name=url]', d).value = r.url;
    $('[name=name]', d).value = r.name;
    $('[data-rename-title]', d).textContent = r.title !== r.name ? ' ("' + r.title + '")' : '';
    openDialog('dlg-rename');
  }
  $$('[data-purge]').forEach(function (b) {
    b.addEventListener('click', function () {
      var d = $('#dlg-purge');
      $('[name=id]', d).value = b.getAttribute('data-purge');
      $('[data-purge-title]', d).textContent = 'Delete "' + b.getAttribute('data-name') + '" for good?';
      openDialog('dlg-purge');
      d.querySelector('.btn-danger').focus();
    });
  });
  // Folder delete inside the folders dialog: a second click confirms (no stacked modal).
  $$('[data-confirm-folder]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      if (b.getAttribute('data-armed')) return;
      e.preventDefault();
      b.setAttribute('data-armed', '1');
      b.textContent = 'Confirm delete';
      b.classList.add('btn-danger');
      setTimeout(function () { b.removeAttribute('data-armed'); b.textContent = 'Delete'; b.classList.remove('btn-danger'); }, 4000);
    });
  });

  // ---------- menus ----------
  var openMenu = null, menuOwner = null;
  function closeMenu(focusBack) {
    if (!openMenu) return;
    openMenu.hidden = true;
    if (menuOwner) { menuOwner.setAttribute('aria-expanded', 'false'); if (focusBack) menuOwner.focus(); }
    openMenu = menuOwner = null;
  }
  function showMenu(menu, owner, position) {
    closeMenu();
    menu.hidden = false;
    if (position) {
      var r = owner.getBoundingClientRect(), w = menu.offsetWidth, h = menu.offsetHeight;
      var top = r.bottom + 4, left = r.right - w;
      if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 4);
      menu.style.top = top + 'px';
      menu.style.left = Math.max(8, left) + 'px';
    }
    owner.setAttribute('aria-expanded', 'true');
    openMenu = menu; menuOwner = owner;
    var items = menuItems(menu);
    if (items[0]) items[0].focus();
  }
  function menuItems(menu) {
    return $$('[role^="menuitem"]', menu).filter(function (i) { return !i.closest('[hidden]') && i.offsetParent !== null; });
  }
  document.addEventListener('click', function (e) {
    if (openMenu && !openMenu.contains(e.target) && !menuOwner.contains(e.target)) closeMenu();
  });
  document.addEventListener('keydown', function (e) {
    if (!openMenu) return;
    var items = menuItems(openMenu), i = items.indexOf(document.activeElement);
    if (e.key === 'Escape') { e.preventDefault(); closeMenu(true); }
    else if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
    else if (e.key === 'Tab') closeMenu();
  });
  window.addEventListener('scroll', function () { if (openMenu && openMenu.id === 'row-menu') closeMenu(); }, true);

  var account = $('[data-account]');
  account.addEventListener('click', function () {
    var m = $('.account-menu');
    m.hidden ? showMenu(m, account, false) : closeMenu();
  });

  var rowMenu = $('#row-menu'), menuRow = null;
  $$('[data-more]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.stopPropagation();
      if (openMenu === rowMenu && menuOwner === b) { closeMenu(); return; }
      menuRow = rowInfo(b.closest('tr'));
      $$('[data-landing-only]', rowMenu).forEach(function (n) { n.hidden = !menuRow.landing; });
      $$('[data-act=type]', rowMenu).forEach(function (n) { n.setAttribute('aria-checked', n.getAttribute('data-type') === menuRow.type ? 'true' : 'false'); });
      $('[data-act=copy-id]', rowMenu).hidden = !menuRow.id;
      $('[data-act=source]', rowMenu).href = REPO + menuRow.file;
      showMenu(rowMenu, b, true);
    });
  });
  rowMenu.addEventListener('click', function (e) {
    var item = e.target.closest('[data-act]');
    if (!item || !menuRow) return;
    var act = item.getAttribute('data-act'), r = menuRow;
    if (act === 'source') { closeMenu(); return; }
    e.preventDefault();
    closeMenu();
    if (act === 'claude') openClaude([r]);
    else if (act === 'copy-id') copy(r.id, 'Copied ' + r.id);
    else if (act === 'rename') openRename(r);
    else if (act === 'move') openMove([r]);
    else if (act === 'delete') openDelete([r]);
    else if (act === 'type') {
      var f = $('#form-type');
      f.elements.url.value = r.url;
      f.elements.type.value = item.getAttribute('data-type');
      f.submit();
    }
  });

  // ---------- tables: filters, pagination, selection ----------
  var tables = $$('[data-table]').map(function (sec) {
    var t = {
      sec: sec, rows: $$('tbody tr', sec), type: 'all', folder: 'all', q: '', page: 0,
      search: $('[data-filter-search]', sec), filters: $('.filters', sec), bulk: $('.bulk', sec),
      folderSel: $('[data-folder-filter]', sec), checkAll: $('[data-check-all]', sec)
    };
    t.folderLabels = t.folderSel ? $$('option', t.folderSel).map(function (o) { return o.textContent.replace(/ \(\d+\)$/, ''); }) : [];
    return t;
  });

  function matches(t, r, skip) {
    if (skip !== 'type' && t.type !== 'all' && r.getAttribute('data-type') !== t.type) return false;
    if (skip !== 'folder' && t.folder !== 'all' && r.getAttribute('data-folder') !== t.folder) return false;
    if (t.q && r.getAttribute('data-find').indexOf(t.q) < 0) return false;
    return true;
  }
  function render(t) {
    var shown = t.rows.filter(function (r) { return matches(t, r); });
    var pages = Math.max(1, Math.ceil(shown.length / PAGE_SIZE));
    t.page = Math.min(t.page, pages - 1);
    var start = t.page * PAGE_SIZE;
    t.rows.forEach(function (r) { r.hidden = true; });
    shown.slice(start, start + PAGE_SIZE).forEach(function (r) { r.hidden = false; });
    $('[data-showing]', t.sec).textContent = shown.length ? 'Showing ' + (start + 1) + '–' + Math.min(start + PAGE_SIZE, shown.length) + ' of ' + shown.length : 'Showing 0 of ' + t.rows.length;
    $('.dt-empty', t.sec).hidden = shown.length > 0;
    $('[data-prev]', t.sec).disabled = t.page === 0;
    $('[data-next]', t.sec).disabled = t.page >= pages - 1;
    // Live counts: each chip counts what it would show with the other filters applied.
    $$('[data-type-filter]', t.sec).forEach(function (c) {
      var want = c.getAttribute('data-type-filter');
      var n = t.rows.filter(function (r) { return matches(t, r, 'type') && (want === 'all' || r.getAttribute('data-type') === want); }).length;
      c.querySelector('.n').textContent = n;
    });
    if (t.folderSel) {
      $$('option', t.folderSel).forEach(function (o, i) {
        if (o.value === 'all') return;
        var n = t.rows.filter(function (r) { return matches(t, r, 'folder') && r.getAttribute('data-folder') === o.value; }).length;
        o.textContent = t.folderLabels[i] + ' (' + n + ')';
      });
    }
    syncSelection(t);
  }
  function selected(t) { return t.rows.filter(function (r) { return r.querySelector('.check').checked; }); }
  function syncSelection(t) {
    var sel = selected(t);
    t.rows.forEach(function (r) { r.classList.toggle('is-selected', r.querySelector('.check').checked); });
    t.bulk.hidden = !sel.length;
    if (t.filters) t.filters.hidden = !!sel.length;
    $('[data-bulk-count]', t.sec).textContent = sel.length + ' selected';
    var visible = t.rows.filter(function (r) { return !r.hidden; });
    var on = visible.filter(function (r) { return r.querySelector('.check').checked; }).length;
    t.checkAll.checked = visible.length > 0 && on === visible.length;
    t.checkAll.indeterminate = on > 0 && on < visible.length;
  }

  tables.forEach(function (t) {
    t.search.addEventListener('input', function () { t.q = t.search.value.trim().toLowerCase(); t.page = 0; render(t); });
    $$('[data-type-filter]', t.sec).forEach(function (c) {
      c.addEventListener('click', function () {
        t.type = c.getAttribute('data-type-filter');
        $$('[data-type-filter]', t.sec).forEach(function (x) { x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
        t.page = 0; render(t);
      });
    });
    if (t.folderSel) t.folderSel.addEventListener('change', function () { t.folder = t.folderSel.value; t.page = 0; render(t); });
    $('[data-prev]', t.sec).addEventListener('click', function () { t.page--; render(t); });
    $('[data-next]', t.sec).addEventListener('click', function () { t.page++; render(t); });
    t.rows.forEach(function (r) { r.querySelector('.check').addEventListener('change', function () { syncSelection(t); }); });
    t.checkAll.addEventListener('change', function () {
      t.rows.forEach(function (r) { if (!r.hidden) r.querySelector('.check').checked = t.checkAll.checked; });
      syncSelection(t);
    });
    $('[data-bulk-clear]', t.sec).addEventListener('click', function () {
      t.rows.forEach(function (r) { r.querySelector('.check').checked = false; });
      syncSelection(t);
    });
    $$('[data-bulk]', t.sec).forEach(function (b) {
      b.addEventListener('click', function () {
        var rows = selected(t).map(rowInfo), act = b.getAttribute('data-bulk');
        if (!rows.length) return;
        if (act === 'move') openMove(rows);
        else if (act === 'delete') openDelete(rows);
        else if (act === 'hide') openClaude(rows, 'Hide ' + (rows.length === 1 ? 'this page' : 'these pages') + ' from search engines (add <meta name="robots" content="noindex, nofollow">).');
      });
    });
    render(t);
  });

  // Top-bar search filters every table at once.
  var global = $('#global-search');
  global.addEventListener('input', function () {
    var q = global.value.trim().toLowerCase();
    tables.forEach(function (t) { t.search.value = global.value; t.q = q; t.page = 0; render(t); });
  });
  global.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    var first = $('[data-table] tbody tr:not([hidden])');
    if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); flashRow(first); }
  });

  function flashRow(tr) {
    tr.classList.remove('flash-row');
    void tr.offsetWidth;
    tr.classList.add('flash-row');
  }
  function revealRow(id) {
    var tr = document.getElementById(id);
    if (!tr) return;
    var t = tables.filter(function (x) { return x.sec.contains(tr); })[0];
    if (t && (tr.hidden || !matches(t, tr))) {
      t.type = 'all'; t.folder = 'all'; t.q = ''; t.search.value = ''; global.value = '';
      if (t.folderSel) t.folderSel.value = 'all';
      $$('[data-type-filter]', t.sec).forEach(function (x) { x.setAttribute('aria-pressed', x.getAttribute('data-type-filter') === 'all' ? 'true' : 'false'); });
      t.page = Math.floor(t.rows.indexOf(tr) / PAGE_SIZE);
      render(t);
    }
    tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    flashRow(tr);
    tr.querySelector('.page-name').focus({ preventScroll: true });
  }

  // ---------- command palette ----------
  var palette = $('#dlg-palette'), pInput = $('[data-palette-input]'), pList = $('[data-palette-list]'), pItems = [], pIndex = 0;
  function renderPalette() {
    var q = pInput.value.trim().toLowerCase();
    pItems = (window.LAB.palette || []).filter(function (it) {
      return !q || ((it.id || '') + ' ' + it.name).toLowerCase().indexOf(q) >= 0;
    }).slice(0, 12);
    pIndex = 0;
    pList.innerHTML = '';
    pItems.forEach(function (it, i) {
      var li = document.createElement('li');
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
      var id = document.createElement('span'); id.className = 'pid'; id.textContent = it.id || '';
      var name = document.createElement('span'); name.textContent = it.name;
      var kind = document.createElement('span'); kind.className = 'ptype'; kind.textContent = it.kind === 'page' ? 'page' : 'go to';
      li.appendChild(id); li.appendChild(name); li.appendChild(kind);
      li.addEventListener('mousemove', function () { selectPalette(i); });
      li.addEventListener('click', function () { choosePalette(i); });
      pList.appendChild(li);
    });
    if (!pItems.length) { var none = document.createElement('li'); none.textContent = 'Nothing matches.'; none.className = 'muted'; pList.appendChild(none); }
  }
  function selectPalette(i) {
    pIndex = i;
    $$('li', pList).forEach(function (li, j) { li.setAttribute('aria-selected', j === i ? 'true' : 'false'); });
  }
  function choosePalette(i) {
    var it = pItems[i];
    if (!it) return;
    palette.close();
    if (it.kind === 'page') revealRow(it.row);
    else if (it.url.charAt(0) === '#') {
      var target = document.querySelector(it.url);
      if (target) { target.scrollIntoView({ behavior: 'smooth' }); if (target.focus) target.focus({ preventScroll: true }); }
    } else location.href = it.url;
  }
  function openPalette() {
    openDialog('dlg-palette');
    pInput.value = '';
    renderPalette();
    pInput.focus();
  }
  pInput.addEventListener('input', renderPalette);
  pInput.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); selectPalette(Math.min(pIndex + 1, pItems.length - 1)); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); selectPalette(Math.max(pIndex - 1, 0)); }
    else if (e.key === 'Enter') { e.preventDefault(); choosePalette(pIndex); }
  });

  // ---------- shortcuts ----------
  var isMac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
  $$('[data-mod-key]').forEach(function (k) { k.textContent = isMac ? '⌘K' : 'Ctrl K'; });
  document.addEventListener('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openPalette(); return; }
    var tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
    if (e.metaKey || e.ctrlKey || e.altKey || $('dialog[open]')) return;
    if (e.key === '/') { e.preventDefault(); global.focus(); }
    else if (e.key.toLowerCase() === 'n' && $('#dlg-new-folder')) { e.preventDefault(); openDialog('dlg-new-folder'); }
  });

  // Highlight the section in the rail as you scroll past it.
  var navLinks = $$('.side .nav a[href^="#"]');
  if ('IntersectionObserver' in window && navLinks.length) {
    var dash = $('.side .nav a[href="/admin/"]');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var link = $('.side .nav a[href="#' + en.target.id + '"]');
        [dash].concat(navLinks).forEach(function (a) { a.removeAttribute('aria-current'); });
        (link || dash).setAttribute('aria-current', 'page');
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    ['attention', 'landing-pages', 'docs', 'artifacts'].forEach(function (id) { var el = document.getElementById(id); if (el) io.observe(el); });
  }
})();
