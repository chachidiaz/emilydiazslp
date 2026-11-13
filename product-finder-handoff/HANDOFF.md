# Handoff: SLP Product Finder

A step-by-step tool that helps a speech-language pathologist (SLP) land on one strong digital product idea, then **test it against the market** before building anything.

- **Part 1, Find your idea:** five questions lead to one product idea, plus what goes in it, why it fits, the first three moves and two backup ideas.
- **Part 2, Test it against the market:** a guided research check answers three questions:
  - Is anyone already paying for this?
  - If so, what are they paying?
  - If nobody sells it, is that a gap or a sign there's no demand?

  It ends in a market verdict and next steps.

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
3. **Idea screen**:
   - product title (e.g. "The AAC Mini-Course") and a one-sentence pitch
   - pills: format, price tier (words only, never dollar amounts), build time
   - actions: **Test It Against the Market** (primary), **Copy My Idea**, and a "Change my answers" link
   - cards: What's inside, Why it fits you
   - a berry "Before you build anything: test it against the market" panel
   - Your first three moves (move 1 is always "run the market check")
   - **Two more ideas worth a look**, each with a "Show Me This One" button that swaps it in. A "Back to my top pick" link then appears.
4. **Market check 1 of 3: Who's selling it** ("Is anyone already paying for this?")
   - Three ready-made search phrases built from the idea, such as "AAC printables" and "AAC printables for parents".
   - Six "where to look" cards, each linking to a search for the idea in a new tab:
     - Teachers Pay Teachers
     - Etsy
     - Google (look at "People also ask")
     - Amazon
     - Reddit (search the problem, not the product)
     - TikTok/Instagram
   - **What did you find?** (pick one; required before moving on):
     - Lots of similar products (5+)
     - A handful (1–4)
     - Close cousins only
     - Nothing at all
5. **Market check 2 of 3: What they charge** ("What are people paying?")
   - Up to three products, each with name, price ($) and number of reviews. Reviews show people actually bought it.
   - A "What do buyers wish was different?" box. Read the 1–3★ reviews: the complaints are their angle.
   - A live summary showing the price range, the middle price and total reviews.
   - If "Nothing at all" was picked, a note says to log close cousins or skip ahead.
6. **Market check 3 of 3: Gap or no demand?** ("Is there real demand behind it?")
   - The intro text changes depending on whether sellers were found.
   - Seven demand-signal checkboxes:
     - people ask about it online
     - search shows the question
     - people ask me about it
     - related products sell
     - they pay for workarounds
     - five conversations
     - someone said "I'd buy that"
   - A **"Talk to five real people"** card with a 5-question interview script.
7. **Market verdict:**
   - a headline verdict, a demand-signal meter (x of 7)
   - **What you found:** sellers, price range, reviews, signals ticked, buyer complaints
   - **What to do next:** three steps
   - buttons: **Copy My Research** (idea, verdict, products logged and next steps as plain text), plus Back to My Idea, or See My Backup Ideas when the verdict is weak
   - a fine-print line saying the verdict comes only from what they entered
8. **Footer** on every screen: © year, Terms of Service, Privacy Policy.

### Navigation (the user's top priority: "very easily navigatable")

- **Desktop:** a sticky progress card on the left has two groups:
  - **Find your idea:** steps 1–5 and Your idea
  - **Test it against the market:** steps 1–3 and Market verdict

  Each entry shows the answer given (or the price range and signal count). Any reached step is clickable, and steps not yet reached are disabled. "Your idea" unlocks after step 5. The market steps unlock with the idea, and market steps 2, 3 and the verdict unlock once market step 1 is answered.
- **Phone (≤760px):** the progress card collapses into a horizontal row of discs at the top, with a thin divider between the two groups.
- **Back / Next** bar is `position: sticky; bottom: 0`, so it's always in thumb reach on phones. It appears on the question and market-check screens.
- **Next** stays disabled until an answer is picked:
  - For "Other" it also needs non-empty text, and Enter in the text field advances.
  - Market step 1 requires "What did you find?". Market steps 2 and 3 are optional.
- **Start Over** in the header resets everything.
- Changing an earlier answer updates the result right away.

---

## How the result is decided (all rule-based)

