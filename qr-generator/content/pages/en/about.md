---
title: 'About this generator'
description: 'Who is behind this free QR code and barcode generator, how it is funded and why the codes never expire.'
---

## A tool that stays free

This site does one thing: it turns your data into a code you can download and use, immediately, without an account. No trial period, no "premium" plan hiding the useful features, no watermark on the free tier.

## How it is funded

Advertising, and nothing else. Ads are only loaded once you have accepted them, and the site works exactly the same if you refuse. We do not sell data, we do not run affiliate redirects inside your codes, and we never insert a tracking domain between your code and its destination.

## Why static codes only

Every code generated here is **static**: the content is encoded in the pattern itself. That means:

- the code works forever, even offline, even if this website disappears;
- nobody can switch it off, meter it or start charging you a monthly fee for it;
- no third party — us included — sees who scans it or when.

The trade-off is that you cannot change the destination afterwards. If you expect to change it, encode a URL you control and change the redirect on your own server.

## Technical choices

The site is a Symfony application rendered on the server, with a thin JavaScript layer for the live preview. Codes are generated in memory and returned directly to your browser: nothing is written to disk, nothing is logged, nothing is kept.

## Contact

Questions, bug reports or a symbology you would like us to add? Write to us and we will read it.
