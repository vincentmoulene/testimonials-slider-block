---
key: qr-code-print-size
slug: qr-code-print-size
title: 'What size should a QR code be printed at?'
description: 'The 10:1 rule, minimum sizes for a business card, a poster or a billboard, and the five mistakes that make a printed QR code unscannable.'
date: 2026-08-04
updated: 2026-08-04
tags: [print, best-practices]
tool: url
---

A QR code that does not scan is worse than no QR code at all: it burns the trust of the person who bothered to take out their phone. Almost every failure comes down to one of five things, and size is the first.

## The 10:1 rule

The working rule used by print professionals is simple:

> **The code should measure at least one tenth of the distance from which it will be scanned.**

| Scanning distance | Minimum code size | Typical medium |
|---|---|---|
| 20 cm | 2 cm | business card, product label |
| 50 cm | 5 cm | flyer, menu, packaging |
| 1.5 m | 15 cm | shop window, poster in a corridor |
| 5 m | 50 cm | wall poster, exhibition stand |
| 20 m | 2 m | billboard, building facade |

The rule is deliberately conservative. Modern phones can do better in good light with a short URL — and much worse in a dim restaurant, with a smudged print or with a two-year-old mid-range handset. Design for the worst realistic case, not for your own phone.

## Absolute minimum: 2 cm

Below 2 × 2 cm, ordinary offset or inkjet printing starts to blur the smallest modules together. If you must go smaller, cut the amount of encoded data — a 25-character URL produces a far coarser, more robust pattern than a 120-character one with tracking parameters attached.

## The quiet zone is not optional

The white margin around the code — the *quiet zone* — is what tells the scanner where the pattern ends. The standard asks for four modules; in practice, keep a white band roughly as wide as one of the three big corner squares.

The most common design mistake is bleeding the code to the edge of a coloured block, or letting text run right up against it. Both cost you scans.

## Contrast: dark on light, never the reverse

Scanners expect dark modules on a light background. An inverted code (white on black) fails on a significant share of devices. Coloured codes work as long as the contrast ratio stays high — a dark blue or a deep green on white is fine; a pastel yellow on cream is not.

If you print on a coloured or textured material, add a plain white rectangle behind the code. It looks less elegant in the mock-up and it is the difference between a campaign that works and one that does not.

## Error correction: M for screens, H for the real world

The error-correction level decides how much of the pattern can be destroyed while remaining readable: 7% (L), 15% (M), 25% (Q) or 30% (H). More correction means a denser pattern for the same content, so it is a trade-off, not a free upgrade.

- **M** — the sensible default for screens, PDFs and clean printing.
- **Q or H** — outdoors, on packaging that gets handled, on textiles, on anything curved, and whenever a logo sits on top of the code.

## Always print from a vector file

Export the SVG, not the PNG. A bitmap resized by the printer produces soft module edges, and soft edges are exactly what breaks decoding at small sizes. SVG stays mathematically sharp at 2 cm and at 2 m, and every professional printing workflow accepts it.

## The five-minute test that saves a print run

Before signing off the file:

1. Print the artwork at final size on the actual material.
2. Scan it with an iPhone and an Android phone, at the real distance.
3. Scan it in the lighting where it will live — a dim bar is not an office.
4. Scan it at an angle, not just straight on.
5. Have someone who has never seen it try, without instructions.

If all five pass, you can print ten thousand copies with confidence. If one fails, you have just saved yourself a reprint.
