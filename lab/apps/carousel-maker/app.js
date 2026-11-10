(() => {
const W = 1080, H = 1350, MARGIN = 100;
const SRC = window.BRAND_ASSETS;
const PALETTE = [
  {name:"Berry", hex:"#991649"}, {name:"Orange", hex:"#ff4d03"}, {name:"Chartreuse", hex:"#d0cf53"},
  {name:"Blush", hex:"#f7b6b5"}, {name:"Sky", hex:"#cee9f6"}, {name:"Cream", hex:"#f8f4d2"},
  {name:"Charcoal", hex:"#262626"}, {name:"White", hex:"#ffffff"}
];
const TEXT_COLORS = [{name:"White", hex:"#ffffff"}, {name:"Charcoal", hex:"#262626"}, {name:"Cream", hex:"#f8f4d2"}, {name:"Blush", hex:"#f7b6b5"}];
const GRAPHICS = [
  {id:"scribble", label:"Scribble", kind:"under"},
  {id:"double", label:"Double line", kind:"under"},
  {id:"circle", label:"Circle", kind:"ring"},
  {id:"oval", label:"Oval loop", kind:"ring"}
];
const FONTS = {tommy: {label:"Tommy Soft", css:'700 SIZEpx "Tommy Soft"'}, bring: {label:"Bringbold", css:'SIZEpx "Bringbold"'}};
const SCRIPT_CSS = 'SIZEpx "Bringbold Oblique"';
const STORE_KEY = "sfslp-carousel-v1";

const $ = (id) => document.getElementById(id);
const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

/* ---------- State ---------- */
let uid = 1;
function newSlide(base) {
  return {
    id: uid++, text: "", highlights: [], hlColor: base?.hlColor || "#991649",
    emphasis: null, emColor: base?.emColor || "#d0cf53", emGraphic: base?.emGraphic || "scribble",
    emTint: false, emScript: false,
    font: base?.font || "tommy", textColor: base?.textColor || "#ffffff", size: base?.size || 80,
    vpos: base?.vpos || "middle", align: base?.align || "left",
    bg: "main", bgColor: base?.bgColor || "#991649",
    img: null, imgName: "", crop: {zoom: 1, fx: .5, fy: .5}
  };
}
function exampleSlides() {
  const a = newSlide();
  a.text = "The hardest thing\nabout raising two kids, as a millennial, and growing a business isn't what I thought it was going to be.";
  a.highlights = [0, 1, 2]; a.emphasis = 11; a.emColor = "#d0cf53"; a.emGraphic = "scribble";
  const b = newSlide(a);
  b.text = "It's not the lack of time.\nIt's the guilt of feeling like I'm never fully present for either.";
  b.highlights = [7, 8]; b.emphasis = 15; b.emGraphic = "circle"; b.emColor = "#ff4d03";
  return [a, b];
}
const state = {
  step: 1, current: 0, pickMode: "hl", view: 0, hiRes: false,
  main: {img: null, name: "", crop: {zoom: 1, fx: .5, fy: .5}},
  darkness: .30, showLogo: true, logoColor: "#ffffff", showHandle: true, handle: "@sixfigureslp",
  slides: exampleSlides()
};

/* ---------- Persistence (text + settings only; photos stay in this tab) ---------- */
const PERSIST_SLIDE_KEYS = ["text","highlights","hlColor","emphasis","emColor","emGraphic","emTint","emScript","font","textColor","size","vpos","align","bg","bgColor"];
function save() {
  try {
    localStorage.setItem(STORE_KEY, JSON.stringify({
      hiRes: state.hiRes, darkness: state.darkness, showLogo: state.showLogo, logoColor: state.logoColor, showHandle: state.showHandle, handle: state.handle,
      slides: state.slides.map(s => Object.fromEntries(PERSIST_SLIDE_KEYS.map(k => [k, s[k]])))
    }));
  } catch (e) {}
}
function restore() {
  try {
    const raw = localStorage.getItem(STORE_KEY); if (!raw) return;
    const d = JSON.parse(raw); if (!d || !Array.isArray(d.slides) || !d.slides.length) return;
    ["hiRes","darkness","showLogo","logoColor","showHandle","handle"].forEach(k => { if (d[k] !== undefined) state[k] = d[k]; });
    state.slides = d.slides.map(o => { const s = newSlide(); PERSIST_SLIDE_KEYS.forEach(k => { if (o[k] !== undefined) s[k] = o[k]; }); if (s.bg === "custom") s.bg = "main"; return s; });
  } catch (e) {}
}
let saveTimer; function queueSave() { clearTimeout(saveTimer); saveTimer = setTimeout(save, 400); }

/* ---------- Graphic masks: turn brand PNGs into white alpha masks, trimmed, then tint on demand ---------- */
const masks = {}; const tintCache = new Map();
function loadImg(src) { return new Promise((res, rej) => { const i = new Image(); i.onload = () => res(i); i.onerror = rej; i.src = src; }); }
function toMask(img) {
  const c = document.createElement("canvas"); c.width = img.naturalWidth; c.height = img.naturalHeight;
  const x = c.getContext("2d"); x.drawImage(img, 0, 0);
  const d = x.getImageData(0, 0, c.width, c.height), p = d.data;
  let minX = c.width, minY = c.height, maxX = -1, maxY = -1;
  for (let i = 0; i < p.length; i += 4) {
    const a = (255 - Math.min(p[i], p[i+1], p[i+2])) * p[i+3] / 255;
    p[i] = p[i+1] = p[i+2] = 255; p[i+3] = a;
    if (a > 24) { const px = (i / 4) % c.width, py = (i / 4 / c.width) | 0;
      if (px < minX) minX = px; if (px > maxX) maxX = px; if (py < minY) minY = py; if (py > maxY) maxY = py; }
  }
  x.putImageData(d, 0, 0);
  if (maxX < 0) return c;
  const t = document.createElement("canvas"); t.width = maxX - minX + 1; t.height = maxY - minY + 1;
  t.getContext("2d").drawImage(c, minX, minY, t.width, t.height, 0, 0, t.width, t.height);
  return t;
}
function tinted(name, color) {
  const key = name + color; if (tintCache.has(key)) return tintCache.get(key);
  const m = masks[name]; const c = document.createElement("canvas"); c.width = m.width; c.height = m.height;
  const x = c.getContext("2d"); x.drawImage(m, 0, 0); x.globalCompositeOperation = "source-in"; x.fillStyle = color; x.fillRect(0, 0, c.width, c.height);
  tintCache.set(key, c); return c;
}

/* ---------- Text helpers ---------- */
function tokens(text) { return text.split("\n").map(p => p.split(/\s+/).filter(Boolean)); }
function wordCount(text) { return tokens(text).reduce((n, p) => n + p.length, 0); }
function lum(hex) { const n = parseInt(hex.slice(1), 16); const r = (n >> 16) / 255, g = (n >> 8 & 255) / 255, b = (n & 255) / 255;
  const f = v => v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); return .2126 * f(r) + .7152 * f(g) + .0722 * f(b); }
function onColor(hex) { return lum(hex) > .4 ? "#262626" : "#ffffff"; }
function fontFor(slide, isEm) { const sz = slide.size; return (isEm && slide.emScript ? SCRIPT_CSS.replace("SIZE", Math.round(sz * 1.08)) : FONTS[slide.font].css.replace("SIZE", sz)); }
/* Letter spacing: Safari (iPhone) has no canvas letterSpacing, so there we space letters by hand
   to get the same tracking and line breaks as every other browser. */
const NATIVE_LS = (() => { try {
  const c = document.createElement("canvas").getContext("2d"); if (!("letterSpacing" in c)) return false;
  c.font = "20px sans-serif"; const a = c.measureText("abc").width; c.letterSpacing = "10px"; return c.measureText("abc").width > a + 5;
} catch (e) { return false; } })();
function setSpacing(ctx, slide, isEm) {
  const v = (slide.font === "tommy" && !(isEm && slide.emScript)) ? +(-0.03 * slide.size).toFixed(1) : 0;
  if (NATIVE_LS) ctx.letterSpacing = v + "px"; else ctx._ls = v;
}
function measureW(ctx, word) { const w = ctx.measureText(word).width; return NATIVE_LS ? w : w + (ctx._ls || 0) * [...word].length; }
function drawWord(ctx, word, x, y) {
  const ls = NATIVE_LS ? 0 : (ctx._ls || 0); if (!ls) return ctx.fillText(word, x, y);
  let pre = ""; [...word].forEach((c, i) => { ctx.fillText(c, x + ctx.measureText(pre).width + i * ls, y); pre += c; });
}

function layout(ctx, slide) {
  const s = slide.size, maxW = W - 2 * MARGIN; let idx = 0; const lines = [];
  ctx.font = fontFor(slide, false); setSpacing(ctx, slide, false);
  const space = measureW(ctx, " ") + (slide.font === "tommy" ? s * 0.05 : 0);
  for (const para of tokens(slide.text)) {
    if (!para.length) { lines.push({items: [], w: 0}); continue; }
    let line = {items: [], w: 0};
    for (const word of para) {
      const isEm = idx === slide.emphasis;
      ctx.font = fontFor(slide, isEm); setSpacing(ctx, slide, isEm);
      const w = measureW(ctx, word);
      let add = line.items.length ? space + w : w;
      if (line.items.length && line.w + add > maxW) { lines.push(line); line = {items: [], w: 0}; add = w; }
      line.items.push({word, idx, w, x: line.w + (line.items.length ? space : 0), isEm});
      line.w += add; idx++;
    }
    lines.push(line);
  }
  return lines;
}

function roundRect(ctx, x, y, w, h, r) {
  ctx.beginPath(); ctx.moveTo(x + r, y); ctx.lineTo(x + w - r, y); ctx.quadraticCurveTo(x + w, y, x + w, y + r);
  ctx.lineTo(x + w, y + h - r); ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h); ctx.lineTo(x + r, y + h);
  ctx.quadraticCurveTo(x, y + h, x, y + h - r); ctx.lineTo(x, y + r); ctx.quadraticCurveTo(x, y, x + r, y); ctx.closePath();
}

