# Morning job

Runs every day at about 8 AM IST. Prepares today's LinkedIn post (published by Make at 7 PM IST). Nothing here is fixed: it follows the current lists, so new categories, courses, books and videos are picked up automatically.

## Steps
1. **Refresh lists.** Add anything new, never remove or reorder existing rows.
   - `categories.csv`: from `https://ravikirans.com/wp-json/wp/v2/categories?per_page=100`. A new category with at least 3 posts gets `include` = yes if it is a vendor or certification, otherwise no.
   - `items.csv`: courses from `https://ravikirans.com/ravikiran-srinivasulu-udemy-courses/`, books from `https://leanpub.com/u/ravikirans`, videos from the Make data store 262859, key `ytfeed` (the channel's latest uploads as RSS, refreshed daily; read it with the Make tool `data-store-records_list`). If a source cannot be read, skip it.
   - For every new item, check that its exam is still current (vendor exam list or web search). If the exam is retired or replaced, add the row with `skip` = yes. Never delete rows.
2. **Choose today's item** (IST). Posting days: Mon, Tue, Thu, Sat; other days stop. If `plan.csv` has a row for today, use it. Otherwise choose the type:
   - `youtube`, `course` and `book` are eligible only if they have an item with `skip` empty and no post of that type in the last 7 days. Never two non-category posts in a row.
   - Among eligible types take the one posted longest ago (never posted first). If none is eligible, use `category`.
   - Within a type take the item posted least recently (never posted first; ties: more posts first for categories). `plan.csv` is the history of what was posted.
   - When every included category and every item with `skip` empty has been posted at least once, use type `post` instead: one blog post (list from `https://ravikirans.com/wp-json/wp/v2/posts?per_page=100&page=N`) on a current exam, not yet in `plan.csv`. Rank by traffic: Make data store 262859, key `traffic` (the sheet tab Traffic, first 6 columns, refreshed daily; columns separated by ` ## `, values by ` | `, first value of each column is its header, row order is the same in every column). Take the page URL column and the clicks (else impressions) column and choose the highest not yet posted. If `traffic` is missing or empty, take the most widely taken certification instead. Check the exam is current. If no such post is left, repeat the item posted longest ago.
   - Add the row to `plan.csv`: `date,type,title,url,pdf_url,status` with `status` = `planned`.
3. **Read the page** at the row's `url` (for `youtube`, use the title and description from the feed). Use only facts on it. Never invent exam codes, prices, percentages or claims. For a category, cover only current exams and leave retired ones out of the carousel.
4. **Carousel** (not for `youtube`): write a spec JSON as in `tools/example_red_hat.json`, 5 to 7 slides plus the automatic last slide.
   - Slide 1 `cover`: one hook (surprising fact or common mistake), at most 12 words, key word in `*stars*`.
   - Middle slides: `stat`, `cards`, `steps` or `code`. Headings at most 8 words, plain language, no emojis.
   - `cta`: the row's `url` without `https://`. `label`: the category, course, book or post name. Set `cta_label` (small top label) and `cta_title` (heading) by type: category: "Free study guides" / "Free study guides for every <vendor> exam."; post: "Study guide" / "Read the full <exam> study guide."; course and book: say what the item actually is, taken from its page (for example "Practice tests" for a practice-test course, "Practice test book" for a practice-test book, "Video course" or "Beginner guide" otherwise) with a heading such as "Get <title> on Udemy." or "Get <title> on Leanpub."; never say practice tests unless the item is one. For a course or book also show what the learner gets and the real link.
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
- Render fails: still write `today.json` with empty `pdf_url` and `image_url` = `https://raw.githubusercontent.com/ravikiran-srini/ravikirans-carousels/main/brand/logo.png`, set `status` = `failed`. Make then posts the text with the logo.
- Page cannot be read: write nothing. Make emails the owner that the day was skipped.