All data lives at the top of the `<script>` in `product-finder.html`. The content lists are under the `CONTENT` banner comment and the rules under `LOGIC`.

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

### Market check and verdict (also rule-based)

The research is done by the SLP. The tool guides her, records what she finds and turns it into a verdict with fixed rules. **It never fetches anything itself.** The search links simply open the marketplace's own search page in a new tab.

- **`MARKET_INTRO`**: the three market questions, used on the intro and idea screens.
- **`COMP`**: the four "What did you find?" answers: `many`, `few`, `adjacent`, `none`.
- **`SIGNALS`**: the seven demand signals.
- **`SCRIPT`**: the five interview questions.
- **`VERDICTS`**: the headline and body text for each verdict.
- **`searchPhrases()` / `places()`**: build the search phrases from each option's `search` field and the format's `search` word, and the six search URLs.
- **`priceStats()`**:
  - parses the logged prices, stripping `$` and commas
  - returns lowest, highest and middle (median) price, plus total reviews
  - blank or non-numeric entries are ignored
- **`verdictKey(comp, signalCount)`**:

| What they found | Demand signals ticked | Verdict |
| --- | --- | --- |
| Lots (5+) or a handful (1–4) | 2 or more | **go**: "People already pay for this. Go." |
| Lots (5+) | 0–1 | **angle**: "It sells, but it's crowded." |
| A handful (1–4) | 0–1 | **unsure**: "Not enough proof yet." |
| Close cousins or nothing | 3 or more | **gap**: "Looks like a real gap." |
| Nothing at all | 0–1 | **nodemand**: "Probably no demand yet." |
| Anything else | | **unsure** |

- **`verdictMoves()`**: three next steps per verdict.
  - The "go" verdict's price step quotes the range the SLP logged, e.g. "Price inside the range you found ($19–$35), not below it."
  - Weak verdicts (unsure, nodemand) send her back to her backup ideas.
- The research is kept in `state.research` (`comp`, `rows[3]`, `gaps`, `signals`). **Start Over** clears it. It is not saved between visits.

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

  These were guessed and haven't been checked. They're in the `<footer>` just after `<div id="app">`.
- [ ] Font licence and font files in place.
- [ ] Optional: add analytics, or an email capture before the result. Neither exists yet. If you add email capture, the privacy policy must cover it.
- [ ] Optional: add `<meta property="og:*">` tags and a favicon for sharing.

---

## Technical notes

- One IIFE, ES5-style JS, no frameworks. State is `{ step, answers, override, copied, research }`. Steps:
  - 0: intro
  - 1–5: questions
  - 6: idea
  - 7–9: market check
  - 10: verdict

  `render()` won't show a step that isn't unlocked yet.
- `render()` rebuilds `#app` with `innerHTML` from template strings. **All dynamic text goes through `esc()`**, including the free-text "Other" answer. Keep it that way.
- Typing never re-renders, so the cursor isn't lost:
  - The "Other" field toggles only the Next button.
  - The price and review fields update only the live summary (`updateSummary()`).
- Clicks use event delegation on `#app` via `data-action` attributes:
  - `begin`, `restart`, `go`, `back`, `next`
  - `pick`, `comp`, `signal`
  - `alt`, `top`, `copy`
- Market search links are plain `<a target="_blank" rel="noopener">` with screen-reader text "(opens in a new tab)". The demand signals are `role="checkbox"` buttons.
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
- the market check on five scenarios, each giving the expected verdict:

| What they found | Signals ticked | Prices logged | Verdict |
| --- | --- | --- | --- |
| Lots | 2 | $19 and $35 (160 reviews in total) | go, quoting "($19–$35)" |
| Lots | 0 | none | angle |
| Nothing | 3 | none | gap |
| Nothing | 0 | none | nodemand |
| Close cousins | 1 | none | unsure |

- Next on market step 1 stays disabled until "What did you find?" is answered
- the TPT search link builds correctly (e.g. `…/browse?search=AAC%20printables`)

Not yet tested:
- real iOS Safari and Android devices
- screen readers
- the target host's CSS environment
- whether each marketplace's search URL format still works on the day you launch. Click each one once: sites change these occasionally.
