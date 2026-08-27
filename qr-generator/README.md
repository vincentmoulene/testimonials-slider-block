# QR & barcode generator — free, multilingual, SEO-first

A Symfony 8 application that generates QR codes and barcodes for free, in five
languages, with no account and no expiry date. It is designed to be left online
permanently, funded by advertising, and to build a mailing list from the people
who download a code.

```
/                       → redirects to the visitor's best-matching language
/{locale}               → home page: the generic QR generator (one free-form field)
/{locale}/tools         → all generators (localised path and slug)
/{locale}/tools/{slug}  → one landing page per generator, per language
/{locale}/blog          → file-based blog (Markdown)
/q/{png|svg|webp}       → stateless image endpoint (download + no-JS fallback)
/api/generate           → JSON endpoint used by the live preview
/api/lead               → JSON endpoint that records the email of a downloader
/sitemap.xml            → every URL, with hreflang alternates
```

## What it does

- **A generic generator on the home page**: one field, anything inside — a link,
  a text, a reference. A bare domain is turned into a link, everything else is
  encoded verbatim.
- **19 specialised generators**, each with its own landing page in each language:
  - *Links & text* — URL, plain text
  - *Contact* — vCard, email, SMS, phone, WhatsApp, Telegram, Signal
  - *Places* — geolocation, driving directions (Google Maps), calendar event
  - *Business* — Wi-Fi, Google review, SEPA transfer (EPC / GiroCode)
  - *Payments & security* — Bitcoin, multi-chain crypto (Ethereum EIP-681,
    Litecoin, Dogecoin, Bitcoin Cash, Monero, Dash, Solana Pay), 2FA / TOTP
  - *Retail* — 1D barcodes (EAN-13, EAN-8, UPC-A, Code 128, Code 39, ITF-14)

  Each payload follows the standard its readers expect — EPC069-12 for SEPA,
  `otpauth://` for authenticators, EIP-681 for Ethereum, BIP-21 for the other
  chains, VEVENT for calendars — rather than a link to a page we control.
- **5 languages** (en, fr, es, de, it) with translated URLs, translated slugs and
  a full `hreflang` cluster on every page.
- **Static codes only.** The payload is encoded in the pattern, nothing is stored,
  nothing depends on this server once the image is downloaded.
- **Downloads** in PNG, SVG and WebP, up to 1024 px, with colour, margin and
  error-correction control.
- **Works without JavaScript**: the form is a plain GET form and the server renders
  the preview from the query string. JS only upgrades it to a live preview.

## SEO design

Everything below is implemented, not aspirational:

| Signal | Where |
|---|---|
| Unique `<title>` + meta description per page and per locale | `translations/messages.*.yaml` |
| Canonical built from `SITE_URL` (never from the request host) | `src/Seo/SeoFactory.php` |
| `hreflang` for the 5 locales + `x-default`, on pages **and** in the sitemap | `templates/base.html.twig`, `src/Seo/SitemapBuilder.php` |
| Localised paths *and* slugs (`/fr/outils/qr-code-wifi` ↔ `/de/werkzeuge/wlan-qr-code-generator`) | `src/Tool/ToolRegistry.php` |
| JSON-LD: `WebApplication`, `WebSite`, `HowTo`, `FAQPage`, `BreadcrumbList`, `BlogPosting`, `ItemList` | `src/Controller/*` |
| Pre-filled URLs marked `noindex, follow` so they never compete with the clean page | `ToolController::tool()` |
| `robots.txt` disallowing `/q/` and `/api/` (infinite crawl space) | `src/Controller/SeoController.php` |
| Atom feed per locale | `/{locale}/feed.xml` |
| Server-rendered HTML, one CSS file, no framework JS, `Cache-Control` on every page | AssetMapper + `setPublic()` |
| Unique editorial copy per tool page (not a shared boilerplate block) | `tool.*.copy_body` keys |
| Input validated to the real standard (IBAN mod 97, Base32 secrets, per-chain address formats) | `src/Generator/PayloadFactory.php` |

