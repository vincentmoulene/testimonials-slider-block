---
key: use-case-packaging
slug: qr-code-packaging
title: 'QR codes on packaging: the manual nobody throws away'
description: 'Instructions, tutorials, warranty, traceability, spare parts: what a QR code adds to a package, and the printing constraint that decides everything.'
date: 2026-08-11
updated: 2026-08-11
tags: [packaging, use-cases]
tool: qrcode
---

A paper manual costs money to print, takes up space, exists in one language and goes into the bin with the box. A QR code does the same job in 2 cm², in every language, and it is still there three years later when the customer wants to know how to descale the thing.

## What goes behind the code

- **The full manual**, in the phone's language. You print a two-page leaflet instead of sixteen.
- **A video tutorial.** For assembly, thirty seconds of video beats three pages of diagrams.
- **Warranty registration.** The customer scans, types an email: you get a contact, they get a warranty without stapling a receipt into a drawer.
- **Maintenance and spare parts.** This is what drives repeat purchases, and it is almost always forgotten.
- **Traceability.** Origin, batch, date. For food and cosmetics this is becoming an expectation, not a bonus.
- **Recycling.** Up-to-date sorting instructions, without reprinting the box every time the rules change.

## The constraint that decides everything: printing

Packaging is not a sheet of A4. It is flexo-printed on corrugated board, screen-printed on plastic or etched into metal — and sometimes on a curved surface.

Four rules:

1. **Supply the vector file (SVG or EPS).** The printer will rescale, and a rescaled PNG gives you blurred modules.
2. **2 cm minimum**, more on corrugated board where the ink spreads.
3. **On a curved surface** (bottle, tube), put the code on the flattest part and raise error correction to Q or H.
4. **Never place the code on a fold or a seam.** It is the most common mistake, and it is fatal.

## The long-term trap

Packaging outlives your website. A run of 200,000 boxes stays in circulation for two or three years, sometimes longer at the back of a warehouse.

So encode a **short, stable URL you control**: `yourbrand.com/p/REF`. Not the long URL your CMS generates, and not one belonging to a vendor whose subscription you may not renew. When the site changes you change the redirect; the box cannot be reprinted.

## And soon: the barcode replaced

The **GS1 Digital Link** standard is preparing to replace the EAN barcode with a 2D code carrying both the product identifier for the till and a link to consumer information. The migration is under way globally. If you are designing packaging today for the next three years, now is the time to look into it.

## The takeaway

On a package, a QR code replaces paper nobody reads with information the customer genuinely wants — on the day they need it, not on the day they buy.
