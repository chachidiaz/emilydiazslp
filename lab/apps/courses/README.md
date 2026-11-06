# Course app — Learn with Emily Diaz, SLP

A course-hosting site: students sign up, buy and watch lessons; Emily manages students,
access and orders in a small admin area. Built from the "Developer Handoff" doc and the
"Emily Diaz Course App" design canvas (12 screens). Plain PHP, like the other lab apps.

Open: `/apps/courses/` (sign in first). Component sheet: `/apps/courses/components.php`.

## Look

Matches the canvas's `hearth.css` exactly (Newsreader headings, Inter body, warm neutrals,
plum `#743E5A` as the only accent). This differs on purpose from the brand system in
`lab/brand/`. All colors are tokens in `assets/app.css`; don't hard-code colors in screens.

## Build order

1. ~~Tokens and shared components~~ (`assets/app.css`, `assets/app.js`, `lib/ui.php`, `components.php`)
2. ~~Authentication and roles: login, sign up, confirmation, password reset~~
3. Student area: My courses, Lesson with progress, Settings
4. Purchase: Checkout with order bump, Upsell, Order confirmation, enrollment on payment
5. Admin: Students and Student detail, then Orders
6. Undesigned states: errors, empty states, loading, mobile

## Files

| File | What it does |
| --- | --- |
| `assets/app.css` | Design tokens and every shared component class |
| `assets/app.js` | Switches, segmented controls and chip groups (ARIA state only) |
| `lib/ui.php` | Page head, student nav and footer, admin shell, badges, switches, progress, icons |
| `components.php` | Component sheet: every token and component on one page |
| `lib/config.php`, `lib/db.php` | Settings from `data/config.php`; MySQL connection and migrations |
| `lib/auth.php`, `lib/mail.php` | Sessions, sign-in, rate limits, emailed links, roles; outgoing email |
| `login.php`, `signup.php`, `welcome.php` | Screens 1, 5 and 6 |
| `forgot.php`, `reset.php` | Password reset by email (link works once, for 1 hour) |
| `magic.php` | Magic link sign-in (link works once, for 15 minutes) |
| `google.php` | Continue with Google (hidden until the Google secrets are set) |
| `logout.php` | Sign out |
| `index.php`, `admin/index.php` | Placeholders for My courses (step 3) and Students (step 5) |

## Setup (live site)

1. In Hostinger hPanel → Databases → MySQL Databases, create a database and user.
2. Add these GitHub repository secrets (Settings → Secrets and variables → Actions). The deploy
   workflow writes them into `data/config.php` on each publish; the repo never holds them.

| Secret | Value |
| --- | --- |
| `COURSES_DB_HOST` | Usually `localhost` on Hostinger |
| `COURSES_DB_NAME` | Database name, e.g. `u123456789_courses` |
| `COURSES_DB_USER` | Database user |
| `COURSES_DB_PASSWORD` | Database password |
| `COURSES_ADMIN_EMAILS` | Admin emails, comma-separated, e.g. `hello@emilydiazslp.com` |
| `COURSES_MAIL_FROM` | Sender address for sign-in emails (a mailbox on the domain) |
| `COURSES_GOOGLE_CLIENT_ID`, `COURSES_GOOGLE_CLIENT_SECRET` | Optional: turns on Continue with Google. Redirect URI: `https://lab.emilydiazslp.com/apps/courses/google.php` |

Tables are created on the first visit.

## How sign-in works

- **Passwords** need 12+ characters. 10 wrong tries for one email (or 30 from one connection) in 15 minutes pauses sign-in for that email or connection.
- **Magic links and reset links** are emailed, work once, and open a page with a button first, so email scanners can't use them up. Forgot-password and magic-link forms give the same answer whether or not an account exists.
- **Admin**: an email in `COURSES_ADMIN_EMAILS` becomes admin only after its owner proves the address (magic link, reset or Google). If someone else signed up with that address first, their password and sessions are removed at that moment. Signing in for the first time by magic link leaves the admin without a password until they set one with Forgot password.
- **Sign out everywhere**: each user has a `session_version`; raising it ends every session. A password reset does this automatically.
- **Suspended** students can't sign in and see a message telling them how to reach Emily.


## Local preview

```
php -S 127.0.0.1:8000 -t lab lab/apps/deadlines/lib/dev-router.php
```

Then open http://127.0.0.1:8000/apps/courses/. Create `data/config.php` first (never committed):

```php
<?php
return [
    'db' => ['host' => '127.0.0.1', 'name' => 'courses', 'user' => 'courses', 'pass' => '...'],
    'mail_mode' => 'log',              // writes emails to data/mail.log instead of sending
    'admin_emails' => 'you@example.com',
];
```