Sitemap size today: **139 URLs**, all indexable and cross-linked. The generic
generator deliberately has *no* landing page of its own — it is the home page,
and a second URL for it would be duplicate content.

## Monetisation

Advertising is opt-in, per visitor, and never blocks the tool:

1. Set `ADSENSE_ENABLED=1` and `ADSENSE_CLIENT=ca-pub-…`.
2. `/ads.txt` is then served automatically from that publisher id.
3. Google **Consent Mode v2** is declared with everything denied by default; the
   AdSense script is injected only after the visitor accepts
   (`assets/controllers/consent_controller.js`).
4. Three slots are available — `top`, `inline`, `footer`. Leave
   `ADSENSE_SLOT_*` empty to rely on Auto ads, or set the unit ids for manual placements.

The consent choice lives in `localStorage`, never in a cookie, and never reaches
the server — which is why the whole site can stay cacheable by a CDN.

## Collecting the email addresses

`LEAD_CAPTURE_MODE` decides how much the site asks for:

| Value | Behaviour |
|---|---|
| `off` | No email is ever requested. |
| `optional` | A discreet opt-in form sits next to the download buttons. |
| `download` (default) | The visitor is asked for an email the first time they download a code. |

In `download` mode, the address is remembered in the browser's `localStorage`, so
the same person is asked **once** and every later download is reported silently to
`/api/lead` — the row's `generation_count` goes up instead of the visitor being
interrupted again.

What ends up in the `leads` table: the address (lower-cased, one row per person),
the language, the generator used, the page they came from, first and last download
dates, how many downloads, and a **keyed hash** of the IP (`hash_hmac` with
`APP_SECRET`) that proves where a consent came from without storing an identifier.

Two things are kept apart on purpose:

- the **address**, required to deliver the download the visitor asked for;
- the **marketing consent**, a separate ticked box. Only that box authorises a
  newsletter, and a later download can never silently revoke it.

The endpoint is rate limited (10 submissions per IP per 10 minutes) and carries a
honeypot field: a bot that fills it gets a success it cannot distinguish from the
real one, and nothing is stored.

```bash
# Everything, as CSV, on stdout (the summary goes to stderr, so piping is safe)
php bin/console app:leads:export > leads.csv

# Only the addresses you are actually allowed to email
php bin/console app:leads:export --consented-only --since="-30 days" -o leads.csv

# Erasure request
php bin/console app:leads:forget someone@example.com
```

**Honest limitation:** the gate is enforced in the browser. Someone who reads the
HTML can still call `/q/png?...` directly. It is a lead-capture step, not a
paywall — and keeping it client-side is what lets every page stay fully cacheable
by a CDN. Visitors without JavaScript download without being asked.

The privacy, cookie and about pages already describe all of this, in the five
languages. If you change what you do with the addresses, change those pages too.

## Requirements

PHP 8.4 with `gd`, `intl` and a PDO driver. SQLite is the default and needs no
setup; PostgreSQL is the right choice as soon as you run more than one instance.
No Redis, no Node.js.

## Local development

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php -d variables_order=EGPCS -S 127.0.0.1:8000 -t public public/index.php
# then open http://127.0.0.1:8000/
```

(`variables_order=EGPCS` lets PHP's built-in server pass real environment
variables through to Symfony; the Docker image sets it in `docker/php.ini`.)

Run the checks:

```bash
php bin/phpunit
php bin/console lint:twig templates
php bin/console lint:yaml config translations
php bin/console debug:translation fr --only-missing
```

## Configuration

Copy the variables you need into `.env.local` (never commit it):

| Variable | Purpose |
|---|---|
| `SITE_NAME` | Brand shown in the header, titles and JSON-LD |
| `SITE_URL` | **Required in production.** Canonical origin used for every absolute URL |
| `CONTACT_EMAIL` | Shown on the About page |
| `ADSENSE_ENABLED` / `ADSENSE_CLIENT` | Advertising (see above) |
| `ADSENSE_SLOT_TOP` / `_INLINE` / `_FOOTER` | Optional manual ad units |
| `ANALYTICS_ID` | Optional GA4 id, also gated behind consent |
| `TRUSTED_PROXIES` | Set to your CDN/proxy range when behind one |
| `LEAD_CAPTURE_MODE` | `off`, `optional` or `download` (see above) |
| `DATABASE_URL` | SQLite by default; PostgreSQL recommended in production |
| `APP_SECRET` | Generate a random 32-byte hex string. **Also keys the IP hashes — changing it makes existing hashes unmatchable** |

## Deployment

```bash
docker build -t qr-generator .
docker run -p 8080:80 \
  -e APP_SECRET="$(openssl rand -hex 16)" \
  -e SITE_URL="https://your-domain.com" \
  qr-generator
