# Purchase path tracker

A small, self-hosted tracker for emilydiazslp.com, lab.emilydiazslp.com and campus.emilydiazslp.com.
It shows every page's traffic and, once later phases are built, each visitor's path to a Campus purchase.

Dashboard: `/apps/tracker/` (uses the lab's one admin login, the same as `/admin/` and the deadline timers app)

## Build phases

| Phase | What | Status |
| --- | --- | --- |
| 1. Pages | Pixel, collector, `events` / `visitors` / `pages` tables, Overview, Pages and Setup screens | built |
| 2. Goals and funnels | Goals set up in the dashboard (page visited, action taken), Goals and Funnels screens | next |
| 3. Purchase path | `vid` hidden field and paid-order webhook in Campus (`chachidiaz/campus` repo), `orders` table, Flodesk opt-in email matching, Journeys and Sources screens | |
| 4. Polish | Period comparison, consent banner, daily rollups, weekly email summary | |

## Installing the pixel

Paste into the `<head>` of every page on all three sites:

```html
<script defer src="https://lab.emilydiazslp.com/apps/tracker/p.js"></script>
```

- **Goal clicks:** `data-goal="checkout_click"` on a button or link, or on a `<form>` to count its submits.
- **Manual goals:** `tracker.goal('watched_replay')`.
- **Checkout links:** the pixel adds `?vid=<visitor id>` to links to `campus.emilydiazslp.com/checkout…`.

## How it works

1. `p.js` sets a random visitor ID in the `pt_vid` cookie on `.emilydiazslp.com`, so all three sites
   share it, with a localStorage backup. A session (`pt_sid`) ends after 30 minutes without activity.
2. On load it sends a `pageview` to `collect.php`. Each time the tab is hidden it sends the visible
   seconds and scroll depth so far, and the collector keeps the largest value on that pageview's row.
3. Events go out with `sendBeacon` as `text/plain`, so the other subdomains need no CORS preflight.
4. The collector drops bots (user-agent list, plus `navigator.webdriver` in the pixel), events from
   hosts that aren't ours, and anything malformed. It always answers 204.
5. Query strings are never stored, so emails in Flodesk links (`?email=…`) never reach the database.
   UTM tags are pulled out into their own columns first.
6. The Setup tab's "exclude this browser" button sets a `pt_ignore` cookie on `.emilydiazslp.com`.

## Files

| File | What it does |
| --- | --- |
| `p.js` | The pixel |
| `collect.php` | Public endpoint the pixel sends to |
| `index.php` | Dashboard: Overview, Pages, Setup |
| `tracker.css`, `tracker-admin.js` | Dashboard styles (on top of `/brand/styles.css` and `/apps/deadlines/admin.css`) and chart/table behavior |
| `lib/core.php` | Database, intake and report queries (blocked from the web by `.htaccess`) |
| `data/` | `tracker.sqlite`, created on first event. Blocked from the web, never committed |

## Local preview

From the repo root:

```bash
php -S 127.0.0.1:8000 -t lab lab/apps/deadlines/lib/dev-router.php
```

Events from `localhost` / `127.0.0.1` are saved under the site `dev`, which the dashboard leaves out.

## Known limits

- Safari keeps script-set cookies for 7 days, so a Safari visitor who comes back after a week gets a
  new ID unless the localStorage backup survives. Phase 3's email matching covers buyers.
- Country isn't filled in yet (no IP lookup on Hostinger). The IP address is never stored.
- SQLite is fine to roughly a million events a month. Past that, move `events` to Hostinger MySQL.
