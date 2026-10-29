# lab.emilydiazslp.com

Home for Claude artifacts, docs, and landing pages, served at https://lab.emilydiazslp.com.
Kept separate from the main Astro site in `src/`; nothing here affects `emilydiazslp.com`.

## Where things live

```
lab/
├── index.html        ← subdomain home page (/)
├── artifacts/        ← Claude artifacts (one folder per artifact)
├── docs/             ← docs and guides
└── landing-pages/    ← landing pages
```

Plain static files, no build step. A file at `lab/docs/intake-guide/index.html`
is served at `/docs/intake-guide/`.

## Adding something

1. Create a folder in the right section, e.g. `lab/landing-pages/fall-workshop/`.
2. Put the page in it as `index.html` (plus any images next to it).
3. Add a link to it in `lab/index.html`.

## Hosting on the subdomain

Point the host at this folder as its root, with no build command:

- **Vercel / Netlify:** new project from this repo, root directory `lab`,
  framework "Other", no build command. Then add `lab.emilydiazslp.com` under Domains.
- **DNS:** add the `CNAME` record for `lab` that the host gives you.
