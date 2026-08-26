---
key: static-vs-dynamic
slug: static-vs-dynamic-qr-codes
title: 'Static vs dynamic QR codes: what the subscription really buys you'
description: 'Dynamic QR codes are editable and trackable — and they stop working the day you stop paying. Here is how to decide, and how to get editability without the subscription.'
date: 2026-08-11
updated: 2026-08-11
tags: [strategy, tracking]
tool: url
---

Every paid QR code service sells the same headline feature: *"change the destination after printing"*. It is a real benefit, and it comes with a real hostage situation. Understanding the mechanism makes the decision obvious.

## How each one works

A **static** code contains the destination itself. Scanning `https://example.com/menu` reads exactly those characters out of the pattern and hands them to the browser. No server in the middle. Nothing to maintain.

A **dynamic** code contains a short URL owned by the provider — `https://qr.provider.io/a7Xk2` — which redirects to your real destination. The provider can change that redirect, and counts every scan on the way through.

## What you gain with dynamic codes

- **Editable destination.** Print once, repoint later.
- **Scan analytics.** Count, timestamp, rough location, device type.
- **A/B testing and expiry rules**, on the more advanced plans.

For a printed catalogue with a two-year life, or a packaging run of 200,000 units, that flexibility is worth real money.

## What you give up

1. **A dependency that outlives the campaign.** Stop paying and the redirect dies. Every printed code becomes a dead link — on packaging you cannot recall.
2. **A third party between you and your audience.** Every scan passes through someone else's domain, logged with an IP address. In the EU that is a data-processing relationship you have to document.
3. **A trust cost.** A scanner preview showing `qr.provider.io` instead of your own domain looks like a phishing link to anyone paying attention.
4. **Fragility.** Provider outage, domain seizure, acquisition, price change: all of them break codes you have already printed.

## The third option almost nobody mentions

You can have editability *and* independence, without a subscription:

1. Create a redirect on **your own domain**: `https://yourbrand.com/go/menu`.
2. Encode that URL in a **static** QR code.
3. When the destination changes, change your own redirect.

The printed code never changes. The redirect is a two-line rule in your web server, a page on your CMS, or a free redirect on any hosting you already pay for. You own the domain, so nobody can switch it off. And if you want scan counts, your existing web analytics already records hits on that URL.

The only thing you lose is the provider's dashboard — replaced by data you already have.

## A simple decision rule

| Situation | Use |
|---|---|
| Fixed destination (Wi-Fi, vCard, phone, a permanent page) | Static |
| Short campaign, destination known | Static |
| Long-lived print, destination may change, you own a domain | Static code on your own redirect |
| Large-scale campaign needing granular per-code analytics, no in-house tooling | Dynamic, with a documented exit plan |

## If you do go dynamic

Ask three questions before signing:

- What happens to my codes if I stop paying? (Often: they die immediately.)
- Can I export the mapping of every code to its destination?
- Can I point the provider's redirects at a subdomain I own, so I can take them back?

If the answer to the third is yes, most of the risk disappears. If it is no, you are renting your printed material.

## The takeaway

Dynamic codes are not a scam — they solve a genuine problem. But most of the time, the problem they solve is one you can solve yourself with a redirect on your own domain and a static code that will still work in fifteen years.
