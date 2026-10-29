# Emily Diaz, SLP — Design System

Brand and UI system for **EmilyDiazSLP**, the practice of Emily Diaz, M.S. CCC-SLP — a speech-language pathologist and **AAC specialist**. She serves two audiences at once:

1. **Families** — AAC evaluations, speech & language therapy (in-home and virtual in AZ, TX, IN), 1:1 coaching.
2. **Professionals** — SLPs, teachers, paraprofessionals and school districts: contracts & consulting, professional development, speaking, and digital products (The AAC Assessment Hub, The AAC Handbook, workshops).

The brand voice is warm, first-person and confessional — a peer who has been where you are, not an institution. Visually it is soft, feminine and unfussy: deep plum and rose on cream, one handwritten script accent per section, generous whitespace, pill buttons, rounded photos.

## Sources used to build this system

| Source | What was taken from it |
| --- | --- |
| `uploads/emily-maroon-logo.png` | Primary logo lockup → `assets/logo-maroon.png` |
| `uploads/Oranges And Lemons Script.otf`, `… Script Upright.otf`, `OrangesAndLemonsCaps.otf` | Display/script typefaces → `assets/fonts/`, `@font-face` in `tokens/fonts.css` |
| Brand color list supplied in the brief | `#743E5A`, `#C45B8F`, `#FFFBFD`, `#E192B9`, `#FFFFFF` → `tokens/colors.css` (exact values, unrounded) |
| http://emilydiazslp.com (public pages: `/`, `/about`, `/aac-services`, `/courses-2`) | Copy, page structure, section rhythm, nav labels, iconography, photography |
| Poppins (Google Fonts) | Body + heading typeface, per the brief |

The original uploads were byte-identical to the files in `assets/`, so only `assets/` is kept in this repo.

No codebase or Figma file was provided; the site is WordPress/Elementor, so structure was read from the rendered public pages rather than component source. The member area (`learn.emilydiazslp.com`) is login-gated and was **not** recreated — nothing about it was invented.

---

## Index

- `styles.css` — the single entry point consumers link. `@import` list only.
- `tokens/` — `fonts.css`, `colors.css`, `typography.css`, `spacing.css`, `effects.css`, `base.css`
- `assets/` — `logo-maroon.png`, `fonts/*.otf`
- `guidelines/` — 17 foundation specimen cards (Colors, Type, Spacing, Brand)
- `components/core/` — **Button, Badge, Card, SectionHeading**
- `components/forms/` — **Input, Textarea, Select, Checkbox**
- `components/content/` — **ServiceCard, CourseCard, QuoteBlock, BulletList**
- `ui_kits/website/` — click-through recreation of emilydiazslp.com (see its README)
- `templates/service-page/ServicePage.dc.html` — copy-and-fill service/offer landing page (script hero, service cards, plum band, opt-in)
- `thumbnail.html`, `SKILL.md`

Every component directory holds `<Name>.jsx`, `<Name>.d.ts`, `<Name>.prompt.md` and one `@dsCard` HTML card.

### Intentional additions
The brief supplied brand guidelines only (no component inventory), so the component set is a standard small kit sized to what the live site actually uses: buttons, badges, cards, section headings, four form fields, and four content blocks (service card, course card, quote, bullet list). `SectionHeading`, `ServiceCard`, `CourseCard`, `QuoteBlock` and `BulletList` are direct abstractions of repeated patterns on the live site.

---

## CONTENT FUNDAMENTALS

**Voice.** First person singular for Emily herself ("I share about what's worked for me"), first person plural for the practice ("Our practice values neurodiversity affirming services"). Addressed as **you**, never "clients" or "users". Warm, plain, direct — a colleague talking, not a clinic writing.

**Vulnerability is the hook.** Copy leads with the hard part, then the turn: *"I'm an AAC Specialist, but I have a confession to make."* / *"**I was failing.**"* / *"I know from experience how it feels to know nothing about devices, have zero staff buy-in, and no time to train teachers."* Bold is used sparingly, for exactly that emotional beat.

**Question → answer structure.** Sections open with a question or an offer and answer it immediately: *"How can I help?" → "AAC Services" → "let's tackle aac together"*; *"Ready to work together?" → "Here's how I can help."*

**Casing is a system:**
- Navigation and CTA labels: **lowercase** — `about me`, `aac courses + guides`, `book a free consult`, `request more info`, `see the full list of services`.
- Headlines and section titles: **Sentence or Title case** — "AAC Evaluations", "Professional Bio", "Evaluations & therapy services".
- Eyebrows above headings: **UPPERCASE** with wide tracking.
- Acronyms always capitalized: AAC, SLP, GLP, IEE, IEP.
- `&` and `+` are used freely in headings ("Speech & Language Therapy", "aac courses + guides").

**Sentence rhythm.** Short declaratives mixed with one long, listing sentence. Ellipses and em-dashes carry the conversational turn: *"…or you could let us guide you along the way."* Numbers are concrete and specific: "60-minute evaluation", "4–6 weeks", "12-week coaching program", "since 2015".

**Clinical accuracy without jargon walls.** Terms are named then translated: "augmentative & alternative communication (AAC)", "gestalt language processing". Language is neurodiversity-affirming — "complex communicators", "finding your voice", never deficit framing.

**Emoji: no.** The site uses none. Do not introduce them. Warmth comes from script type, photography and phrasing.

