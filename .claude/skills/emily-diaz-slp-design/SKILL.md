---
name: emily-diaz-slp-design
description: Emily Diaz, SLP brand and design system (colors, fonts, logo, voice, components, templates). Use it for anything branded for Emily Diaz SLP — a document, landing page, app, tool, artifact, slide, email, social graphic, or a page in this repo (lab/ or the Astro site in src/) — before choosing colors, type, layout or copy tone.
user-invocable: true
---

# Emily Diaz, SLP — brand

The full design system lives in **`lab/brand/`** in this repo. Read `lab/brand/README.md`
first: it covers voice and copy rules, colors, type, layout, cards, motion, states and
iconography. Follow it; don't invent new colors, fonts or icon styles.

## What's where

| Need | File |
| --- | --- |
| Rules for voice, color, type, layout | `lab/brand/README.md` |
| All styles in one link | `lab/brand/styles.css` (imports everything in `tokens/`) |
| Design tokens (CSS variables) | `lab/brand/tokens/colors.css`, `typography.css`, `spacing.css`, `effects.css`, `fonts.css`, `base.css` |
| Logo | `lab/brand/assets/logo-maroon.png` (plum script lockup; on plum, use `filter: brightness(0) invert(1)`) |
| Script/display fonts | `lab/brand/assets/fonts/*.otf` (Oranges And Lemons Script, Script Upright, Caps). Poppins comes from Google Fonts. |
| Specimens for each foundation | `lab/brand/guidelines/*.card.html` |
| React components (Button, Badge, Card, SectionHeading, form fields, ServiceCard, CourseCard, QuoteBlock, BulletList) | `lab/brand/components/` — each has `.jsx`, `.d.ts` (props) and `.prompt.md` (usage) |
| Full website recreation | `lab/brand/ui_kits/website/` |
| Landing page to copy and fill | `lab/brand/templates/service-page/ServicePage.dc.html` |

## Quick reference

- Colors: plum `#743E5A` (headings, primary buttons, inverse bands), rose `#C45B8F`
  (links, eyebrows, accents), blush `#E192B9`, blush-100 `#F6D9E7`, cream `#FFFBFD`
  (page background), white `#FFFFFF` (cards only). Body text `#5A4A53`. No gradients.
- Type: Poppins 700 for headings, nav and buttons; Poppins 400 at line-height 1.75 for body.
  One Oranges And Lemons Script accent line per section, 40px or larger, lowercase phrasing.
- Shape: pill buttons, 20px card radius, soft plum-tinted shadows, no 0px corners.
- Voice: warm, first person, talks to "you". Lowercase nav and button labels. No emoji.

## Using it

**Pages in `lab/`** (served at lab.emilydiazslp.com): link the stylesheet with an absolute
path so it works from any folder depth:

```html
<link rel="stylesheet" href="/brand/styles.css">
<img src="/brand/assets/logo-maroon.png" alt="Emily Diaz, SLP">
```

Then use the CSS variables (`var(--plum-700)`, `var(--font-sans)`, `var(--shadow-sm)`, …)
and the base classes `.eyebrow` and `.script`.

**Pages hosted elsewhere** (Astro site, Kajabi, other domains): link
`https://lab.emilydiazslp.com/brand/styles.css` once the brand folder is published, or copy
the token values in.

**Claude artifacts and self-contained files** (they can't load files from this repo or
lab.emilydiazslp.com): copy the needed CSS variables inline, load Poppins from Google Fonts,
and embed the logo and any Oranges And Lemons font as base64 `data:` URIs read from
`lab/brand/assets/`.

**Word, PDF, slides, social graphics**: use the same hex colors, Poppins for text, the
script font for one accent line, and the logo PNG.

Photography and the flat illustrated icons are hot-linked from emilydiazslp.com and are not
in the repo yet (see "Iconography" in the README).