function drawCover(ctx, img, crop) {
  const sc = Math.max(W / img.naturalWidth, H / img.naturalHeight) * crop.zoom;
  const dw = img.naturalWidth * sc, dh = img.naturalHeight * sc;
  ctx.drawImage(img, (W - dw) * crop.fx, (H - dh) * crop.fy, dw, dh);
}

/* ---------- Render one slide at 1080×1350 (scaled for thumbnails) ---------- */
function render(canvas, slide, scale = 1) {
  const ctx = canvas.getContext("2d");
  ctx.setTransform(scale, 0, 0, scale, 0, 0);
  ctx.imageSmoothingEnabled = true; ctx.imageSmoothingQuality = "high";
  ctx.clearRect(0, 0, W, H);
  // Background
  const photo = slide.bg === "custom" && slide.img ? {img: slide.img, crop: slide.crop} : (slide.bg !== "solid" && state.main.img ? {img: state.main.img, crop: state.main.crop} : null);
  if (slide.bg === "solid") { ctx.fillStyle = slide.bgColor; ctx.fillRect(0, 0, W, H); }
  else if (photo) {
    drawCover(ctx, photo.img, photo.crop);
    ctx.fillStyle = `rgba(0,0,0,${state.darkness})`; ctx.fillRect(0, 0, W, H);
  } else {
    const g = ctx.createLinearGradient(0, 0, W, H); g.addColorStop(0, "#3a2a2f"); g.addColorStop(1, "#262626");
    ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);
    ctx.fillStyle = "rgba(255,255,255,.28)"; ctx.font = '600 30px "DM Sans", sans-serif'; ctx.textAlign = "center";
    if ("letterSpacing" in ctx) ctx.letterSpacing = "0px";
    ctx.fillText("Your photo goes here (step 1)", W / 2, 120); ctx.textAlign = "left";
  }

  // Text block
  const s = slide.size, lh = s * 1.13; const lines = layout(ctx, slide);
  const blockH = lines.length ? (lines.length - 1) * lh + s * 1.08 : 0;
  const logoReserve = (state.showLogo || state.showHandle) ? 220 : 120;
  let top = slide.vpos === "top" ? 150 : slide.vpos === "bottom" ? H - logoReserve - blockH : (H - blockH) / 2 - 30;
  top = Math.max(60, top);
  const base0 = top + s * 0.86;
  const lx = (line) => slide.align === "center" ? (W - line.w) / 2 : MARGIN;
  const hl = new Set(slide.highlights);

  // Highlight blocks (merge consecutive highlighted words on a line)
  ctx.fillStyle = slide.hlColor;
  lines.forEach((line, li) => {
    const b = base0 + li * lh, x0 = lx(line); let run = null;
    const flush = () => { if (!run) return; const pad = s * 0.14;
      roundRect(ctx, x0 + run.a - pad, b - s * 0.86, run.b - run.a + pad * 2, s * 1.1, s * 0.09); ctx.fill(); run = null; };
    for (const it of line.items) { if (hl.has(it.idx)) { if (run) run.b = it.x + it.w; else run = {a: it.x, b: it.x + it.w}; } else flush(); }
    flush();
  });

  // Words
  ctx.textBaseline = "alphabetic"; ctx.textAlign = "left";
  let emBox = null;
  lines.forEach((line, li) => {
    const b = base0 + li * lh, x0 = lx(line);
    for (const it of line.items) {
      ctx.font = fontFor(slide, it.isEm); setSpacing(ctx, slide, it.isEm);
      ctx.fillStyle = hl.has(it.idx) ? onColor(slide.hlColor) : (it.isEm && slide.emTint ? slide.emColor : slide.textColor);
      drawWord(ctx, it.word, x0 + it.x, b);
      if (it.isEm) emBox = {x: x0 + it.x, w: it.w, b};
    }
  });

  // Emphasis graphic
  if (emBox && masks[slide.emGraphic]) {
    const g = GRAPHICS.find(g => g.id === slide.emGraphic); const art = tinted(slide.emGraphic, slide.emColor);
    if (g.kind === "under") {
      const w = emBox.w * 1.12, h = Math.max(s * 0.14, w * art.height / art.width);
      ctx.drawImage(art, emBox.x - emBox.w * 0.06, emBox.b + s * 0.13, w, Math.min(h, s * 0.34));
    } else {
      const w = emBox.w + s * 0.95, h = s * 1.72;
      ctx.drawImage(art, emBox.x + emBox.w / 2 - w / 2, emBox.b - s * 0.36 - h / 2, w, h);
    }
  }

  // Logo + handle
  const footY = H - 92;
  if (state.showLogo && masks.logo) {
    const art = tinted("logo", state.logoColor), lw = 200, lhh = lw * art.height / art.width;
    ctx.drawImage(art, MARGIN - 6, footY - lhh / 2, lw, lhh);
  }
  if (state.showHandle && state.handle.trim()) {
    ctx.font = '700 34px "Tommy Soft"'; if ("letterSpacing" in ctx) ctx.letterSpacing = "0px";
    ctx.fillStyle = state.logoColor; ctx.textAlign = "right"; ctx.textBaseline = "middle";
    ctx.fillText(state.handle.trim(), W - 80, footY + 4);
    ctx.textAlign = "left"; ctx.textBaseline = "alphabetic";
  }
  ctx.setTransform(1, 0, 0, 1, 0, 0);
}

