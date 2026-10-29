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
- This repo is public: anything committed is visible on GitHub even before it is published.
