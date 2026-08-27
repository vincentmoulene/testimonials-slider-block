---
key: qr-code-print-size
slug: qr-code-druckgroesse
title: 'Wie groß muss ein QR-Code gedruckt werden?'
description: 'Die 10:1-Regel, Mindestgrößen für Visitenkarte, Plakat und Großfläche sowie die fünf Fehler, die einen gedruckten QR-Code unlesbar machen.'
date: 2026-08-04
updated: 2026-08-04
tags: [druck, best-practices]
tool: qrcode
---

Ein QR-Code, der sich nicht scannen lässt, ist schlimmer als gar keiner: Er verbrennt das Vertrauen der Person, die extra ihr Telefon herausgeholt hat. Fast jedes Scheitern geht auf eine von fünf Ursachen zurück — und die Größe steht an erster Stelle.

## Die 10:1-Regel

Die Faustregel aus der Druckpraxis lautet:

> **Der Code sollte mindestens ein Zehntel der Entfernung messen, aus der er gescannt wird.**

| Scan-Entfernung | Mindestgröße | Typisches Medium |
|---|---|---|
| 20 cm | 2 cm | Visitenkarte, Produktetikett |
| 50 cm | 5 cm | Flyer, Speisekarte, Verpackung |
| 1,5 m | 15 cm | Schaufenster, Plakat im Flur |
| 5 m | 50 cm | Wandplakat, Messestand |
| 20 m | 2 m | Großfläche, Fassade |

Die Regel ist bewusst konservativ. Ein aktuelles Telefon schafft bei gutem Licht und kurzer URL mehr — in einem dunklen Restaurant oder auf verschmiertem Druck deutlich weniger. Planen Sie für den schlechtesten realistischen Fall, nicht für Ihr eigenes Gerät.

## Absolutes Minimum: 2 cm

Unter 2 × 2 cm laufen im gewöhnlichen Offset- oder Tintenstrahldruck die kleinsten Module ineinander. Wenn es kleiner sein muss, reduzieren Sie die codierte Datenmenge: Eine URL mit 25 Zeichen ergibt ein viel gröberes und damit robusteres Muster als eine mit 120 Zeichen voller Tracking-Parameter.

## Die Ruhezone ist keine Option

Der weiße Rand um den Code sagt dem Scanner, wo das Muster endet. Die Norm verlangt vier Module; halten Sie in der Praxis einen weißen Streifen etwa so breit wie eines der drei großen Eckquadrate. Den Code an die Kante einer Farbfläche zu setzen ist der häufigste Layoutfehler.

## Kontrast: dunkel auf hell, nie umgekehrt

Scanner erwarten dunkle Module auf hellem Grund. Ein invertierter Code scheitert auf einem erheblichen Teil der Geräte. Farbige Codes funktionieren, solange der Kontrast hoch bleibt: Dunkelblau oder Tannengrün auf Weiß ja, Pastellgelb auf Creme nein. Auf farbigem oder strukturiertem Material gehört ein weißes Rechteck hinter den Code.

## Fehlerkorrektur: M für Bildschirme, H für draußen

Die Fehlerkorrektur bestimmt, wie viel des Musters zerstört sein darf: 7 % (L), 15 % (M), 25 % (Q) oder 30 % (H). Mehr Korrektur bedeutet bei gleichem Inhalt ein dichteres Muster.

- **M** — sinnvolle Voreinstellung für Bildschirm, PDF und sauberen Druck.
- **Q oder H** — im Freien, auf angefasster Verpackung, auf Textil, auf gewölbten Flächen und immer, wenn ein Logo im Code sitzt.

## Immer aus einer Vektordatei drucken

Exportieren Sie das SVG, nicht das PNG. Ein von der Druckerei skaliertes Pixelbild erzeugt weiche Modulkanten — und genau die zerstören die Decodierung bei kleinen Größen.

## Der Fünf-Minuten-Test, der eine Auflage rettet

1. In Endgröße auf dem echten Material drucken.
2. Mit einem iPhone und einem Android-Gerät scannen, aus realer Entfernung.
3. Im Licht des Einsatzortes scannen.
4. Schräg scannen, nicht nur frontal.
5. Jemanden testen lassen, der den Code nie gesehen hat — ohne Anleitung.

Bestehen alle fünf, können Sie beruhigt zehntausend Exemplare drucken.