```

The image is FrankenPHP (Caddy + PHP) in worker mode: one container, HTTP/2,
automatic HTTPS on a real domain, and OPcache preloading. Migrations run at boot
(set `RUN_MIGRATIONS_ON_BOOT=0` to handle them yourself). `compose.yaml` and
`fly.toml` are provided for Docker Compose and Fly.io respectively.

**Persisting the leads.** With the default SQLite database, mount a volume at
`/app/var/data` — `compose.yaml` and `fly.toml` already do. Without it, every
redeploy starts from an empty list. With PostgreSQL, set `DATABASE_URL` and drop
the volume.

### Go-live checklist

1. Point `SITE_URL` at the final domain — canonical and hreflang tags depend on it.
2. Submit `https://your-domain.com/sitemap.xml` in Google Search Console **and**
   Bing Webmaster Tools.
3. Check the rich results for one tool page and one article
   (`search.google.com/test/rich-results`).
4. Replace `public/og-image.svg` and `public/icon.svg` with your own branding.
5. Apply to AdSense **after** the site is live with its content — the legal pages
   (privacy, cookies, terms) and the consent banner are already in place, which is
   what the review looks for.
6. Set `ADSENSE_ENABLED=1` once approved, and verify `/ads.txt`.
7. Download a code yourself, then run `php bin/console app:leads:export` to check
   the address landed in the database — and that the volume survives a redeploy.

## Adding content

**A new article** — create `content/blog/{locale}/{file}.md`:

```markdown
---
key: shared-key-across-languages   # links translations together for hreflang
slug: url-slug-in-this-language
title: 'Article title'
description: 'Meta description, ~155 characters.'
date: 2026-08-26
tags: [print, guides]
tool: wifi                          # optional: shows a CTA to that generator
---

Markdown body…
```

Articles sharing a `key` are automatically declared as translations of each other
in the `hreflang` tags and in the sitemap.

**A new generator** — add a `Tool` to `src/Tool/ToolRegistry.php` (with one slug
per locale), a payload builder branch in `src/Generator/PayloadFactory.php`, demo
values in `src/Tool/ToolDemo.php`, and the `tool.<id>.*` and `faq.<id>.*` keys in
the five translation files. Routes, sitemap, hreflang, FAQ and structured data
follow automatically. Pass `standalone: false` for a tool that should not get a
landing page of its own.

## Architecture

```
src/
├── Blog/        Markdown articles and static pages (front matter + CommonMark)
├── Command/     CSV export and GDPR erasure for the collected addresses
├── Controller/  Thin controllers, one per surface
├── Entity/      The single table: leads
├── Generator/   Payload building, rendering, options — pure functions
├── Lead/        Email capture: modes, validation, IP hashing
├── Repository/  Lead lookups, upsert-on-download, streaming export
├── Seo/         Canonical/alternate URL building and sitemap
├── Tool/        Tool registry, localised slugs, demo values
└── Twig/        View helpers (icons, tool paths, JSON-LD)
```

The pages themselves stay stateless — no session, no cookie, fully cacheable. The
only write path is `/api/lead`, so the app still scales horizontally as long as
the database is shared (i.e. PostgreSQL rather than SQLite on a local volume).
