#!/usr/bin/env bash
# Adds the Google Analytics (GA4) tag just before </head> in the pages you name.
# Usage: .github/scripts/add-ga-tag.sh lab/landing-pages/new-page/index.html [more pages...]
# Skips a page that already has the tag, so it's safe to run twice.
set -euo pipefail
GA_ID="G-JVTDYG6V1B"
[ $# -gt 0 ] || { echo "usage: $0 page.html [page.html ...]" >&2; exit 1; }

snippet="<!-- Google tag (gtag.js) -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id=${GA_ID}\"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '${GA_ID}');
</script>"

for f in "$@"; do
  if grep -q "$GA_ID" "$f"; then echo "already tagged: $f"; continue; fi
  if ! grep -qi '</head>' "$f"; then echo "skip (no </head>): $f" >&2; continue; fi
  SNIPPET="$snippet" perl -0pi -e 's#</head>#$ENV{SNIPPET}\n</head>#i' "$f"
  echo "added: $f"
done
