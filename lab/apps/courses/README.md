# Course app — Learn with Emily Diaz, SLP

A course-hosting site: students sign up, buy and watch lessons; Emily manages students,
access and orders in a small admin area. Built from the "Developer Handoff" doc and the
"Emily Diaz Course App" design canvas (12 screens). Plain PHP, like the other lab apps.

Open: `/apps/courses/` (shows the component sheet until My courses is built).

## Look

Matches the canvas's `hearth.css` exactly (Newsreader headings, Inter body, warm neutrals,
plum `#743E5A` as the only accent). This differs on purpose from the brand system in
`lab/brand/`. All colors are tokens in `assets/app.css`; don't hard-code colors in screens.

## Build order

1. ~~Tokens and shared components~~ (`assets/app.css`, `assets/app.js`, `lib/ui.php`, `components.php`)
2. Authentication and roles: login, sign up, confirmation, password reset
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

## Local preview

```
php -S 127.0.0.1:8000 -t lab lab/apps/deadlines/lib/dev-router.php
```

Then open http://127.0.0.1:8000/apps/courses/.
