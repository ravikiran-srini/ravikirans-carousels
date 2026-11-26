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

## SES migration (status 26 Nov 2026, 23:30 IST)
Done:
- New AWS account (Builder ID + project, activated 26 Nov 2026) for contact@ravikirans.com. Free plan: only region ap-southeast-2 (Sydney) is allowed; other regions need a paid plan. Console sign-in: choose "Change to new experience" on the classic sign-in page, then enter the email (code goes to his Gmail via the Hostinger forwarder).
- SES domain identity ravikirans.com in Sydney: Easy DKIM, 3 CNAMEs added in Hostinger DNS, status Verified.
- IAM user fluentsmtp-ses (AmazonSESFullAccess). He created the access key himself and pasted it into FluentSMTP.
- FluentSMTP now has one connection: Amazon SES, ap-southeast-2, From contact@ravikirans.com, Default. Brevo connection is gone. Test email delivered via SES in 1.25 s and arrived in Gmail.
- Production access requested (Marketing, https://ravikirans.com). Status Under review, up to 96 hours. Until approved: sandbox, 200 emails a day, 1 a second, recipients must be verified (any address @ravikirans.com counts).
Remaining:
- Do NOT send campaigns until production access is approved.
- Bounce and complaint handling: SES account-level suppression list is on by default. Optional extra: SNS topic plus FluentCRM bounce webhook.
- Optional: tighten the IAM policy to ses:SendRawEmail only; add include:amazonses.com to the SPF TXT record.
- Ask before deleting the Brevo account or its DNS records (brevo-code TXT, brevo1/brevo2 DKIM CNAMEs, _dmarc rua to brevo). Permanent.
- AWS root MFA still to enable (his step). The mailbox plan (Hostinger free business email) expires 2029-07-29, auto-renew off: renew it or add a second recovery email on AWS.
- Hostinger hPanel and Webmail 2FA are on.

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
