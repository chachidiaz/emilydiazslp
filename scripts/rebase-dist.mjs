// Moves a finished build (dist/) under a sub-folder, e.g. /new-site/, for the
// preview copy at lab.emilydiazslp.com/new-site/. The pages are written with
// root links ("/about/", "/images/x.png"); this rewrites them to "/new-site/about/".
// Usage: SITE_BASE=/new-site/ node scripts/rebase-dist.mjs
import { readFileSync, writeFileSync, readdirSync, statSync, rmSync, existsSync } from "node:fs";
import { join, extname } from "node:path";

const base = (process.env.SITE_BASE || "/").replace(/\/?$/, "/");
if (base === "/") {
  console.log("SITE_BASE is /, nothing to do.");
  process.exit(0);
}
const prefix = base.slice(0, -1); // "/new-site"

const walk = (dir) =>
  readdirSync(dir).flatMap((name) => {
    const p = join(dir, name);
    return statSync(p).isDirectory() ? walk(p) : [p];
  });

// A root link is "/" followed by anything but a second "/" (protocol-relative).
const attr = /(\s(?:href|src|action|poster|content)=["'])\/(?!\/)/g;
const srcset = /(\ssrcset=["'])([^"']*)/g;
const cssUrl = /url\((["']?)\/(?!\/)/g;

let changed = 0;
for (const file of walk("dist")) {
  const ext = extname(file);
  if (![".html", ".css", ".js"].includes(ext)) continue;
  let s = readFileSync(file, "utf8");
  const before = s;
  if (ext === ".html") {
    s = s.replace(attr, `$1${prefix}/`);
    s = s.replace(srcset, (_, a, v) => a + v.replace(/(^|,\s*)\/(?!\/)/g, `$1${prefix}/`));
  }
  s = s.replace(cssUrl, `url($1${prefix}/`);
  if (s !== before) { writeFileSync(file, s); changed++; }
}

// The site's .htaccess is written for the domain root; leave it out of the preview.
if (existsSync("dist/.htaccess")) rmSync("dist/.htaccess");

console.log(`Rebased ${changed} files under ${base}`);