/* ---------- UI builders ---------- */
function seg(el, options, get, set) {
  el.innerHTML = "";
  options.forEach(o => { const b = document.createElement("button"); b.type = "button"; b.textContent = o.label; b.dataset.v = o.v;
    b.addEventListener("click", () => { set(o.v); }); el.appendChild(b); });
  return () => el.querySelectorAll("button").forEach(b => b.setAttribute("aria-pressed", String(b.dataset.v === String(get()))));
}
function swatches(el, colors, get, set) {
  el.innerHTML = "";
  colors.forEach(c => { const b = document.createElement("button"); b.type = "button"; b.className = "sw"; b.style.background = c.hex;
    b.title = c.name; b.setAttribute("aria-label", c.name); b.dataset.v = c.hex; b.addEventListener("click", () => set(c.hex)); el.appendChild(b); });
  return () => el.querySelectorAll("button").forEach(b => b.setAttribute("aria-pressed", String(b.dataset.v === get())));
}
const cur = () => state.slides[state.current];
const syncers = [];

function wireSlider(id, get, set, fmt) {
  const el = $(id), out = $(id + "-o");
  el.addEventListener("input", () => { set(+el.value); out.textContent = fmt(+el.value); changed(); });
  syncers.push(() => { el.value = get(); out.textContent = fmt(get()); });
}

