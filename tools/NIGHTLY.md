# Morning job

Runs every day at about 8 AM IST. Prepares today's LinkedIn post (published by Make at 7 PM IST). Nothing here is fixed: it follows the current lists, so new categories, courses, books and videos are picked up automatically.

## Steps
1. **Refresh lists.** Add anything new, never remove or reorder existing rows.
   - `categories.csv`: from `https://ravikirans.com/wp-json/wp/v2/categories?per_page=100`. A new category with at least 3 posts gets `include` = yes if it is a vendor or certification, otherwise no.
   - `items.csv`: courses from `https://ravikirans.com/ravikiran-srinivasulu-udemy-courses/`, books from `https://leanpub.com/u/ravikirans`. Videos are added only by the owner.
2. **Choose today's item** (IST). If `plan.csv` has a row for today, use it. Otherwise: Mon and Tue = `category`; Thu = `youtube`; Sat = `course` or `book`, whichever was posted less recently. Other days: stop.
   - Within a type, take the item posted least recently (never posted first; ties: more posts first for categories). `plan.csv` is the history of what was posted.
   - If a type has no items, use `category`.
   - Add the row to `plan.csv`: `date,type,title,url,pdf_url,status` with `status` = `planned`.
3. **Read the page** at the row's `url` (for `youtube`, the video page). Use only facts on it. Never invent exam codes, prices, percentages or claims.
4. **Carousel** (not for `youtube`): write a spec JSON as in `tools/example_red_hat.json`, 5 to 7 slides plus the automatic last slide.
   - Slide 1 `cover`: one hook (surprising fact or common mistake), at most 12 words, key word in `*stars*`.
   - Middle slides: `stat`, `cards`, `steps` or `code`. Headings at most 8 words, plain language, no emojis.
   - `cta`: the row's `url` without `https://`. `label`: the category, course or book name. For a course or book show what the learner gets, and the real link.
   - Render: `python3 tools/render.py spec.json carousels/<date>-<slug>.pdf --png-dir /tmp/png`. Look at the PNGs, fix overflow or overlap, render again.
   - Save slide 1 as `carousels/<date>-<slug>-cover.png`.
5. **Write `today.json`** in the repo root, with keys `date, type, title, url, pdf_url, image_url, post, comment, facebook, x, page_text`.
   - `pdf_url` / `image_url`: raw GitHub URLs of the PDF and cover PNG (`https://raw.githubusercontent.com/ravikiran-srini/ravikirans-carousels/main/...`). For `youtube`, `pdf_url` is empty and `image_url` is `https://img.youtube.com/vi/<id>/maxresdefault.jpg`.
   - `post` (LinkedIn): 60 to 90 words, 2 to 4 short paragraphs. First line a specific claim from the page, under 100 characters. No URL, at most 2 emojis, one genuine question to the reader, no engagement bait. Then "Study guides link in the comments." (course or book: "Link in the comments.", video: "Video link in the comments."). End with 2 hashtags.
   - `comment`: at most 40 words, one practical tip, then a blank line and the link with a short label ("Free study guides: ", "Practice tests: ", "Watch the full video: ").
   - `facebook`: 50 to 80 words, conversational, URL on its own line, then 3 hashtags.
   - `x`: under 220 characters including the URL: a hook line, the URL, 3 hashtags.
   - `page_text`: one line and the URL, for the company Page share.
   - Write in first person as the site owner. Never use double quotes or backslashes in any text.
6. **Publish.** Set `pdf_url` and `status` = `ready` in `plan.csv`. Commit PDF, cover PNG, `plan.csv` and `today.json` as Ravikiran with no Claude co-author or session lines. Run first: `git config user.name "Ravikiran Srinivasulu" && git config user.email "39718717+ravikiran-srini@users.noreply.github.com"`. Plain message such as `Add <date> <slug> post`. Push: `git -c push.negotiate=true push origin HEAD:main`.

## If something fails
- Render fails: still write `today.json` with empty `pdf_url` and `image_url`, set `status` = `failed`. Make then posts text only.
- Page cannot be read: write nothing. Make emails the owner that the day was skipped.
