# Notes for Claude

## Brand

Everything made for Emily Diaz SLP (documents, landing pages, apps, tools, artifacts)
uses the brand design system in `lab/brand/`. Load the `emily-diaz-slp-design` skill
(`.claude/skills/emily-diaz-slp-design/SKILL.md`) and read `lab/brand/README.md` before
designing or writing copy.

## lab.emilydiazslp.com

`lab/` holds Claude artifacts, docs, and landing pages for lab.emilydiazslp.com (see `lab/README.md`).

- Work on drafts in your session branch.
- When the user says to **publish**, commit the change, merge it into `main`, and push `main`.
  `.github/workflows/deploy-lab.yml` then uploads `lab/` to Hostinger over FTP.
- Add a link to every new page in `lab/index.html`.
- Give every new page in `lab/landing-pages/`, `lab/docs/` and `lab/artifacts/` an ID right after
  `<meta charset>`: `<meta name="lab-id" content="LP-004">`, followed by the date it goes live,
  `<meta name="lab-published" content="2026-11-02">` (the dashboard's Published column). Prefixes: `LP-` landing pages, `DOC-` docs,
  `ART-` artifacts. Use the next unused number (`grep -rho --include='*.htm*' 'lab-id" content="[A-Z]*-[0-9]*' lab`).
  When the user names an ID (e.g. "edit LP-002"), find the page with that grep. Fixed IDs: APP-001 deadline
  timers app (`lab/apps/deadlines/`), APP-002 its demo funnel, BR-001 design system, BR-002 website UI kit,
  BR-003 service page template (all under `lab/brand/`), plus the lab dashboard at `lab/admin/`.
- This repo is public: anything committed is visible on GitHub even before it is published.
