# QR & barcode generator — free, multilingual, SEO-first

A Symfony 8 application that generates QR codes and barcodes for free, in five
languages, with no account, no database and no expiry date. It is designed to be
left online permanently and funded by advertising.

```
/                       → redirects to the visitor's best-matching language
/{locale}               → home page + URL QR generator
/{locale}/tools         → all generators (localised path and slug)
/{locale}/tools/{slug}  → one landing page per generator, per language
/{locale}/blog          → file-based blog (Markdown)
/q/{png|svg|webp}       → stateless image endpoint (download + no-JS fallback)
/api/generate           → JSON endpoint used by the live preview
/sitemap.xml            → every URL, with hreflang alternates
```

## What it does

- **10 generators**: URL, text, Wi-Fi, vCard, email, SMS, phone, WhatsApp,
  geolocation, and 1D barcodes (EAN-13, EAN-8, UPC-A, Code 128, Code 39, ITF-14).
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

Sitemap size today: **94 URLs**, all indexable and cross-linked.

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

## Requirements

PHP 8.4 with `gd` and `intl`. No database, no Redis, no Node.js.

## Local development

```bash
composer install
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
| `APP_SECRET` | Generate a random 32-byte hex string |

## Deployment

```bash
docker build -t qr-generator .
docker run -p 8080:80 \
  -e APP_SECRET="$(openssl rand -hex 16)" \
  -e SITE_URL="https://your-domain.com" \
  qr-generator
```

The image is FrankenPHP (Caddy + PHP) in worker mode: one container, HTTP/2,
automatic HTTPS on a real domain, and OPcache preloading. `compose.yaml` and
`fly.toml` are provided for Docker Compose and Fly.io respectively.

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
follow automatically.

## Architecture

```
src/
├── Blog/        Markdown articles and static pages (front matter + CommonMark)
├── Controller/  Thin controllers, one per surface
├── Generator/   Payload building, rendering, options — pure functions
├── Seo/         Canonical/alternate URL building and sitemap
├── Tool/        Tool registry, localised slugs, demo values
└── Twig/        View helpers (icons, tool paths, JSON-LD)
```

Nothing is persisted, so the app scales horizontally with zero shared state.
