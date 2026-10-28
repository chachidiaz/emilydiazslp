# emilydiazslp.com

Built with [Astro](https://astro.build/). The header and footer are each a component, written once and shared by every page.

## Where things live

```
src/
├── components/
│   ├── Header.astro       ← edit the header here (applies to every page)
│   └── Footer.astro       ← edit the footer here (applies to every page)
├── layouts/
│   └── BaseLayout.astro   ← page shell: <head>, Header, page content, Footer
├── data/
│   └── site.ts            ← site name, description, and the menu links
├── styles/
│   └── global.css         ← site-wide styles
└── pages/                 ← one file per page (the file name becomes the URL)
    ├── index.astro        ← /
    └── about.astro        ← /about/
public/                    ← files copied as-is (favicon, images, etc.)
```

Header and footer styles live inside their own `.astro` files and only affect that component.

## Adding a page

Create a file in `src/pages/`, e.g. `src/pages/services.astro` (becomes `/services/`):

```astro
---
import BaseLayout from "../layouts/BaseLayout.astro";
---

<BaseLayout title="Services">
  <h1>Services</h1>
</BaseLayout>
```

It gets the shared header and footer automatically. To show it in the menu, add it to `nav` in `src/data/site.ts`.

## Commands

Requires Node.js 22.23.4 or newer.

```bash
npm install       # once
npm run dev       # live preview at http://localhost:4321
npm run build     # build the site into dist/ for hosting
npm run preview   # preview the built site
```
