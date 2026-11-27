# Email automation: handoff

Newsletter system for ravikirans.com. Self-hosted, near free. Last updated 27 Nov 2026.
No passwords, API keys or access keys are stored here (this repo is public). Credentials live in the plugins and consoles.

## Current status (read first)
- Sending is on Amazon SES (Sydney) via FluentSMTP. Brevo is gone: connection, DNS records and account all removed.
- SES is in the SANDBOX. Production access was requested on 26 Nov 2026 (up to 96 hours). Until approved: 200 emails a day, 1 a second, recipients must be verified (any @ravikirans.com address counts).
- Do NOT send any campaign until production access is approved AND Ravikiran says go. He said he will not send for at least 2 weeks (from 27 Nov 2026).
- Welcome-back campaign is a draft only (id 1). Footer details are now filled in.

## Goal
- Collect subscribers on ravikirans.com, store them on Hostinger, record the signup page.
- Send a newsletter to all of them. Later a weekly newsletter built and scheduled by a Claude scheduled task (not built yet).
- Carousel PDFs go in the newsletter: slide images in the email, PDF download button.

## Stack
- WordPress on Hostinger. FluentCRM 3.3.5 (free) holds contacts. Fluent Forms form id 3 is the signup form.
- Math captcha add-on "Custom Captcha Field for Fluent Forms": single digit, answer at most 9. Patched in `Bootstrap.php`; a plugin update undoes the patch.
- Pop-up: Code Snippets plugin, snippet id 10. Shows after 3 to 5 s, no confirmation email.
- Sending: FluentSMTP, one connection: Amazon SES, region ap-southeast-2, From contact@ravikirans.com, Default. Logging on, kept 14 days. Test emails delivered in about 1.2 s (to contact@ravikirans.com, which forwards to his Gmail).
- "Server-side cron" is off, so FluentCRM runs on WP cron (page visits). A real Hostinger cron is a later option.
- Footer (FluentCRM > Settings > Business Setup): business name `ravikirans.com`, address 1/79 Raju Nagar, First Main Road, Chennai 600077, Tamil Nadu, India. The business is not a registered entity; the name is the domain.

## Data in FluentCRM
- List "Newsletter" (id 1). Tags: "Website signup" (1), "Old list" (2), "Batch 01" to "Batch 15" (ids 3 to 17). Custom field "Signup page".
- 3,979 contacts imported from the old Brevo list (all subscribed, all on Newsletter, tagged "Old list", each in exactly one Batch tag of 265 or 266). The dashboard showed 3,981 active contacts on 26 Nov (new signups since).
- Junk addresses (a@b.com, aaa.pl@pl.pl and similar) are still inside. A scan was offered, not run: list them for Ravikiran, delete nothing until he approves.
- The Brevo account is closed, so the old list now exists only in FluentCRM (and a CSV export from 24 Nov that he may have on his computer).

## AWS and SES (set up 26 Nov 2026)
- Account: new AWS Builder ID plus project for contact@ravikirans.com (activated 26 Nov 2026). Free plan: only region ap-southeast-2 (Sydney) is allowed; other regions (e.g. Mumbai) need a paid plan. Account id 208978380237.
- Console sign-in: the classic root sign-in rejects the email. Use "Change to new experience", then enter contact@ravikirans.com; a code goes to his Gmail via the Hostinger forwarder.
- SES identity: ravikirans.com in Sydney, Easy DKIM (3 CNAMEs in Hostinger DNS), status Verified.
- IAM user `fluentsmtp-ses` with customer-managed policy `FluentSMTP-SES-SendOnly` (ses:SendEmail, SendRawEmail, GetSendQuota). Ravikiran created the access key himself and pasted it into FluentSMTP. Inline policies and attach-by-URL are blocked on the free plan; create a managed policy, then attach through the user's Permissions tab.
- Production access request: Marketing, https://ravikirans.com. Status "Under review" (SES Get set up page, Sydney).
- Bounce handling: the SES account-level suppression list is on by default. Optional extra: SNS topic plus FluentCRM bounce webhook (not set up).
- Costs: Essentials plan about $0.16 per 1,000 emails; 8,000 subscribers x 4 sends a month is about $5 a month. Attachments cost $0.12 per GB per copy, so never attach. Free-plan credits ($100 plus up to $100) expire 26 May 2027.
- Open question: whether the free plan blocks production access (a paid plan with a payment method might be needed). Check the SES status when approval or a rejection arrives, and tell Ravikiran before anything is charged.
- Still his steps: enable MFA on the AWS root user. His Hostinger email plan expires 2029-07-29 with auto-renew off: renew it or add a second recovery email on AWS.