**Personality lists** are allowed and are part of the brand ("Coffee order: vanilla almond milk latte, half sweet"). Keep them to five short items, no punctuation at line ends.

---

## VISUAL FOUNDATIONS

**Color.** Deep plum `#743E5A` is the brand anchor: logo, headings, primary buttons, inverse sections. Rose `#C45B8F` is the action/accent color: links, eyebrows, secondary buttons, script accents. Blush `#E192B9` and blush-100 `#F6D9E7` are quiet-section backgrounds and icon medallions. Cream `#FFFBFD` is the default page background — pure white is reserved for cards so they read as lifted off the page. Neutrals are warmed toward the plum hue (`--ink-700` `#5A4A53` for body copy), never cold grey. Max two background tones per screen plus one plum band.

**Type.** Poppins does all the work: **Bold 700** for nav, headlines and buttons; **Regular 400** for body at `line-height: 1.75`. Oranges And Lemons Script is the emotional accent — one line per section, at 40–116px, set in plum or rose, `line-height ~.95`, always lowercase phrasing ("voice.", "let's tackle aac together", "here's how I can help."). Never set script below 32px and never for body copy or UI labels. The Caps face is for short lockup-style words only. Tracking: `.16em` on uppercase eyebrows, `.04em` on nav.

**Layout.** 1160px max container, 24px gutters, 96px section padding (64px tight). Two-column splits dominate: copy on one side, a photo or device mockup on the other, alternating side down the page. Content columns cap at ~62ch. Header is sticky with a plum "member login" utility bar above it. Nothing else is fixed or floating.

**Backgrounds.** Flat color only — cream, white, blush, or a full-bleed plum band. **No gradients**, no noise, no repeating patterns. Photography is cut-out (transparent PNG/WebP of Emily) placed over a soft blush organic blob shape (`--radius-blob`), or full-bleed rounded rectangles at `--radius-xl` (32px). One decorative hand-drawn flourish asset appears near the footer.

**Imagery vibe.** Bright, warm, high-key: white and cream backdrops, natural light, plum/pink wardrobe, generous negative space. Never cool-toned, never grainy, never B&W — except partner logos, which run grayscale at 60–65% opacity.

**Cards.** White fill, 20px radius (`--radius-lg`), plum-tinted shadow `--shadow-sm`, 32px padding, no border by default. Never a colored left-border accent. Course cards clip a photo to the same 20px radius with `overflow: hidden`.

**Shadows.** All shadows are plum-tinted (`rgba(74,39,57,…)`), soft and low-contrast — `xs` for chrome, `sm` at rest, `md` on hover, `lg` for the one hero card per page. Inner shadows are effectively unused; `--shadow-inset-soft` exists only for a subtle top highlight on tinted surfaces.

**Borders & radii.** 1px hairline `--ink-200` on fields and outlined containers, 2px `--border-thick` on outline buttons and checkboxes. Radii: 6 / 12 / 20 / 32 / pill. Buttons are always fully pill. Nothing in the system has a 0px corner.

**Motion.** Restrained and short: 200ms `cubic-bezier(.22,.61,.36,1)` for color and shadow, 120ms for press. Cards lift `translateY(-3px)` and deepen one shadow step on hover; course-card images scale 1.03 over 400ms. No bounce, no spring, no scroll-triggered choreography, no parallax. Fades are acceptable for menus; nothing slides more than a few pixels.

**States.** Hover *darkens* fills (plum → `--plum-900`, rose → `#AB4A7B`) rather than lightening them; outline buttons invert to a plum fill; text links go rose → plum. Press: same darkened fill plus `scale(.97)`. Focus: 3px rose ring at 35% opacity (`--shadow-focus`), never a browser outline. Disabled: 45% opacity, no color change.

**Transparency & blur.** Used sparingly and only on plum: body copy on plum sections is `rgba(255,251,253,.82)`, dividers `rgba(255,255,255,.28)`, icon medallions on plum `rgba(255,255,255,.12)`. There is no glassmorphism. `--blur-soft` exists for a sticky-header treatment if ever needed; the live site doesn't use it. Text over photography is avoided entirely — so no protection gradients or scrims are needed; when a label must sit on an image, use an opaque blush or white capsule instead.

---

## ICONOGRAPHY

The live site uses **flat, multi-color illustrated PNG icons** (a stock set in the Flaticon idiom — `shopping.png`, `heart.png`, `school-bag.png`, `speech.png`, `job-interview.png`), sized ~40–48px and centered in a blush circular medallion on service cards. Social icons are plum PNG glyphs (Instagram, Facebook, YouTube) plus a small key/login PNG in the footer. There is **no icon font, no SVG sprite, and no line-icon set**. Emoji are never used. Unicode is used only for the `✓` inside checkboxes and check-markers.

**Substitution flagged:** these icon PNGs, the photography, and the partner logos live on emilydiazslp.com and could only be reached over the network — they are **hot-linked by URL** in the UI kit and component cards, not copied into `assets/`. `assets/` therefore contains only the logo and the OTF fonts. If you need offline or production-safe assets, export the icon set and photography from WordPress and drop them into `assets/icons/` and `assets/photos/`; then swap the URLs in `ui_kits/website/Chrome.jsx` (the `IMG` map).

If a new UI needs an icon that doesn't exist in Emily's set, use a **filled, single-color plum glyph** (Phosphor "fill" or Heroicons solid via CDN) rather than inventing a multi-color illustration — and flag it.
