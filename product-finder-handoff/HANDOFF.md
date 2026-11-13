# Handoff: SLP Product Finder

A step-by-step quiz that helps a speech-language pathologist (SLP) land on one strong digital product idea. Five questions in, one product idea out, plus what goes in it, why it fits, the first three moves and two backup ideas.

**Status:** design and working build are done. It needs to be deployed on a self-hosted platform. **No AI or outside services**: everything is plain HTML, CSS and vanilla JavaScript in one file.

---

## Files in this folder

| File | What it is |
| --- | --- |
| `product-finder.html` | The finished, working build. One self-contained file: inline CSS, inline JS, logo embedded as a base64 PNG. No build step, no dependencies. |
| `sixfigure-wordmark-orange.png` | The same logo as a separate file (520×169, transparent), in case the host wants it as a real image instead of base64. |
| `HANDOFF.md` | This document. |

Reference design (Claude Design canvas, owner-only unless shared): https://claude.ai/artifact/1H2kFhuQ6Jiwu8fFu6PCiX. It's the same UI as `product-finder.html`; **the HTML file is the source of truth.**

---

## What the user experiences

1. **Intro screen**: logo header, "5 quick questions" sticker, headline "Find the *product* only you can make.", one primary button "Let's Find It", and five numbered cards previewing the steps.
2. **Five question screens**, one per screen, single-choice answer cards:
   1. Who you serve (6 options)
   2. What you know (9 options + **Other** with a free-text field, max 40 characters)
   3. Your time per week (4 options)
   4. How you create (4 options)
   5. Your goal (4 options)
3. **Result screen**:
   - product title (e.g. "The AAC Mini-Course") and a one-sentence pitch
   - pills: format, price tier (words only, never dollar amounts), build time
   - **Copy My Idea** (copies a plain-text summary) and **Change My Answers**
   - cards: What's inside, Why it fits you, Your first three moves
   - **Two more ideas worth a look**, each with a "Show Me This One" button that swaps it in. A "Back to my top pick" link then appears.
4. **Footer** on every screen: © year, Terms of Service, Privacy Policy.

### Navigation (the user's top priority: "very easily navigatable")

- **Desktop:** a sticky progress card on the left lists every step with the answer given. Any reached step is clickable. Steps not yet reached are disabled. "Your idea" unlocks after step 5.
- **Phone (≤760px):** the progress card collapses into a horizontal row of numbered discs at the top.
- **Back / Next** bar is `position: sticky; bottom: 0`, so it's always in thumb reach on phones.
- **Next** stays disabled until an answer is picked. For "Other" it also needs non-empty text, and Enter in the text field advances.
- **Start Over** in the header resets everything.
- Changing an earlier answer updates the result right away.

---

## How the result is decided (all rule-based)

All data lives at the top of the `<script>` in `product-finder.html`. The data objects are near line 180, the logic just below.

- **`STEPS`**: questions, help text and options. Audience options carry `obj` (used in the pitch). Expertise options carry `topic` (used in the title) and `outcome` (used in the pitch). Time options carry `level` 1–4.
- **`FORMATS`**: 8 product formats: `toolkit`, `templates`, `library`, `membership`, `minicourse`, `course`, `workshop`, `coaching`. Each has a name, a short title word, a description, a price tier, a build time and three "what's inside" lines.
- **`topFormat(answers)`** picks the main format:
  - `print` (Writing & designing) → `membership` if (6+ hrs and goal = signature) or (6+ hrs and goal = income); else `toolkit`
  - `systems` → `library` if 6+ hrs and goal is income/signature; else `templates`
  - `camera` → `course` if (6+ hrs and goal is income/signature) or (goal = signature and 3+ hrs); else `minicourse`
  - `live` → `coaching` if goal is income/signature and 3+ hrs; else `workshop`
- **Title** = `"The " + expertise.topic + " " + format.short`
- **Pitch** = `format.desc + " that helps " + audience.obj + " " + expertise.outcome + "."`
- **Other:** topic is the typed text in Title Case. Outcome is `"get confident and consistent with " + typed text`.
- **`WHY`**: one line each, keyed by the comfort, time and goal answers.
- **`MOVES`**: three "first moves" per goal.
- **`PREFS`**: ordered format list per comfort answer. The backup ideas are the first two entries that aren't the format being shown.

To change any copy, edit these objects. No other code needs to change.

---

## Brand: The Six Figure SLP design system

Use these exact values. Don't introduce new colors or fonts.

**Colors**

| Token | Hex | Use |
| --- | --- | --- |
| orange | `#ff4d03` | Primary button fill, accent word in headlines (24px+ only), numerals |
| orange-text | `#c93a00` | Small orange text (eyebrows) |
| pink | `#f3b9b6` | Selected answer card, backup-idea cards, footer links on berry |
| lime | `#d0ce5d` | Sticker, price-tier pill |
| sky | `#d0e8f5` | Arrow discs on light buttons, "Why it fits" card, current-step highlight |
| berry | `#942949` | Footer background (text on it is cream; links pink) |
| ink | `#24101a` | All text, focus rings |
| ink-muted | `#6b4f58` | Secondary text |
| cream | `#fdf8f2` | Page background |
| white | `#ffffff` | Cards |
| line | `#ead9cf` | Hairlines, unselected card borders |

