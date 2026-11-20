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
├── contact-submit.php     ← emails the contact and inquiry forms to hello@emilydiazslp.com (Hostinger runs PHP)
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

**Preview:** every push to `main` that changes the site uploads a copy to
https://lab.emilydiazslp.com/new-site/ (`.github/workflows/deploy-site-preview.yml`,
using the lab's FTP login). That copy is hidden from search engines and doesn't send
Google Analytics data. `scripts/rebase-dist.mjs` moves the build's links under `/new-site/`.

**Going live:** `.github/workflows/deploy-site.yml` builds the site and uploads `dist/` over FTP.
It only runs when started by hand (GitHub → Actions → **Deploy site** → Run workflow),
because the first run replaces the live site. Before running it:

1. Back up the WordPress site in Hostinger (hPanel → Files → Backups).
2. Decide where the new site goes: either empty the folder the domain serves
   (usually `public_html/`) of WordPress files, or create a fresh folder and point
   emilydiazslp.com at it in hPanel. WordPress's own `index.php` and `.htaccess`
   would otherwise keep answering requests.
3. Add the repository secret `SITE_FTP_SERVER_DIR` with that folder (e.g. `public_html/`).
   The FTP login secrets are shared with the lab deploy.
4. The old WordPress addresses that duplicated a page (`/aac-evals-2`, `/speech-therapy`,
   `/speaking`, `/1-1-coaching`, `/privacy-policy-2`, `/courses-2`) and the course sales
   pages (`/hub`, `/handbook-always`, `/glp-workshop-evergreen`, `/when-aac-isnt-working…`)
   redirect in `public/.htaccess`. The sales pages now live on lab.emilydiazslp.com.
5. If the server has other non-WordPress folders (for example `aac-hub/`), keep them when
   clearing out WordPress.

Every page loads the same Flodesk and Deadline Funnel scripts as the old site
(`src/layouts/BaseLayout.astro`). The free resource library sign-up on `/free-resources/`
is the Flodesk form that fed the "Resource Library" segment; change its look in Flodesk.
