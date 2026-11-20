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
│   └── site.ts            ← site name, menu links, social links, sale bar
├── styles/
│   └── global.css         ← site-wide styles
└── pages/                 ← one file per page (the file name becomes the URL)
    ├── index.astro             ← /
    ├── about.astro             ← /about/
    ├── aac-services.astro      ← /aac-services/
    ├── business-coaching.astro ← /business-coaching/
    ├── courses.astro           ← /courses/   (old address /courses-2 redirects here)
    ├── contact.astro           ← /contact/
    └── 404.astro               ← "page not found"
public/                    ← files copied as-is
├── images/  fonts/  videos/   ← assets copied from the old WordPress site
├── contact-submit.php     ← sends the contact form by email (Hostinger runs PHP)
└── .htaccess              ← redirects and caching
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

## Moving off WordPress

These pages are a hand-built copy of the old WordPress/Elementor site (same copy,
images, fonts and colors), with no WordPress or Elementor left in them.

`.github/workflows/deploy-site.yml` builds the site and uploads `dist/` over FTP.
It only runs when started by hand (GitHub → Actions → **Deploy site** → Run workflow),
because the first run replaces the live site. Before running it:

1. Back up the WordPress site in Hostinger (hPanel → Files → Backups).
2. Decide where the new site goes: either empty the folder the domain serves
   (usually `public_html/`) of WordPress files, or create a fresh folder and point
   emilydiazslp.com at it in hPanel. WordPress's own `index.php` and `.htaccess`
   would otherwise keep answering requests.
3. Add the repository secret `SITE_FTP_SERVER_DIR` with that folder (e.g. `public_html/`).
   The FTP login secrets are shared with the lab deploy.
4. Pages that were not in the menu (free resources, therapy, coaching, school contracts,
   privacy policy, terms, website disclaimer) still link to their old WordPress
   addresses. Rebuild them here before switching, or they will stop working.
