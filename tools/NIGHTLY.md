# Nightly carousel job

Runs every morning at about 8 AM IST. Builds the carousel PDF for today's LinkedIn post (published at 7 PM IST).

## Steps
1. Read `plan.csv`. Pick the row whose `date` is today (IST) and `status` is `planned`. If none, stop.
2. If `type` is `youtube`, set `status` to `n/a` and stop (video posts have no carousel).
3. Fetch the row's `url` and read the page. Use only facts on that page. Never invent exam codes, prices, percentages or claims.
4. Write a spec JSON as in `tools/example_red_hat.json`: 5 to 7 slides plus the automatic final slide.
   - Slide 1 `cover`: one hook (a surprising fact or a common mistake), at most 12 words. Wrap the key word in `*stars*` to show it in red.
   - Middle slides: use `stat`, `cards`, `steps`, `code`. Each heading at most 8 words. Plain language, no emojis.
   - `cta`: the row's `url` without `https://`. `label`: the category, course or book name.
   - `type` course or book: show what the learner gets (topics, format), and the real link. No made-up reviews or numbers.
5. Render: `python3 tools/render.py spec.json carousels/<date>-<slug>.pdf --png-dir /tmp/png`. Look at the PNGs (Read tool). Fix any text overflow or overlap and render again.
6. Set `pdf_url` in `plan.csv` to `https://raw.githubusercontent.com/ravikiran-srini/ravikirans-carousels/main/carousels/<date>-<slug>.pdf` and `status` to `ready`.
7. Commit the PDF and `plan.csv` as Ravikiran, with no Claude co-author or session lines in the message. Run first: `git config user.name "Ravikiran Srinivasulu" && git config user.email "39718717+ravikiran-srini@users.noreply.github.com"`. Use a plain commit message such as `Add <date> <slug> carousel`. Then push to `main`: `git -c push.negotiate=true push origin HEAD:main`.

## If anything fails
Set `status` to `failed` for that row, push `plan.csv` only, and finish. The 7 PM post then goes out as text only.
