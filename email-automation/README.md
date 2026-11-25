# Email automation: handoff

Newsletter system for ravikirans.com. Free, self-hosted. Last updated 25 Nov 2026.
No passwords or API keys are stored here (this repo is public). Credentials live in the plugins on the site.

## Goal
- Collect subscribers on ravikirans.com, store them on Hostinger, record the page they signed up on.
- Send a newsletter to all of them for free, now by daily batches, later all at once.
- Weekly newsletter built and scheduled by a Claude scheduled task (not built yet).
- Carousel PDFs go in the newsletter: slide images in the email, PDF download button.

## Stack (all done and working)
- WordPress on Hostinger. FluentCRM 3.3.5 (free) holds contacts. Fluent Forms form id 3 is the signup form.
- Math captcha: add-on "Custom Captcha Field for Fluent Forms", single digit, answer at most 9. It was patched in `Bootstrap.php`; a plugin update will undo the patch.
- Pop-up: Code Snippets plugin, snippet id 10. Shows after 3 to 5 s, no confirmation email.
- Sending: FluentSMTP, connection type Brevo (Sendinblue) API. Brevo free = 300 emails/day. Sender: contact@ravikirans.com. Domain ravikirans.com is authenticated in Brevo (DKIM, brevo-code, DMARC with rua). Test email delivered.
- Brevo API IP blocking is off (it blocked the Hostinger server's changing IPv6). SMTP key IP blocking is off.
- FluentSMTP has no daily cap setting. Weekly Email Summary is on.
- "Server-side cron" is off, so FluentCRM runs on WP cron (page visits). A real Hostinger cron is a later option.

## Data in FluentCRM
- List "Newsletter" (id 1). Tags: "Website signup" (1), "Old list" (2), "Batch 01" to "Batch 15" (ids 3 to 17).
- Custom field "Signup page".
- 3,979 contacts imported from the old Brevo list, all subscribed, all on Newsletter, all tagged "Old list", each in exactly one Batch tag (266 or 265 per batch).
- Junk addresses are still inside (a@b.com, aaa.pl@pl.pl and similar). Remove after the first send.

## Plan agreed
1. Welcome-back email to the old list, one batch per day (Batch 01 to 15), about 280 per day so site emails still fit under 300.
2. After each batch: read Brevo delivery logs, mark hard bounces as "Bounced", remove unsubscribes, resend failures. To be a daily scheduled task.
3. Review who opens and clicks. Keep the engaged.
4. When the list reaches about 7,000 to 8,000: move to Amazon SES and send to everyone at once (no batches).

## Pending
- Postal address for the email footer (needed by law). Not given yet.
- Welcome-back draft (subject "Still want exam-prep tips?", sender Ravikiran Srinivasulu, button to ravikirans.com, unsubscribe link, address footer) is only drafted in chat. Build it in FluentCRM as a DRAFT for Batch 01 and show it to Ravikiran. Do NOT send without his go-ahead.
- Build the daily rotation (one campaign per Batch tag per day).
- Check that the free FluentCRM REST API can create and schedule campaigns, then build the weekly Claude task.
- Optional: real server cron, WordPress update (7.1.5 available).

## Amazon SES (researched 25 Nov 2026, for later)
- New accounts: Essentials plan, $0.16 per 1,000 emails. Old $0.10 rate is gone for new accounts.
- 8,000 subscribers x 4 emails a month = 32,000 emails = about $5 a month.
- Attachments cost extra: $0.12 per GB, per copy sent. A 2 MB PDF to 8,000 people, 4 times a month = about $8 a month. So do not attach.
- New AWS accounts get up to $200 credit for 6 months. Unknown if unused credit carries over; assume it expires.
- FluentSMTP has a built-in SES connection, so switching keeps FluentCRM and the lists.
- Needs production-access approval from AWS before sending at volume.
- Free Brevo cannot cover 8,000 x 4 (300/day is about 9,000 a month).

## Carousel PDFs in emails
- Do not attach PDFs.
- PDF: host on ravikirans.com, link with a "Download the full PDF" button.
- Slide images: host on GitHub Pages (not raw.githubusercontent.com links). Repo must be public. Keep each image under 100 KB. Pages soft limit is 100 GB/month bandwidth; this use is about 32 GB/month at 8,000 subscribers.
- Not set up yet. Idea: the morning carousel job also saves slide images to the Pages repo.

## Working notes
- FluentCRM REST base: https://ravikirans.com/wp-json/fluent-crm/v2 (nonce `window.fcAdmin.rest.nonce` from a FluentCRM admin page).
  - Bulk tag: POST /subscribers/do-bulk-action with `{action_name:"add_to_tags", subscriber_ids:[...], action_options:[tagId]}`.
  - Create contact: POST /subscribers with `{email, status:"subscribed", lists:[1], tags:[2]}`.
  - `/subscribers/sync-segments` did not work for bulk tagging.
- Built-in browser REPL: `tab.click(x,y)` uses full-resolution coordinates; no file upload there.
- Ravikiran prefers short answers in simple language, and automation over manual steps. His first name is one word: "Ravikiran".
- Never send to the list without his approval.