**Type**: only two families:
- **Bringbold Nineties** (weight 400 only, never bold, never all caps): hero headline, result title, card titles, numerals, "Product Finder" label.
- **MADE Tommy Soft** (400/500/700): everything else, letter-spacing −0.01em on body and about −0.025em on headings, `font-synthesis: none`.
- Never mix both fonts in one line. Emphasis is color only, as with the orange word "product".

**Shape:** cards 20px radius, buttons and pills fully rounded, thumbnails and inputs 12px. Flat: no shadows on cards, no gradients, no emoji. The only icons are arrows inside discs, plus check marks.

**Buttons:** a pill with an arrow disc. One primary (orange) per view. The light variant is white with a 2px ink border and a sky disc.

**Logo:** orange wordmark on cream or white only, at least 160px wide (it's 170px in the header). Never place it on orange or berry: there is no reversed version.

**Voice:** friendly, first person to "you", short sentences. Title Case on buttons, sentence case elsewhere. Never invent stats, client counts or income claims.

---

## Deploying

1. Upload `product-finder.html` to the host. Rename it to `index.html` if it should live at a folder URL.
2. **Fonts (required for the brand look):** create a `fonts/` folder next to the page containing:
   - `BringboldNineties-Regular.woff2`
   - `MadeTommySoft-Regular.otf`
   - `MadeTommySoft-Medium.otf`
   - `MadeTommySoft-Bold.otf`

   Get them from The Six Figure SLP design system (https://claude.ai/artifact/PaXHgsEmby8v8vqjRFUhAQ, `project/fonts/`) or from the owner. Without them the page falls back to Georgia and system sans and still works. The `@font-face` rules are at the top of the `<style>` block, so change those paths if the fonts live elsewhere.
   - ⚠️ **MADE Tommy Soft requires a commercial licence from MadeType** before use on a live site.
   - ⚠️ **Don't commit the font files to a public repo.**
3. **Platforms that only allow a "custom code" block** (Kajabi, Wix, etc.):
   - Paste the contents of `<style>`, the `<div id="app">`, the footer and the `<script>`.
   - Keep the `#app` and `#live` elements: the script renders into `#app` and announces copy actions in `#live`.
   - Check that the host's own CSS doesn't override `button` or `input` styles.
4. Serve over **HTTPS**. "Copy My Idea" uses the Clipboard API there and falls back to `execCommand('copy')` elsewhere.

### Open items to confirm before going live

- [ ] **Legal links:**
  - `https://thesixfigureslp.com/terms`
  - `https://thesixfigureslp.com/privacy-policy`

  These were guessed and haven't been checked. They're in the `<footer>` near line 170.
- [ ] Font licence and font files in place.
- [ ] Optional: add analytics, or an email capture before the result. Neither exists yet. If you add email capture, the privacy policy must cover it.
- [ ] Optional: add `<meta property="og:*">` tags and a favicon for sharing.

---

## Technical notes

- One IIFE, ES5-style JS, no frameworks. State is `{ step, answers, override, copied }`. `step` 0 is the intro, 1–5 are questions, 6 is the result.
- `render()` rebuilds `#app` with `innerHTML` from template strings. **All dynamic text goes through `esc()`**, including the free-text "Other" answer. Keep it that way.
- Typing in the "Other" field updates state **without** re-rendering, so the cursor isn't lost. Only the Next button's disabled state is toggled.
- Clicks use event delegation on `#app` via `data-action` attributes: `begin`, `restart`, `go`, `back`, `next`, `edit`, `pick`, `alt`, `top`, `copy`.
- Accessibility:
  - answer cards are `role="radio"` with `aria-checked` inside a `role="radiogroup"`
  - every step change moves focus to the screen heading (`tabindex="-1"`)
  - the current step has `aria-current="step"`
  - focus rings are 3px ink with a 3px offset, and cream on the footer
  - touch targets are at least 44px
  - `prefers-reduced-motion` turns off transitions
  - no answer state is shown by color alone (selected cards also get a check)
- Responsive: a fluid layout with a `flex-wrap` sidebar and content. Breakpoint at 760px. There was no horizontal scroll at 390px or 1280px.
- Nothing is saved: refreshing the page starts over. Use `localStorage` if persistence is wanted, wrapped in try/catch.

## Tested

In headless Chromium at 1280px and 390px:
- a full run-through with "Other" on step 2 ("voice therapy" gives "The Voice Therapy Course: a step-by-step video course that helps school SLPs get confident and consistent with voice therapy.")
- Next stays disabled until text is entered
- the logo loads, the footer links resolve to the URLs above
- no console errors, no horizontal overflow

Not yet tested: real iOS Safari and Android devices, screen readers, and the target host's CSS environment.
