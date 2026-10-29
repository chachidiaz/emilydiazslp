# lab.emilydiazslp.com

Home for Claude artifacts, docs, and landing pages, served at https://lab.emilydiazslp.com.
Kept separate from the main Astro site in `src/`; nothing here affects `emilydiazslp.com`.

## Where things live

```
lab/
├── index.html        ← subdomain home page (/)
├── brand/            ← brand design system: colors, fonts, logo, components (/brand/)
├── artifacts/        ← Claude artifacts (one folder per artifact)
├── docs/             ← docs and guides
└── landing-pages/    ← landing pages
```

Plain static files, no build step. A file at `lab/docs/intake-guide/index.html`
is served at `/docs/intake-guide/`.

## Brand

`brand/` holds the design system. Its `README.md` has the rules; link
`/brand/styles.css` from any page to get the colors, fonts and base styles.

## Adding something

1. Create a folder in the right section, e.g. `lab/landing-pages/fall-workshop/`.
2. Put the page in it as `index.html` (plus any images next to it).
3. Add a link to it in `lab/index.html`.

## Publishing

Hosted on Hostinger. Every push to `main` that changes `lab/` runs
`.github/workflows/deploy-lab.yml`, which uploads this folder over FTP. It goes
live about a minute later. Progress shows under the repo's **Actions** tab.

The workflow needs four repository secrets (GitHub → Settings → Secrets and
variables → Actions → New repository secret):

| Secret | Value |
| --- | --- |
| `FTP_SERVER` | FTP host from Hostinger hPanel → Files → FTP Accounts |
| `FTP_USERNAME` | FTP username |
| `FTP_PASSWORD` | FTP password |
| `FTP_SERVER_DIR` | The subdomain's folder, ending in `/`, e.g. `/domains/emilydiazslp.com/public_html/lab/` |
