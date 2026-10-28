# emilydiazslp.com

Built with [Eleventy](https://www.11ty.dev/). The header and footer are each written once and shared by every page.

## Where things live

```
src/
├── _includes/
│   ├── partials/
│   │   ├── header.njk     ← edit the header here (applies to every page)
│   │   └── footer.njk     ← edit the footer here (applies to every page)
│   └── layouts/
│       └── base.njk       ← page shell: <head>, header, main content, footer
├── _data/
│   ├── site.json          ← site name, URL, and the navigation menu links
│   └── year.js            ← current year for the footer
├── assets/
│   ├── css/main.css       ← styles (header and footer sections are labeled)
│   └── images/
├── index.njk              ← Home page
└── about.njk              ← About page
```

## Adding a page

Create a file in `src/`, e.g. `src/services.njk`:

```
---
layout: layouts/base.njk
title: Services
permalink: /services/
---
<h1>Services</h1>
```

It gets the shared header and footer automatically. To show it in the menu, add it to `nav` in `src/_data/site.json`.

## Commands

```bash
npm install     # once
npm start       # live preview at http://localhost:8080
npm run build   # build the site into _site/ for hosting
```
