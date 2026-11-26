# Email automation: handoff

Newsletter system for ravikirans.com. Free, self-hosted. Last updated 25 Nov 2026 (SES decided).
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
- Sending (until the SES switch below): FluentSMTP, connection type Brevo (Sendinblue) API. Brevo free = 300 emails/day. Sender: contact@ravikirans.com. Domain ravikirans.com is authenticated in Brevo (DKIM, brevo-code, DMARC with rua). Test email delivered.
- Brevo API IP blocking is off (it blocked the Hostinger server's changing IPv6). SMTP key IP blocking is off.
- FluentSMTP has no daily cap setting. Weekly Email Summary is on.
- "Server-side cron" is off, so FluentCRM runs on WP cron (page visits). A real Hostinger cron is a later option.

## Data in FluentCRM
- List "Newsletter" (id 1). Tags: "Website signup" (1), "Old list" (2), "Batch 01" to "Batch 15" (ids 3 to 17).
- Custom field "Signup page".
- 3,979 contacts imported from the old Brevo list, all subscribed, all on Newsletter, all tagged "Old list", each in exactly one Batch tag (266 or 265 per batch).
- Junk addresses are still inside (a@b.com, aaa.pl@pl.pl and similar). Remove after the first send.

## Plan agreed (revised 26 Nov 2026: Brevo replaced by Amazon SES)
1. Send the welcome-back email through Amazon SES, not 300/day Brevo batches. Send Batch 01 (266) first, check the SES bounce rate, then send the rest at once. Clean junk addresses before sending: AWS reviews at about 5% hard bounces and may pause at 10%.
2. After each send: read bounces and complaints, mark hard bounces "Bounced", remove unsubscribes. Later a scheduled task.
3. Review who opens and clicks. Keep the engaged.

## SES migration (decided 26 Nov 2026)
Brevo stays the working sender until an SES test email is delivered. Then remove it.
- AWS account: Ravikiran used one 7 to 8 years ago, so he is not eligible for the $200 credits (AWS FAQ). Pay as you go, about $3 to $5 a month at 8,000 subscribers x 4 sends. Try "forgot password" on the old account first; otherwise create a new one with contact@ravikirans.com, email and password, then enable root MFA. Status: not signed in to AWS in the built-in browser yet.
- He must do himself: create or sign in to the AWS account; paste the SES access key and secret into FluentSMTP (Claude cannot enter keys or passwords).
- Claude does: verify ravikirans.com in SES (DKIM records in Hostinger DNS), bounce and complaint handling (SNS), production-access request (new accounts start in the sandbox, verified recipients only), add the SES connection in FluentSMTP, send a test, make SES the default, delete the Brevo connection in FluentSMTP.
- Ask before: deleting the Brevo account or its DNS records (brevo-code, DKIM). Permanent.
- Hostinger: enable 2FA on the hPanel account and optionally the mailbox (Webmail > All Settings > Account).

## Pending
- Footer details (needed by law): FluentCRM > Settings > Business Setup has empty Business Name and Full business address. The draft footer uses these. Ask Ravikiran for the postal address.
- Welcome-back DRAFT exists: campaign id 1 "Welcome back - Batch 01", status draft, subject "Still want exam-prep tips?", list Newsletter + tag Batch 01 (266 contacts confirmed in the UI), from Ravikiran Srinivasulu <contact@ravikirans.com>. Body is short, with a button to ravikirans.com. Show it to Ravikiran. Do NOT schedule or send without his go-ahead.
- Daily rotation (one campaign per Batch tag, 02 to 15): clone campaign 1 via the API with a different tag. Not built yet.
- Schedule endpoint (POST /campaigns/{id}/schedule) is untested on purpose: it would send. Test only with a one-contact test tag.
- Weekly Claude scheduled task: not built.
- Optional: real server cron, WordPress update (8 updates pending).

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
  - Campaigns (verified 25 Nov): `POST /campaigns {title}` creates a draft; then `PUT /campaigns/{id}` with `{title, email_subject, email_pre_header, email_body, design_template:"simple", settings}` fills it. Recipients go in `settings.subscribers:[{list:"1",tag:"3"}]`, `sending_filter:"list_tag"`; custom sender in `settings.mailer_settings` with `is_custom:"yes"`. Merge into the settings returned by the POST instead of replacing them. `GET /campaigns/{id}` returns `{campaign:{...}}`; the POST response is not wrapped, so list `/campaigns` to get the id.
  - `POST /campaigns/estimated-contacts` returned 0 for a valid tag. Do not trust it; the campaign Recipients step in the UI shows the real count (`#/email/campaigns/{id}?step=2`).
  - Setting `location.hash` to an unknown FluentCRM route bounces to the dashboard. Use the sidebar links.
- Built-in browser REPL: `tab.click(x,y)` uses full-resolution coordinates; no file upload there.
- Ravikiran prefers short answers in simple language, and automation over manual steps. His first name is one word: "Ravikiran".
- Never send to the list without his approval.
