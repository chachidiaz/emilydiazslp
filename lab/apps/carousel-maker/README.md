# Six Figure SLP Carousel Designer: website version

This folder is the complete website. Upload **everything in this folder**, keeping the
`fonts/` and `images/` subfolders next to `index.html`.

```
index.html        the page
styles.css        layout, colors and brand fonts
app.js            the designer (slide rendering, steps, downloads)
brand-assets.js   the underline, circle and oval graphics plus the logo used on slides
fonts/            MADE Tommy Soft Bold, Bringbold Nineties Regular and Oblique
images/logo.png   logo in the page header and browser tab
```

It is a static site with no server code, database or build step. Photos never leave the
person's browser. Slide text and settings are remembered in that browser only.

## Where to upload it

Any host that serves plain HTML files works. Some options:

- **Your own web host (cPanel, SiteGround, Bluehost and similar):** upload the folder into a
  subfolder such as `public_html/carousel/`. The tool then lives at
  `yourdomain.com/carousel/`.
- **Netlify Drop (free, about 1 minute):** go to app.netlify.com/drop and drag this whole
  folder onto the page. You can connect a subdomain like `carousel.yourdomain.com` later.
- **Vercel, Cloudflare Pages or GitHub Pages:** create a project from this folder. No build
  command is needed, and the output directory is the folder itself.

**Wix and Kajabi** can't host a folder of files like this. Host it with one of the options
above, then link to it from a Wix or Kajabi page or menu. A plain link works better than
embedding it in an iframe, because some embed blocks stop downloads from working.

## Good to know

- The page loads two things from the internet: the DM Sans font (Google Fonts, used only for
  the interface) and JSZip (cdnjs, used for "Download all slides"). If either is blocked,
  the page still works: the interface falls back to the system font, and each slide can be
  saved with its own **Save PNG** button.
- `index.html` has `noindex`, so search engines won't list the tool. Delete that line if you
  want it public.
- **Font license:** uploading the font files makes them publicly downloadable from your site.
  Check that your licenses for MADE Tommy Soft and Bringbold Nineties cover web or
  self-hosted use.
- Opening `index.html` by double-clicking it mostly works, but test it from the uploaded
  site. Browsers limit some features for local files.

## Making changes

Don't edit these files directly. The source is `../src/template.html`. After changing it,
run this from the `Instagram Carousel Maker` folder:

```bash
python3 src/build.py
```

That rebuilds this `website/` folder and the single-file `carousel-designer.html` used for
the Claude artifact.

## Lab notes (lab.emilydiazslp.com)

The live copy at `/apps/carousel-maker/` has phone-layout and iPhone fixes made directly in these
files. The server caches CSS and JS for 7 days, so after changing `styles.css`, `app.js` or
`brand-assets.js`, bump the `?v=` value on their links in `index.html`.
