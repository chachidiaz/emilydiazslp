# Deadline timers

A small Deadline Funnel–style app for lab.emilydiazslp.com. You create campaigns with an
evergreen or fixed-date deadline. Each campaign gets announcement bar and inline countdown
timers. Every person is tracked by email and browser cookie, so their deadline stays the
same across your email sequence and across devices.

Dashboard: `/apps/deadlines/` · Demo funnel: `/apps/deadlines/demo/`

## How tracking works

1. Someone opens a page with a timer. They get a deadline (evergreen: now + the campaign
   length; fixed: the campaign date) and a cookie that remembers it.
2. Links in your Flodesk emails carry their email: `?email={{ subscriber.email }}`. The email
   wins over the cookie, so a phone, a laptop and a work computer all show the same deadline.
   A cookie-only visitor who later clicks an email link keeps the deadline they already had.
3. The email is removed from the address bar once the page loads, so it doesn't get shared.
4. After the deadline, the timers show a message, hide, or send people to another page.
   Use `data-deadline-show="active"` / `"expired"` to swap discount and full-price buttons.
5. **Reset** (days after the deadline): when that many days have passed, a returning person
   gets a brand-new countdown. 0 means never.

## Embedding

```html
<script src="/apps/deadlines/timer.js" defer></script>
<div data-deadline-timer="TIMER_KEY"></div>

<a data-deadline-campaign="SLUG" data-deadline-show="active" href="...">claim my discount</a>
<a data-deadline-campaign="SLUG" data-deadline-show="expired" href="...">enroll now</a>
```

Add `?dl_reset=1` to a page while logged in to restart your own timer.

## Files

| File | What it does |
| --- | --- |
| `index.php` | Dashboard: login, campaigns, timers, people, settings |
| `api.php` | Public endpoint `timer.js` calls; starts or looks up each visitor's deadline |
| `timer.js` | Embed script that draws the timers |
| `admin.css` | Dashboard styles on top of `/brand/styles.css` |
| `lib/` | PHP code (blocked from the web by `.htaccess`) |
| `data/` | The SQLite database, created on first visit. Blocked from the web, never committed |
| `demo/` | Practice funnel: opt-in, thank-you page, sales page |

## Local preview

From the repo root:

```bash
php -S 127.0.0.1:8000 -t lab lab/apps/deadlines/lib/dev-router.php
```

Then open http://127.0.0.1:8000/apps/deadlines/. The router blocks `data/` and `lib/` the way
`.htaccess` does on Hostinger. Delete `lab/apps/deadlines/data/deadlines.sqlite` to start over.

## On Hostinger

- Needs PHP 8.1 or newer with the SQLite (PDO) extension. Both are on by default.
- The first visit to `/apps/deadlines/` asks you to create the password. Do it right after the
  first publish. To start over (forgotten password, or someone else got there first), delete
  `data/deadlines.sqlite` in hPanel → File Manager. That also deletes all campaigns.
- The FTP deploy never touches `data/deadlines.sqlite`, so publishing keeps your data.
- If the dashboard shows a red warning that your tracking data can be downloaded, the server
  isn't applying `data/.htaccess`. Fix that before sending traffic.
- Back up `data/deadlines.sqlite` from File Manager now and then.
