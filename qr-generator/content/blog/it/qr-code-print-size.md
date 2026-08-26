---
key: qr-code-print-size
slug: dimensione-stampa-qr-code
title: 'Di che dimensione stampare un QR code?'
description: 'La regola 10:1, le dimensioni minime per un biglietto da visita, un manifesto o un cartellone, e i cinque errori che rendono illeggibile un QR code stampato.'
date: 2026-08-04
updated: 2026-08-04
tags: [stampa, buone-pratiche]
tool: url
---

Un QR code che non si scansiona è peggio di nessun QR code: brucia la fiducia di chi ha tirato fuori il telefono. Quasi tutti i fallimenti dipendono da una di cinque cause, e la dimensione viene per prima.

## La regola 10:1

La regola usata dai professionisti della stampa è semplice:

> **Il codice deve misurare almeno un decimo della distanza da cui verrà scansionato.**

| Distanza di scansione | Dimensione minima | Supporto tipico |
|---|---|---|
| 20 cm | 2 cm | biglietto da visita, etichetta |
| 50 cm | 5 cm | volantino, menu, confezione |
| 1,5 m | 15 cm | vetrina, manifesto in corridoio |
| 5 m | 50 cm | manifesto murale, stand |
| 20 m | 2 m | cartellone, facciata |

La regola è volutamente prudente. Un telefono recente fa di meglio con buona luce e un URL corto, e molto peggio in un locale in penombra o su una stampa sporca. Progetta per il peggior caso realistico, non per il tuo telefono.

## Minimo assoluto: 2 cm

Sotto i 2 × 2 cm la stampa offset o a getto d'inchiostro comincia a far fondere i moduli più piccoli. Se devi scendere ancora, riduci i dati codificati: un URL di 25 caratteri produce un motivo molto più grosso — quindi più robusto — di uno da 120 caratteri pieno di parametri di tracciamento.

## La zona di silenzio non è facoltativa

Il margine bianco attorno al codice dice al lettore dove finisce il motivo. La norma chiede quattro moduli; in pratica lascia una fascia bianca larga più o meno quanto uno dei tre quadrati d'angolo. Attaccare il codice al bordo di un fondo colorato è l'errore d'impaginazione più comune.

## Contrasto: scuro su chiaro, mai il contrario

I lettori si aspettano moduli scuri su fondo chiaro. Un codice invertito fallisce su una quota rilevante di dispositivi. I codici colorati funzionano finché il contrasto resta alto: blu scuro o verde intenso su bianco sì, giallo pastello su crema no. Su materiali colorati o testurizzati metti un rettangolo bianco dietro al codice.

## Correzione degli errori: M per gli schermi, H per l'esterno

Il livello di correzione stabilisce quanta parte del motivo può essere distrutta restando leggibile: 7 % (L), 15 % (M), 25 % (Q) o 30 % (H). Più correzione significa motivo più denso a parità di contenuto.

- **M** — scelta sensata per schermi, PDF e stampa pulita.
- **Q o H** — all'aperto, su confezioni manipolate, su tessuto, su superfici curve e ogni volta che un logo si sovrappone al codice.

## Stampa sempre da un file vettoriale

Esporta l'SVG, non il PNG. Un'immagine raster ridimensionata dalla tipografia produce bordi dei moduli sfocati, ed è proprio la sfocatura a rompere la decodifica alle piccole dimensioni.

## Il test di cinque minuti che salva una tiratura

1. Stampa a dimensione finale sul materiale reale.
2. Scansiona con un iPhone e con un Android, alla distanza reale.
3. Scansiona con l'illuminazione del luogo d'uso.
4. Scansiona di sbieco, non solo frontalmente.
5. Falla provare a qualcuno che non l'ha mai visto, senza istruzioni.

Se passa tutti e cinque, puoi stampare diecimila copie con tranquillità.
