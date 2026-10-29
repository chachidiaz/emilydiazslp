# Notes for Claude

## lab.emilydiazslp.com

`lab/` holds Claude artifacts, docs, and landing pages for lab.emilydiazslp.com (see `lab/README.md`).

- Work on drafts in your session branch.
- When the user says to **publish**, commit the change, merge it into `main`, and push `main`.
  `.github/workflows/deploy-lab.yml` then copies `lab/` to Hostinger over SSH.
- Add a link to every new page in `lab/index.html`.
- This repo is public: anything committed is visible on GitHub even before it is published.