/* ---------- Rendering schedule ---------- */
let raf = 0, thumbsAll = true;
function changed(all = false) { if (all) thumbsAll = true; queueSave(); if (!raf) raf = requestAnimationFrame(draw); }
function draw() {
  raf = 0;
  if (state.step === 1) render($("preview-1"), state.slides[0]);
  if (state.step === 2) { render($("preview-2"), cur()); drawThumbs(); }
}
function drawThumbs() {
  const strip = $("strip");
  if (thumbsAll || strip.querySelectorAll(".thumb").length !== state.slides.length) { buildStrip(); thumbsAll = false; return; }
  const c = strip.querySelectorAll(".thumb canvas")[state.current]; if (c) render(c, cur(), c.width / W);
}
function buildStrip() {
  const strip = $("strip"); strip.innerHTML = "";
  state.slides.forEach((s, i) => {
    const b = document.createElement("button"); b.type = "button"; b.className = "thumb"; b.setAttribute("aria-label", "Edit slide " + (i + 1));
    b.setAttribute("aria-current", String(i === state.current));
    const c = document.createElement("canvas"); c.width = 144; c.height = 180; render(c, s, 144 / W);
    const n = document.createElement("span"); n.className = "num"; n.textContent = i + 1;
    b.append(c, n); b.addEventListener("click", () => { state.current = i; syncSlide(); changed(true); });
    strip.appendChild(b);
  });
  const add = document.createElement("button"); add.type = "button"; add.className = "add-slide"; add.innerHTML = "<span>+</span>Add slide";
  add.addEventListener("click", () => {
    const s = newSlide(cur()); state.slides.splice(state.current + 1, 0, s); state.current++;
    syncSlide(); changed(true); $("slide-text").focus();
  });
  strip.appendChild(add);
}

