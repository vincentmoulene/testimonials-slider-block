---
key: wifi-qr-code-guide
slug: wifi-qr-code-guide
title: 'Wi-Fi QR codes: the complete guide for hosts, cafés and offices'
description: 'How a Wi-Fi QR code works, which phones support it, the SSID mistakes that break it, and why it is more secure than writing the password on a blackboard.'
date: 2026-08-18
updated: 2026-08-18
tags: [wifi, guides]
tool: wifi
---

If you still dictate a twenty-character Wi-Fi password several times a day, you are doing manual labour a piece of paper can do better. A Wi-Fi QR code takes thirty seconds to create and removes the task permanently.

## What is actually inside the code

Nothing magic — a single line of text following a convention every camera app understands:

```
WIFI:T:WPA;S:MyNetwork;P:my-password;;
```

`T` is the security type, `S` the network name, `P` the password. That is the whole standard. The phone parses it, recognises a network configuration and offers to join.

Because it is plain text, the password **is** readable by anyone who decodes the image with a QR reader. A Wi-Fi code protects against typing mistakes and awkwardness, not against a determined guest. Which is fine: they were going to get the password anyway.

## Which devices support it

- **iPhone / iPad**: natively from the camera app since iOS 11 (2017).
- **Android**: natively since Android 10 (2019). Android 11 and later can also *generate* a code for the network the phone is on, in Settings → Network → Share.
- **Older Android**: needs a QR app that supports Wi-Fi payloads. Most free readers do.

In practice, in 2026, essentially every phone that walks into a café can use it.

## The four mistakes that break it

1. **Wrong case in the SSID.** `MyNetwork` and `mynetwork` are two different networks as far as the phone is concerned. Copy the name exactly as it appears in your router's admin page.
2. **Special characters not escaped.** A semicolon, a colon, a comma or a backslash in the password must be escaped in the payload. A generator handles this for you — writing the string by hand usually does not.
3. **Ticking "hidden network" when the SSID is broadcast.** This makes some phones refuse the connection outright. Only tick it if the SSID genuinely is not broadcast.
4. **Choosing the wrong security type.** Almost every modern router is WPA2 or WPA3 — both use the `WPA` setting. `WEP` only applies to hardware old enough to be a security problem in itself. `nopass` is for genuinely open networks.

## Where to put it

- **Holiday rentals and hotels**: laminated card on the desk, plus one in the welcome booklet.
- **Cafés and restaurants**: on the table tent or the back of the menu, not only at the counter.
- **Offices**: next to the meeting-room screen — visitors join without interrupting anyone.
- **Waiting rooms**: on the poster that already tells people the wait time.

Print it at least 5 cm wide, in black on white, and add one line of text: *"Scan to join the Wi-Fi"*. Not everybody knows the trick yet.

## The guest-network habit

The right security practice is not to hide the password — it is to separate the networks. Put guests on the guest SSID of your router (nearly every model has one), generate the code for that network, and keep your own devices, NAS and printers on the main one. The code then costs you nothing in security, and saves you a conversation every single day.

## Rotating the password

If you change the Wi-Fi password, the printed code stops working — it contains the old one. Two habits make this painless: keep the source file (SVG) with your network documentation, and regenerate + reprint the card the same day you change the password. It takes a minute.
