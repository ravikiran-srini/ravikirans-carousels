# Website, Revision Library and carousel jobs: handoff

Site work for ravikirans.com (WordPress on Hostinger, Olsen Light theme). Last updated 27 Nov 2026.
Newsletter, SES and the email list are a separate chat: see `../email-automation/README.md`. Do not work on SES here.
No passwords, keys or tokens in this repo (it is public).

## Standing rules
- His first name is one word: "Ravikiran". Short answers, simple language. He dislikes manual steps: automate routine fixes and cleanup.
- Commit as Ravikiran, no Claude co-author or session lines: `git config user.name "Ravikiran Srinivasulu"; git config user.email "39718717+ravikiran-srini@users.noreply.github.com"`. Push: `git -c push.negotiate=true push origin HEAD:main` (the repo must be attached to the session with push access).
- Never publish a page, send mail, delete anything permanent or change settings without telling him first. If a tool call is denied, do not work around it.
- Skills and job instructions stay generic and short (no one-night special cases).
- Generated drafts first, show a preview, publish only after his explicit OK.
- Two chats: this one is website, carousel/PDF and Revision Library. Email and SES are in the other.

## Jobs and their state (27 Nov 2026)
- Morning build: scheduled task trig_01XPbfZFBzHA5CavBfddNXKT (07:37 IST, Opus 5.5). DISABLED for the week of 23 to 29 Nov at his request. Follows `tools/NIGHTLY.md`; writes `today.json`, `plan.csv`, `carousels/*`, and now also appends to `library.json`.
- Make 8659415: daily 19:00 IST post (Mon, Tue, Thu, Sat) from `today.json`. Paused for the week.
- Make 8753316: saves the YouTube feed and traffic data to data store 262859. Now WEEKLY, Mondays 06:30 IST. Paused for the week.
- Auto-resume: scheduled task trig_01Cu36Lbv4PFLpscG6Q5cV5E fires Sun 29 Nov 2026 20:00 IST; it re-enables the build task and activates Make 8659415 and 8753316, confirms all three, and tells him in one line. Check it ran; if not, resume them by hand.
- Old reminder trig_01D73gDKiZbYwBbjb9oj9vLk (the 24 Nov 08:45 check) could not be disabled (denied). It may fire into an old chat; ignore it.
- Make 8640867: delete only after about a week and on his explicit say-so. Make 8660956: delete only on his explicit "delete".

## Revision Library (built 26 to 27 Nov 2026)
Design he approved:
1. A page grouped by category, newest first; each entry has cover, title, date, Download button. Filled automatically by the morning job from `library.json` (he edits nothing).
2. A "Quick Revision" box at the end of every study guide linking to that category on the library page. Applies to existing and new guides by itself.
3. A small public page per PDF (summary, cover, download button, related guide, his Udemy course).
4. Email gate: the download link is EMAILED (no attachment, no instant download). Clicking it confirms and subscribes the person and sets a cookie so the gate is skipped afterwards. Existing subscribers are recognised through personal links in newsletter emails (no re-entering email). A rare "Already subscribed? Enter your email" fallback is fine. No gate beyond email. Free on LinkedIn too; that is fine.
5. Later: a one-page plan for the Android/iOS course app (see below).

Done:
- Code Snippets snippet id 11 "Revision Library (page + Quick Revision box)", active, run everywhere. Source copy: `website/snippets/revision-library.php` (paste there if the snippet is ever lost). It adds the shortcode `[revision_library]` and a `the_content` filter for the box. It reads `library.json` from raw.githubusercontent.com (cached 1 hour in the transient `rl_library_items`).
- The box shows only when the guide's category has at least one PDF in `library.json`, so there are no dead links while the library is empty.
- WordPress page "Revision Library", id 50437, slug `revision-library`, status DRAFT, contains the shortcode. Empty state reads "Quick revision PDFs are on the way". Preview: https://ravikirans.com/?page_id=50437&preview=true (signed in).
- `library.json` created empty: `{"items": []}`. `tools/NIGHTLY.md` step 6 now appends an item for each `post` and `category` PDF.
- Tested with stubs (grouping, sort, box); not yet tested live with a real entry.