/* ---------- Word picker ---------- */
function buildWords() {
  const s = cur(), box = $("words"); box.innerHTML = ""; let idx = 0; const paras = tokens(s.text); const hl = new Set(s.highlights);
  if (!wordCount(s.text)) { box.innerHTML = '<span class="empty">Your words show up here once you type the slide text.</span>'; return; }
  paras.forEach((p, pi) => {
    if (pi > 0) { const br = document.createElement("span"); br.className = "br"; box.appendChild(br); }
    p.forEach(word => {
      const i = idx++; const b = document.createElement("button"); b.type = "button"; b.className = "word"; b.textContent = word;
      if (hl.has(i)) { b.classList.add("hl"); b.style.background = s.hlColor; b.style.color = onColor(s.hlColor); }
      if (i === s.emphasis) { b.classList.add("em"); if (!hl.has(i)) b.style.color = s.emColor === "#ffffff" || s.emColor === "#f8f4d2" || s.emColor === "#cee9f6" ? "" : s.emColor; }
      b.setAttribute("aria-pressed", String(hl.has(i) || i === s.emphasis));
      b.addEventListener("click", () => {
        if (state.pickMode === "hl") { const set = new Set(s.highlights); set.has(i) ? set.delete(i) : set.add(i); s.highlights = [...set].sort((a, c) => a - c); }
        else s.emphasis = s.emphasis === i ? null : i;
        buildWords(); syncEm(); changed();
      });
      box.appendChild(b);
    });
  });
}
function syncEm() {
  const s = cur(); const words = tokens(s.text).flat();
  $("em-status").textContent = s.emphasis != null && words[s.emphasis] ? `Emphasized word: "${words[s.emphasis]}"` : 'No word emphasized yet. Switch to "Emphasize it" and tap one word.';
  $("clear-em").disabled = s.emphasis == null;
  buildGfx();
}
function buildGfx() {
  const s = cur(), box = $("gfx");
  if (!box.children.length) {
    GRAPHICS.forEach(g => { const b = document.createElement("button"); b.type = "button"; b.dataset.v = g.id;
      const c = document.createElement("canvas"); c.width = 200; c.height = 100; const l = document.createElement("span"); l.textContent = g.label;
      b.append(c, l); b.addEventListener("click", () => { cur().emGraphic = g.id; buildGfx(); changed(); }); box.appendChild(b); });
  }
  box.querySelectorAll("button").forEach(b => {
    b.setAttribute("aria-pressed", String(b.dataset.v === s.emGraphic));
    const c = b.querySelector("canvas"), x = c.getContext("2d"); x.clearRect(0, 0, 200, 100);
    if (!masks[b.dataset.v]) return;
    const art = tinted(b.dataset.v, s.emColor), g = GRAPHICS.find(g => g.id === b.dataset.v);
    x.font = '700 30px "Tommy Soft"'; x.fillStyle = "#ffffff"; x.textAlign = "center"; x.textBaseline = "alphabetic"; x.fillText("word", 100, 62);
    if (g.kind === "under") x.drawImage(art, 46, 68, 108, Math.min(22, 108 * art.height / art.width));
    else x.drawImage(art, 20, 14, 160, 72);
  });
}

/* ---------- Sync slide editor to the current slide ---------- */
let syncSlideParts = [];
function syncSlide() {
  const s = cur(), n = state.slides.length;
  $("slide-label").textContent = `Slide ${state.current + 1} text`;
  $("preview-2-cap").textContent = `Slide ${state.current + 1} of ${n}`;
  $("slide-text").value = s.text;
  $("move-left").disabled = state.current === 0; $("move-right").disabled = state.current === n - 1; $("del-slide").disabled = n === 1;
  $("em-tint").checked = s.emTint; $("em-script").checked = s.emScript;
  $("bg-custom").hidden = s.bg !== "custom"; $("bg-solid").hidden = s.bg !== "solid";
  $("slide-photo-info").hidden = !s.img;
  if (s.img) { $("slide-thumb").src = s.img.src; $("slide-photo-name").textContent = s.imgName; }
  syncSlideParts.forEach(f => f()); syncers.forEach(f => f());
  buildWords(); syncEm();
}

/* ---------- Photo loading ---------- */
function readPhoto(file, cb) {
  if (!file || !file.type.startsWith("image/")) return;
  const url = URL.createObjectURL(file); const img = new Image();
  img.onload = () => cb(img, file.name);
  img.onerror = () => { URL.revokeObjectURL(url); alertStatus("That photo could not be opened. Try a JPG, PNG or WebP file."); };
  img.src = url;
}
function alertStatus(msg) { const el = state.step === 1 ? $("photo-status") : $("em-status"); el.textContent = msg; }
function wireDrop(dropEl, input, onFile) {
  input.addEventListener("change", () => { onFile(input.files[0]); input.value = ""; });
  ["dragenter", "dragover"].forEach(ev => dropEl.addEventListener(ev, e => { e.preventDefault(); dropEl.classList.add("over"); }));
  ["dragleave", "drop"].forEach(ev => dropEl.addEventListener(ev, e => { e.preventDefault(); dropEl.classList.remove("over"); }));
  dropEl.addEventListener("drop", e => onFile(e.dataTransfer.files[0]));
}
function setMain(img, name) {
  $("photo-status").textContent = "";
  state.main.img = img; state.main.name = name; state.main.crop = {zoom: 1, fx: .5, fy: .5};
  $("main-photo-info").hidden = false; $("main-thumb").src = img.src; $("main-name").textContent = name;
  syncers.forEach(f => f()); changed(true);
}

/* ---------- Steps ---------- */
function goStep(n) {
  state.step = n;
  [1, 2, 3].forEach(i => { $("step-" + i).hidden = i !== n; });
  document.querySelectorAll(".step-btn").forEach(b => { const i = +b.dataset.step;
    if (i === n) b.setAttribute("aria-current", "step"); else b.removeAttribute("aria-current");
    b.classList.toggle("done", i < n); });
  if (n === 2) { syncSlide(); thumbsAll = true; }
  if (n === 3) buildFinal();
  window.scrollTo({top: 0, behavior: "smooth"});
  changed();
}

