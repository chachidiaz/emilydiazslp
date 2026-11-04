# Surveys

Short surveys for lab.emilydiazslp.com. Answers are saved on Hostinger; nothing goes to a third party.

- Survey pages live in `landing-pages/` (e.g. `/landing-pages/call-times-survey/`, LP-005).
- Results: `/apps/surveys/` (log in at `/admin/` first). Shows tallies, written answers and a
  spreadsheet download.

## From a Flodesk email

Link the button to:

```
https://lab.emilydiazslp.com/landing-pages/call-times-survey/?email={{ subscriber.email }}
```

The email ties answers to a person. Someone who answers twice replaces their earlier answers.
Without it, answers are saved without a name.

## Adding a survey

1. Add it to `SV_SURVEYS` in `lib/core.php` (question keys, types, options).
2. Copy `landing-pages/call-times-survey/` and change the questions and the hidden `survey` field.
   Field names must match the keys (`name="days[]"` for a pick-any question).

## Files

| File | What it does |
| --- | --- |
| `submit.php` | Public endpoint survey pages post to |
| `index.php` | Results page (admin only) |
| `lib/` | Survey list, storage, validation. Blocked from the web |
| `data/` | `surveys.sqlite`, created on first answer. Blocked from the web, never committed, not touched by the FTP deploy |