`library.json` item format: `{"slug":"<date>-<slug>","title","category","category_slug","date","cover","pdf","summary","guide_url"}` (cover and pdf are raw GitHub URLs; category_slug comes from `categories.csv` and must equal the WordPress category slug).

To do, in order:
1. Per-PDF page: public URL `/revision-library/<slug>/` (the library cards already link there). Needs a rewrite rule or a template in a snippet that renders from `library.json`; show cover, summary, Download button, link to `guide_url`, Udemy link.
2. Email gate and tokenized links: Download button asks for an email (or recognises a cookie or a personal link); FluentCRM API creates or finds the contact, sends the link by email, link click confirms and subscribes, sets cookie, then serves the PDF. Protect direct PDF URLs (PDFs are public in the GitHub repo today; decide whether to host them on the site or accept that raw links exist). Use FluentCRM free features only (Pro features are unavailable).
3. Add personal links to newsletter emails so old subscribers pass the gate automatically (FluentCRM smartcode, coordinate with the email chat).
4. "Signup source" (see below).
5. Test with one sample entry in `library.json` and a test contact; remove the test data afterwards; show him; publish the page only after his OK. Then tell him the first real PDF will fill the page and the boxes.
6. App plan, one page.

## Signup source (undecided: now, or with the library)
Popup form is Fluent Forms form 3 with hidden field `signup_page` = `{embed_post.permalink}` mapped to the FluentCRM custom field "Signup page". He wants ONE column called "Signup source": the page URL for ordinary popup signups, and the vendor or certification category for signups triggered by a PDF download. My recommendation: first source only, plus a tag "Downloaded: <vendor>" per later download. He has not chosen the timing.

## Popup and confirmation email
- Popup = Code Snippets snippet id 10, form 3. On 26 Nov 2026 the FluentCRM feed on form 3 got double opt-in turned on, the form message was changed to "Almost done! Check your inbox...", and the double opt-in email and thank-you text were reworded (FluentCRM > Settings > Email Settings > Double Opt-in Settings). The math captcha plugin is no longer needed; he asked about removing it (add-on "Custom Captcha Field for Fluent Forms"); check whether that was done.
- Delivery of confirmation emails depends on SES, which is handled in the email chat.

## Site facts
- Plugins: FluentCRM 3.3.5 free, Fluent Forms 6.2.17 free, FluentSMTP 7.1.5, Code Snippets, Yoast SEO, Pretty Links, Wordfence, Akismet, Ninja Forms, Reading Progress Bar, WPFront Scroll Top, Advanced Editor Tools, Fixed Widget. WordPress and several plugins have updates pending.
- Study guides are normal posts (Gutenberg, `.entry-content`, category plus tags). Example guide id 50370. Structure: entry-meta-top, title, entry-meta-bottom, featured image, entry-content, tags, utils, related, comments.
- Logo change done on the site. The old logo in the browser tab is only browser cache: the site serves the new icon (`cropped-site-icon-512-*.png`); a hard refresh or another browser shows it.
- Pretty Links: /linkedIn points to an expired affiliate link (left alone). Facebook, Twitter and YouTube links not yet checked.
- Open, low priority: Gravatar and sidebar photo (he does them), AdSense propagation, LinkedIn groups reposting (skipped).

## Course app (not started)
He wants a simple Android and iOS app with three sections: video courses people can buy in the app, timed test questions so users can judge their knowledge, and a library of the category-by-category carousel PDFs, each linked to its course (course-specific PDFs later). He may connect a Teachable-style account to the website later. The Revision Library and `library.json` are meant to be reusable as the app's PDF source. Write a one-page plan after the library is done.

## Working notes
- Built-in browser (browser_repl) is signed in to WordPress admin. Set a wide viewport (`setViewportSize({width:1300,height:1000})`) or admin content hides. Reuse tab variables; declaring the same name twice fails.
- Create a page through the REST API from an admin page: `fetch('/wp-json/wp/v2/pages', {method:'POST', headers:{'X-WP-Nonce': wpApiSettings.nonce, ...}})`.
- Code Snippets editor: `document.querySelector('.CodeMirror').CodeMirror.setValue(code)`, then "Save and Activate".
- A PHP snippet is linted with `php -l` before pasting; Code Snippets has safe mode if one breaks the site.