/* ---------- Step 3: final renders, viewer, downloads ---------- */
let finals = [];
function slideBlob(s) { const k = state.hiRes ? 2 : 1; return new Promise(res => { const c = document.createElement("canvas"); c.width = W * k; c.height = H * k; render(c, s, k); c.toBlob(res, "image/png"); }); }
let finalsReady = Promise.resolve();
function buildFinal() { finalsReady = finalsReady.then(buildFinalNow); return finalsReady; }
async function buildFinalNow() {
  const st = $("dl-status"); st.className = "status"; st.textContent = "Rendering slides…";
  finals.forEach(f => URL.revokeObjectURL(f.url));
  finals = await Promise.all(state.slides.map(async s => { const blob = await slideBlob(s); return {blob, url: URL.createObjectURL(blob)}; }));
  state.view = clamp(state.view, 0, finals.length - 1);
  const grid = $("all-slides"); grid.innerHTML = "";
  finals.forEach((f, i) => {
    const fig = document.createElement("figure"); const img = document.createElement("img"); img.src = f.url; img.alt = "Slide " + (i + 1);
    img.addEventListener("click", () => { state.view = i; showView(); });
    const cap = document.createElement("figcaption"); const t = document.createElement("span"); t.textContent = "Slide " + (i + 1);
    const b = document.createElement("button"); b.type = "button"; b.className = "btn small"; b.textContent = useShare ? "Save to Photos" : "Save PNG";
    b.addEventListener("click", () => saveFile(fileName(i), f.blob));
    cap.append(t, b); fig.append(img, cap); grid.appendChild(fig);
  });
  st.textContent = "";
  showView();
}
function showView() {
  const f = finals[state.view]; if (!f) return;
  $("view-img").src = f.url; $("view-img").alt = `Slide ${state.view + 1} of ${finals.length}`;
  $("view-prev").disabled = state.view === 0; $("view-next").disabled = state.view === finals.length - 1;
  $("view-prev").hidden = finals.length < 2; $("view-next").hidden = finals.length < 2;
  $("dots").innerHTML = finals.map((_, i) => `<span class="${i === state.view ? "on" : ""}"></span>`).join("");
}
const fileName = (i) => `six-figure-slp-slide-${String(i + 1).padStart(2, "0")}.png`;

/* Phones: the share sheet offers "Save to Photos" and Instagram, which a ZIP in Files can't. */
const useShare = (() => {
  try {
    if (!matchMedia("(pointer: coarse)").matches || !navigator.canShare) return false;
    return navigator.canShare({files: [new File([new Blob(["x"], {type: "image/png"})], "t.png", {type: "image/png"})]});
  } catch (e) { return false; }
})();
async function shareFiles(files) {
  const st = $("dl-status");
  try {
    await navigator.share({files});
    st.className = "status ok"; st.textContent = "Done. If you tapped Save Image" + (files.length > 1 ? "s" : "") + ", " + (files.length > 1 ? "they're" : "it's") + " in your camera roll.";
  } catch (e) {
    if (e?.name === "AbortError") { st.className = "status"; st.textContent = ""; return; }
    st.className = "status err"; st.textContent = "Your phone couldn't open the share sheet. Press and hold a slide image to save it instead.";
  }
}
const downloadsP = (async () => { try { return window.claude?.use ? await window.claude.use("downloads") : null; } catch (e) { return null; } })();
async function saveFile(name, blob) {
  const st = $("dl-status"); st.className = "status"; st.textContent = "Preparing " + name + "…";
  const dl = await downloadsP;
  if (dl) {
    try { await dl.save({filename: name, data: blob}); st.className = "status ok"; st.textContent = "Saved " + name + "."; }
    catch (e) {
      st.className = "status err";
      st.textContent = e?.code === "declined" ? "Download cancelled." : e?.code === "rate_limited" ? "A save prompt is already open. Finish it, then try again." : "Downloads aren't available here. Press and hold (or right-click) a slide image to save it.";
    }
    return;
  }
  // On phones, open the share sheet so slides can go straight to Photos or Instagram.
  if (useShare && blob.type === "image/png") { await shareFiles([new File([blob], name, {type: "image/png"})]); return; }
  // On a regular website (outside the Claude artifact viewer) a plain download link works.
  const a = document.createElement("a"); a.href = URL.createObjectURL(blob); a.download = name; document.body.appendChild(a); a.click();
  setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 4000);
  st.className = "status ok"; st.textContent = "Downloaded " + name + ". If nothing appeared, press and hold (or right-click) a slide image to save it.";
}