## DNS (Hostinger hPanel, ravikirans.com)
- Kept: Hostinger mail DKIM CNAMEs (hostingermail-a/b/c), MX, autodiscover/autoconfig, www, google-site-verification TXT.
- SPF (TXT @): `v=spf1 include:_spf.mail.hostinger.com include:amazonses.com ~all`.
- DMARC (TXT _dmarc): `v=DMARC1; p=none;` (Brevo reporting address removed).
- Added: 3 SES DKIM CNAMEs (`*._domainkey` to `*.dkim.amazonses.com`).
- Removed 27 Nov 2026: brevo-code TXT, brevo1/brevo2 DKIM CNAMEs.
- Add records through the DNS page form (Type CNAME, Name, Target, Add Record). hPanel signs out now and then; Ravikiran signs in (2FA is on, passwords are his to type).

## Brevo (closed)
- 26 Nov: FluentSMTP connection removed (Ravikiran replaced it with SES). 27 Nov: DNS records removed; account closed via Settings > General > "Definitively close your account", with a code emailed to his Gmail that he entered himself. Nothing left to do.

## Pending
- Wait for SES production access. Then: send a one-contact test through FluentCRM (use a test tag), then Batch 01 (266), check bounces and complaints in SES, then the rest.
- Welcome-back DRAFT: campaign id 1 "Welcome back - Batch 01", status draft, subject "Still want exam-prep tips?", preheader "A short weekly email on cloud and data certification prep", from Ravikiran Srinivasulu <contact@ravikirans.com>, list Newsletter + tag Batch 01 (266 confirmed in the UI). Short body with a button to ravikirans.com. Ravikiran has not reviewed it yet: show it. Do NOT schedule or send without his go-ahead.
- Clean junk addresses before the first send (SES reviews at about 5% hard bounces, may pause at 10%).
- Build campaigns for Batch 02 to 15 by cloning campaign 1 with a different tag (not built). All at once is possible after approval; daily batches are no longer needed.
- After each send: read bounces and complaints, mark hard bounces "Bounced", remove unsubscribes. Later a scheduled task.
- Weekly Claude scheduled task: not built. Optional: real server cron; WordPress updates (8 pending, a PHP update is also recommended).
- Schedule endpoint (POST /campaigns/{id}/schedule) is untested on purpose: it would send. Test only with a one-contact test tag.

## Carousel PDFs in emails
- Do not attach PDFs. Host the PDF on ravikirans.com and link it with a "Download the full PDF" button.
- Slide images: host on GitHub Pages (public repo, each image under 100 KB), not raw.githubusercontent.com. About 32 GB a month at 8,000 subscribers (Pages soft limit 100 GB).
- Not set up yet. Idea: the morning carousel job also saves slide images to the Pages repo.

## Working notes
- FluentCRM REST base: https://ravikirans.com/wp-json/fluent-crm/v2 (nonce `window.fcAdmin.rest.nonce` from a FluentCRM admin page, called with page.evaluate).
  - Bulk tag: POST /subscribers/do-bulk-action with `{action_name:"add_to_tags", subscriber_ids:[...], action_options:[tagId]}`.
  - Create contact: POST /subscribers with `{email, status:"subscribed", lists:[1], tags:[2]}`.
  - Business settings: GET `/setting?settings_keys[]=business_settings`; the UI page is `#/settings` (Business Setup).
  - Campaigns: `POST /campaigns {title}` creates a draft; `PUT /campaigns/{id}` with `{title, email_subject, email_pre_header, email_body, design_template:"simple", settings}` fills it. Recipients: `settings.subscribers:[{list:"1",tag:"3"}]`, `sending_filter:"list_tag"`; custom sender in `settings.mailer_settings` with `is_custom:"yes"`. Merge into the settings the POST returns. The POST response is not wrapped, so list `/campaigns` to get the id.
  - `POST /campaigns/estimated-contacts` returns 0; not trustworthy. The campaign Recipients step (`#/email/campaigns/{id}?step=2`) shows the real count.
  - Setting `location.hash` to an unknown FluentCRM route bounces to the dashboard; `#/settings` and `#/email/campaigns` work, others via the sidebar links.
- Built-in browser (browser_repl): WordPress admin is signed in there. Pages bring big accessibility dumps; keep scripts quiet. It cannot type passwords or enter secrets. `tabs.create(url)` takes a string.
- Ravikiran prefers short answers in simple language and automation over manual steps. His first name is one word: "Ravikiran".
- Never send to the list without his approval. Confirm before any permanent deletion.
