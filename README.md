# emilydiazslp.com

The main website for **Emily Diaz, MS, CCC-SLP**: AAC evaluations, therapy, consulting, and workshops for teens and young adults.

It's a plain static site (no build step):

- `index.html` – page content
- `styles.css` – styles
- `script.js` – mobile menu
- `assets/` – favicon and images
- `CNAME` – custom domain for GitHub Pages

## Preview locally

```bash
python3 -m http.server 8000
```

Then open http://localhost:8000.

## Before launch

Search `index.html` for `TODO` and update:

- Headshot (`assets/emily.jpg`) to replace the photo placeholder
- Contact email (currently `hello@emilydiazslp.com`)
- Contact form action (connect Formspree, Netlify Forms, etc.)
- YouTube channel URL

## Deploying

Works on any static host. For GitHub Pages: Settings → Pages → deploy from the `main` branch root, then point the domain's DNS at GitHub Pages. The `CNAME` file sets the custom domain.