/* ---------- Wire everything ---------- */
function wire() {
  document.querySelectorAll(".step-btn").forEach(b => b.addEventListener("click", () => goStep(+b.dataset.step)));
  document.querySelectorAll("[data-go]").forEach(b => b.addEventListener("click", () => goStep(+b.dataset.go)));

  wireDrop($("drop-main"), $("main-photo"), f => readPhoto(f, (img, name) => {
    setMain(img, name);
    // On phones, bring the preview and crop sliders into view together.
    if (matchMedia("(max-width: 640px)").matches) requestAnimationFrame(() => {
      const pin = document.querySelector("#step-1 .preview-pin"), crop = document.querySelector("#step-1 .crop-g");
      const top = crop.getBoundingClientRect().top + scrollY - pin.offsetHeight - document.querySelector(".steps").offsetHeight - 8;
      window.scrollTo({top, behavior: "smooth"});
    });
  }));
  wireSlider("main-zoom", () => Math.round(state.main.crop.zoom * 100), v => state.main.crop.zoom = v / 100, v => v + "%");
  wireSlider("main-fx", () => Math.round(state.main.crop.fx * 100), v => state.main.crop.fx = v / 100, v => v);
  wireSlider("main-fy", () => Math.round(state.main.crop.fy * 100), v => state.main.crop.fy = v / 100, v => v);
  wireSlider("darkness", () => Math.round(state.darkness * 100), v => state.darkness = v / 100, v => v + "%");
  ["main-zoom","main-fx","main-fy","darkness"].forEach(id => $(id).addEventListener("change", () => changed(true)));

  const logoSync = seg($("logo-color"), [{v: "#ffffff", label: "White"}, {v: "#262626", label: "Charcoal"}, {v: "#ff4d03", label: "Orange"}],
    () => state.logoColor, v => { state.logoColor = v; logoSync(); changed(true); });
  syncers.push(logoSync);
  $("show-logo").addEventListener("change", e => { state.showLogo = e.target.checked; changed(true); });
  $("show-handle").addEventListener("change", e => { state.showHandle = e.target.checked; changed(true); });
  $("handle").addEventListener("input", e => { state.handle = e.target.value; changed(true); });
  syncers.push(() => { $("show-logo").checked = state.showLogo; $("show-handle").checked = state.showHandle; $("handle").value = state.handle; });

  // Slide text
  $("slide-text").addEventListener("input", e => {
    const s = cur(); s.text = e.target.value; const n = wordCount(s.text);
    s.highlights = s.highlights.filter(i => i < n); if (s.emphasis != null && s.emphasis >= n) s.emphasis = null;
    buildWords(); syncEm(); changed();
  });
  const modeSync = seg($("pick-mode"), [{v: "hl", label: "Highlight it"}, {v: "em", label: "Emphasize it"}], () => state.pickMode, v => {
    state.pickMode = v; modeSync();
    $("pick-hint").textContent = v === "hl" ? "Highlight: tap any words to put a color block behind them. Tap again to remove." : "Emphasize: tap one word to add the graphic. Tap it again to remove.";
  });
  modeSync();

  const parts = [
    swatches($("hl-colors"), PALETTE, () => cur().hlColor, v => { cur().hlColor = v; syncSlideParts.forEach(f => f()); buildWords(); changed(); }),
    swatches($("em-colors"), PALETTE, () => cur().emColor, v => { cur().emColor = v; syncSlideParts.forEach(f => f()); buildWords(); buildGfx(); changed(); }),
    swatches($("text-colors"), TEXT_COLORS, () => cur().textColor, v => { cur().textColor = v; syncSlideParts.forEach(f => f()); changed(); }),
    swatches($("bg-colors"), PALETTE, () => cur().bgColor, v => { cur().bgColor = v; syncSlideParts.forEach(f => f()); changed(); }),
  ];
  const segs = [
    [$("font-seg"), [{v: "tommy", label: "Tommy Soft"}, {v: "bring", label: "Bringbold"}], "font"],
    [$("vpos-seg"), [{v: "top", label: "Top"}, {v: "middle", label: "Middle"}, {v: "bottom", label: "Bottom"}], "vpos"],
    [$("align-seg"), [{v: "left", label: "Left"}, {v: "center", label: "Center"}], "align"],
    [$("bg-seg"), [{v: "main", label: "Main photo"}, {v: "custom", label: "Different photo"}, {v: "solid", label: "Solid color"}], "bg"],
  ];
  segs.forEach(([el, opts, key]) => { let f; f = seg(el, opts, () => cur()[key], v => { cur()[key] = v; syncSlide(); changed(); }); parts.push(f); });
  syncSlideParts = parts;

  wireSlider("text-size", () => cur().size, v => cur().size = v, v => v);
  wireSlider("s-zoom", () => Math.round(cur().crop.zoom * 100), v => cur().crop.zoom = v / 100, v => v + "%");
  wireSlider("s-fx", () => Math.round(cur().crop.fx * 100), v => cur().crop.fx = v / 100, v => v);
  wireSlider("s-fy", () => Math.round(cur().crop.fy * 100), v => cur().crop.fy = v / 100, v => v);
  wireDrop($("drop-slide"), $("slide-photo"), f => readPhoto(f, (img, name) => { const s = cur(); s.img = img; s.imgName = name; s.crop = {zoom: 1, fx: .5, fy: .5}; syncSlide(); changed(); }));

  $("em-tint").addEventListener("change", e => { cur().emTint = e.target.checked; changed(); });
  $("em-script").addEventListener("change", e => { cur().emScript = e.target.checked; changed(); });
  $("clear-em").addEventListener("click", () => { cur().emphasis = null; buildWords(); syncEm(); changed(); });

  $("move-left").addEventListener("click", () => { const i = state.current; if (!i) return; const a = state.slides; [a[i - 1], a[i]] = [a[i], a[i - 1]]; state.current--; syncSlide(); changed(true); });
  $("move-right").addEventListener("click", () => { const i = state.current, a = state.slides; if (i >= a.length - 1) return; [a[i + 1], a[i]] = [a[i], a[i + 1]]; state.current++; syncSlide(); changed(true); });
  $("dup-slide").addEventListener("click", () => { const s = cur(); const c = {...s, id: uid++, highlights: [...s.highlights], crop: {...s.crop}}; state.slides.splice(state.current + 1, 0, c); state.current++; syncSlide(); changed(true); });
  let delArmed = null;
  $("del-slide").addEventListener("click", e => {
    if (state.slides.length < 2) return;
    if (!delArmed) { e.target.textContent = "Click again to delete"; delArmed = setTimeout(() => { e.target.textContent = "Delete"; delArmed = null; }, 2500); return; }
    clearTimeout(delArmed); delArmed = null; e.target.textContent = "Delete";
    state.slides.splice(state.current, 1); state.current = clamp(state.current, 0, state.slides.length - 1); syncSlide(); changed(true);
  });
  let resetArmed = null;
  $("reset-btn").addEventListener("click", e => {
    if (!resetArmed) { e.target.textContent = "Click again to clear every slide"; resetArmed = setTimeout(() => { e.target.textContent = "Start a new carousel"; resetArmed = null; }, 3000); return; }
    clearTimeout(resetArmed); resetArmed = null; e.target.textContent = "Start a new carousel";
    const keep = state.slides[0]; state.slides = [newSlide(keep)]; state.current = 0; state.view = 0;
    syncSlide(); goStep(state.main.img ? 2 : 1);
  });

  const resSync = seg($("res-seg"), [{v: "std", label: "1080 × 1350 (standard)"}, {v: "hi", label: "2160 × 2700 (high resolution)"}],
    () => state.hiRes ? "hi" : "std", v => { state.hiRes = v === "hi"; resSync(); queueSave(); buildFinal(); });
  syncers.push(resSync);

  $("view-prev").addEventListener("click", () => { state.view = clamp(state.view - 1, 0, finals.length - 1); showView(); });
  $("view-next").addEventListener("click", () => { state.view = clamp(state.view + 1, 0, finals.length - 1); showView(); });
  let sx = null; const fr = document.querySelector(".viewer .frame");
  fr.addEventListener("pointerdown", e => sx = e.clientX);
  fr.addEventListener("pointercancel", () => sx = null);
  fr.addEventListener("pointerup", e => { if (sx == null) return; const d = e.clientX - sx; sx = null; if (Math.abs(d) > 40) { state.view = clamp(state.view + (d < 0 ? 1 : -1), 0, finals.length - 1); showView(); } });
  document.addEventListener("keydown", e => { if (state.step !== 3 || e.target.closest("input,textarea")) return;
    if (e.key === "ArrowLeft") $("view-prev").click(); if (e.key === "ArrowRight") $("view-next").click(); });

  if (useShare) {
    $("dl-all").textContent = "Save all slides to Photos";
    document.querySelector("#step-3 .lede").textContent = "Swipe through the carousel the way it will look on Instagram. Tap Save all slides to Photos, then tap Save Images in the menu that opens. They land in your camera roll, ready for Instagram.";
  }
  // Let the pinned preview and bottom buttons get out of the way while the phone keyboard is open.
  document.addEventListener("focusin", e => { if (e.target.matches("textarea, input[type=text]")) document.body.classList.add("typing"); });
  document.addEventListener("focusout", e => { if (e.target.matches("textarea, input[type=text]")) document.body.classList.remove("typing"); });
  $("dl-all").addEventListener("click", async () => {
    if (!useShare) return zipAll();
    const st = $("dl-status");
    if (!finals.length) { st.className = "status"; st.textContent = "Rendering slides…"; await finalsReady; }
    await shareFiles(finals.map((f, i) => new File([f.blob], fileName(i), {type: "image/png"})));
  });
  const zipAll = async () => {
    const st = $("dl-status");
    if (!window.JSZip) { st.className = "status err"; st.textContent = "The ZIP tool didn't load. Use Save PNG under each slide instead."; return; }
    st.className = "status"; st.textContent = "Packing " + finals.length + " slides…";
    await finalsReady;
    const zip = new JSZip(); finals.forEach((f, i) => zip.file(fileName(i), f.blob));
    const blob = await zip.generateAsync({type: "blob"});
    saveFile("six-figure-slp-carousel.zip", blob);
  };
}

/* ---------- Boot ---------- */
async function boot() {
  restore();
  wire();
  try {
    await Promise.all([document.fonts.load('700 80px "Tommy Soft"'), document.fonts.load('80px "Bringbold"'), document.fonts.load('80px "Bringbold Oblique"'), document.fonts.load('600 30px "DM Sans"')]);
  } catch (e) {}
  const imgs = await Promise.all(Object.entries(SRC).map(async ([k, src]) => [k, await loadImg(src)]));
  imgs.forEach(([k, img]) => masks[k] = toMask(img));
  syncers.forEach(f => f());
  goStep(1);
}
boot();
})();
