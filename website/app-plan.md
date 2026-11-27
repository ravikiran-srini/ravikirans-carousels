# Course app: one-page plan (27 Nov 2026)

Goal: simple Android and iOS app with three tabs: Courses (video), Tests (timed questions), Library (the Revision Library PDFs, each linked to its course).

## Path (spend only when the last step proves itself)
1. Library first: `library.json` already holds every PDF (category, cover, summary, guide link). The app reads the same file.
2. Test demand with Teachable: its student app is free on all plans (iOS and Android, offline), but it is Teachable's own app, not his brand. Check plan limits and fees first.
3. Own app only if step 2 shows demand: one codebase (Expo/React Native) built with Claude Code, tried on his phone through Expo Go before paying for any store account.

## Contents
- Library: reads `library.json`, opens PDFs, button to the matching course.
- Tests: per-exam question sets (for example 20 questions, 30 minutes), score, explained wrong answers; one JSON file per exam from the practice-question generator, stored on the phone, no server.
- Courses: list and play screen; videos stay on a video host.

## Money and store rules (checked 27 Nov 2026; re-check before launch)
- Selling courses inside the app is digital goods: Apple In-App Purchase is required. Apple 30%, or 15% in the Small Business Program (under US$1M a year; enrol, not automatic).
- Google Play changed fees mid-2026: roughly 10 to 15% on the first US$1M plus a 5% billing fee in the US, UK and EU.
- RevenueCat (purchases on both stores): free up to US$2,500 monthly tracked revenue, then 1%. Only needed if selling in the app.
- Rules on linking to the website to buy differ by country and change often.

## Open decisions (his)
1. Sell inside the app, or keep it free and sell on Udemy/Teachable/the site?
2. Video host for the Courses tab.
3. Recommendation: v1 free with Library + Tests, add selling later. (He has not answered; a comment on this is in the readable copy, a Docs doc "Course app: one-page plan".)

## Next steps
1. Publish the Revision Library (after his OK).
2. Fix one JSON format for test questions; export one exam as a sample.
3. Library + Tests prototype on his phone via Expo Go.
4. Decide on selling, then open store accounts.
